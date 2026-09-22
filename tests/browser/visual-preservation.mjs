import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { chromium } from 'playwright';
import pixelmatch from 'pixelmatch';
import { PNG } from 'pngjs';

const root = path.resolve(import.meta.dirname, '../..');
const composeFile = path.join(root, 'tests/docker-compose.runtime.yml');
const outputDir = path.join(root, 'tests/browser-artifacts');
const siteUrl = process.env.CANDY_CANE_SITE_URL || `http://127.0.0.1:${process.env.CANDY_CANE_RUNTIME_PORT || '8080'}`;
const projectName = process.env.COMPOSE_PROJECT_NAME || `candy-cane-runtime-${process.env.GITHUB_RUN_ID || 'local'}`;
const maxHeightDrift = 0.02;

const viewports = [
  { name: 'desktop', width: 1440, height: 1000 },
  { name: 'tablet', width: 768, height: 1024 },
  { name: 'compact', width: 390, height: 844 },
];

const routes = [
  { name: 'home', path: '/', maxDiff: { default: 0.005 } },
  {
    name: 'single',
    path: '/?name=visible-runtime-post',
    maxDiff: { default: 0.015, tablet: 0.02, compact: 0.035 },
  },
  { name: 'page', path: '/?pagename=candy-cane-runtime-page', maxDiff: { default: 0.005 } },
  { name: 'category', path: '/?category_name=visible-runtime', maxDiff: { default: 0.005 } },
  { name: 'search', path: '/?s=Visible+Runtime', maxDiff: { default: 0.005 } },
  { name: '404', path: '/?p=999999', maxDiff: { default: 0.01, compact: 0.075 } },
];

function wp(...args) {
  return execFileSync(
    'docker',
    ['compose', '-f', composeFile, 'run', '--rm', '--no-deps', 'cli', ...args],
    {
      cwd: root,
      env: { ...process.env, COMPOSE_PROJECT_NAME: projectName },
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'pipe'],
    },
  ).trim();
}

function activateTheme(slug) {
  wp('theme', 'activate', slug);

  // Theme menu locations are stored as theme mods, so assign the same real menus
  // to both slugs before comparing the rendered products.
  wp('menu', 'location', 'assign', 'Main Navigation', 'header-menu1');
  wp('menu', 'location', 'assign', 'Secondary Navigation', 'header-menu2');
}

function makeCanvas(source, width, height) {
  const canvas = new PNG({ width, height });
  canvas.data.fill(255);

  for (let y = 0; y < source.height; y += 1) {
    const sourceStart = y * source.width * 4;
    const sourceEnd = sourceStart + source.width * 4;
    const targetStart = y * width * 4;
    source.data.copy(canvas.data, targetStart, sourceStart, sourceEnd);
  }

  return canvas;
}

function compareScreenshots(referencePath, currentPath, diffPath, maxDiff) {
  const reference = PNG.sync.read(fs.readFileSync(referencePath));
  const current = PNG.sync.read(fs.readFileSync(currentPath));
  const width = Math.max(reference.width, current.width);
  const height = Math.max(reference.height, current.height);
  const referenceCanvas = makeCanvas(reference, width, height);
  const currentCanvas = makeCanvas(current, width, height);
  const diff = new PNG({ width, height });

  const diffPixels = pixelmatch(
    referenceCanvas.data,
    currentCanvas.data,
    diff.data,
    width,
    height,
    {
      threshold: 0.12,
      includeAA: false,
      alpha: 0.5,
      diffColor: [255, 0, 0],
      aaColor: [255, 255, 0],
    },
  );

  fs.writeFileSync(diffPath, PNG.sync.write(diff));

  const totalPixels = width * height;
  const diffRatio = diffPixels / totalPixels;
  const heightDrift = Math.abs(reference.height - current.height) / Math.max(reference.height, current.height);

  return {
    referenceWidth: reference.width,
    referenceHeight: reference.height,
    currentWidth: current.width,
    currentHeight: current.height,
    diffPixels,
    totalPixels,
    diffRatio,
    maxDiff,
    heightDrift,
    maxHeightDrift,
    passed: diffRatio <= maxDiff && heightDrift <= maxHeightDrift,
  };
}

async function preparePage(context, pageErrors) {
  await context.route('**/*', async (route) => {
    const requestUrl = new URL(route.request().url());
    if (requestUrl.hostname === '127.0.0.1' || requestUrl.hostname === 'localhost') {
      await route.continue();
      return;
    }

    // Historical Candy Cane loads external jQuery over HTTP. The embedded legacy
    // bundle supplies its own copy, so external traffic is intentionally blocked
    // to keep the preservation render deterministic and offline-safe.
    await route.abort();
  });

  const page = await context.newPage();
  page.on('pageerror', (error) => pageErrors.push(error.message));
  return page;
}

async function captureTheme(browser, slug, label) {
  activateTheme(slug);
  const captures = [];

  for (const viewport of viewports) {
    const context = await browser.newContext({
      viewport: { width: viewport.width, height: viewport.height },
      deviceScaleFactor: 1,
      colorScheme: 'light',
      locale: 'en-US',
      timezoneId: 'UTC',
      reducedMotion: 'reduce',
    });
    const pageErrors = [];
    const page = await preparePage(context, pageErrors);

    for (const route of routes) {
      const response = await page.goto(`${siteUrl}${route.path}`, {
        waitUntil: 'networkidle',
        timeout: 30000,
      });

      if (!response) {
        throw new Error(`${label}/${viewport.name}/${route.name} returned no browser response.`);
      }

      if (route.name !== '404' && !response.ok()) {
        throw new Error(`${label}/${viewport.name}/${route.name} returned HTTP ${response.status()}.`);
      }

      if (route.name === '404' && response.status() !== 404) {
        throw new Error(`${label}/${viewport.name}/404 returned HTTP ${response.status()} instead of 404.`);
      }

      await page.evaluate(async () => {
        if (document.fonts?.ready) {
          await document.fonts.ready;
        }
      });

      const screenshotPath = path.join(outputDir, `${label}-${viewport.name}-${route.name}.png`);
      await page.screenshot({
        path: screenshotPath,
        fullPage: true,
        animations: 'disabled',
        caret: 'hide',
      });

      captures.push({ viewport, route, screenshotPath });
    }

    if (label === 'current') {
      await page.goto(`${siteUrl}/`, { waitUntil: 'networkidle', timeout: 30000 });
      const firstCardLink = page.locator('.img-cont a').first();
      if (await firstCardLink.count()) {
        await firstCardLink.focus();
        await page.waitForTimeout(50);
        const maskOpacity = await page.locator('.img-cont .mask').first().evaluate((element) => getComputedStyle(element).opacity);
        if (Number.parseFloat(maskOpacity) < 0.95) {
          throw new Error(`Keyboard focus did not reveal the image overlay at ${viewport.name}; opacity=${maskOpacity}.`);
        }
      }

      if (pageErrors.length > 0) {
        throw new Error(`Current Candy Cane emitted browser page errors at ${viewport.name}: ${pageErrors.join(' | ')}`);
      }
    }

    await context.close();
  }

  return captures;
}

async function main() {
  fs.rmSync(outputDir, { recursive: true, force: true });
  fs.mkdirSync(outputDir, { recursive: true });

  const browser = await chromium.launch({ headless: true });

  try {
    await captureTheme(browser, 'candy-cane-legacy', 'legacy');
    await captureTheme(browser, 'candy-cane', 'current');
  } finally {
    await browser.close();
  }

  const report = [];
  let failed = false;

  for (const viewport of viewports) {
    for (const route of routes) {
      const maxDiff = route.maxDiff[viewport.name] ?? route.maxDiff.default;
      const referencePath = path.join(outputDir, `legacy-${viewport.name}-${route.name}.png`);
      const currentPath = path.join(outputDir, `current-${viewport.name}-${route.name}.png`);
      const diffPath = path.join(outputDir, `diff-${viewport.name}-${route.name}.png`);
      const result = compareScreenshots(referencePath, currentPath, diffPath, maxDiff);
      const record = {
        viewport: viewport.name,
        route: route.name,
        ...result,
      };

      report.push(record);
      failed ||= !record.passed;
      console.log(
        `${record.passed ? 'PASS' : 'FAIL'} ${viewport.name}/${route.name}: ` +
          `visual diff ${(record.diffRatio * 100).toFixed(3)}% <= ${(record.maxDiff * 100).toFixed(1)}%, ` +
          `height drift ${(record.heightDrift * 100).toFixed(3)}% <= ${(record.maxHeightDrift * 100).toFixed(1)}%`,
      );
    }
  }

  fs.writeFileSync(path.join(outputDir, 'visual-report.json'), `${JSON.stringify(report, null, 2)}\n`);

  if (failed) {
    throw new Error('Candy Cane visual preservation threshold exceeded. Review browser-artifact diff images before changing tolerances.');
  }

  console.log(`Candy Cane browser preservation passed across ${report.length} route/viewport comparisons.`);
}

main().catch((error) => {
  console.error(error.stack || error.message || error);
  process.exitCode = 1;
});
