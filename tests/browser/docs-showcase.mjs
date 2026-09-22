import fs from 'node:fs';
import path from 'node:path';
import { chromium } from 'playwright';

const root = path.resolve(import.meta.dirname, '../..');
const outputDir = path.join(root, 'docs-showcase-artifacts');
const siteUrl = process.env.CANDY_CANE_SITE_URL || `http://127.0.0.1:${process.env.CANDY_CANE_RUNTIME_PORT || '8080'}`;

const captures = [
  { name: 'desktop-home', path: '/', width: 1440, height: 1000, fullPage: true },
  { name: 'desktop-single', path: '/?name=candy-colors-reframed', width: 1440, height: 1000, fullPage: true },
  { name: 'desktop-page', path: '/?pagename=about-candy-cane', width: 1440, height: 1000, fullPage: true },
  { name: 'tablet-home', path: '/', width: 820, height: 1180, fullPage: true },
  { name: 'mobile-home', path: '/', width: 390, height: 844, fullPage: true },
  { name: 'mobile-single', path: '/?name=candy-colors-reframed', width: 390, height: 844, fullPage: true },
];

async function preparePage(context, errors) {
  await context.route('**/*', async (route) => {
    const url = new URL(route.request().url());
    if (url.hostname === '127.0.0.1' || url.hostname === 'localhost') {
      await route.continue();
      return;
    }
    await route.abort();
  });

  const page = await context.newPage();
  page.on('pageerror', (error) => errors.push(error.message));
  return page;
}

async function waitForStableRender(page) {
  await page.evaluate(async () => {
    if (document.fonts?.ready) {
      await document.fonts.ready;
    }
  });
  await page.waitForTimeout(100);
}

async function main() {
  fs.rmSync(outputDir, { recursive: true, force: true });
  fs.mkdirSync(outputDir, { recursive: true });

  const browser = await chromium.launch({ headless: true });
  const manifest = [];

  try {
    for (const capture of captures) {
      const context = await browser.newContext({
        viewport: { width: capture.width, height: capture.height },
        deviceScaleFactor: 1,
        colorScheme: 'light',
        locale: 'en-US',
        timezoneId: 'UTC',
        reducedMotion: 'reduce',
      });
      const errors = [];
      const page = await preparePage(context, errors);
      const response = await page.goto(`${siteUrl}${capture.path}`, { waitUntil: 'networkidle', timeout: 30000 });

      if (!response?.ok()) {
        throw new Error(`${capture.name} returned HTTP ${response?.status() ?? 'no response'}.`);
      }

      await waitForStableRender(page);
      const screenshotPath = path.join(outputDir, `${capture.name}.png`);
      await page.screenshot({ path: screenshotPath, fullPage: capture.fullPage, animations: 'disabled', caret: 'hide' });

      if (errors.length > 0) {
        throw new Error(`${capture.name} emitted browser page errors: ${errors.join(' | ')}`);
      }

      manifest.push({
        file: `${capture.name}.png`,
        route: capture.path,
        viewport: `${capture.width}x${capture.height}`,
        fullPage: capture.fullPage,
      });
      await context.close();
    }

    const context = await browser.newContext({
      viewport: { width: 1440, height: 1000 },
      deviceScaleFactor: 1,
      colorScheme: 'light',
      locale: 'en-US',
      timezoneId: 'UTC',
      reducedMotion: 'reduce',
    });
    const errors = [];
    const page = await preparePage(context, errors);
    await page.goto(`${siteUrl}/`, { waitUntil: 'networkidle', timeout: 30000 });
    await waitForStableRender(page);

    const firstCardLink = page.locator('.img-cont a').first();
    if ((await firstCardLink.count()) === 0) {
      throw new Error('Showcase home page did not render a focusable Candy Cane image card.');
    }
    await firstCardLink.focus();
    await page.waitForTimeout(100);

    const opacity = await page.locator('.img-cont .mask').first().evaluate((element) => getComputedStyle(element).opacity);
    if (Number.parseFloat(opacity) < 0.95) {
      throw new Error(`Keyboard focus did not reveal the Candy Cane image overlay; opacity=${opacity}.`);
    }

    const overlayPath = path.join(outputDir, 'desktop-keyboard-overlay.png');
    await page.screenshot({ path: overlayPath, fullPage: false, animations: 'disabled', caret: 'hide' });
    if (errors.length > 0) {
      throw new Error(`keyboard-overlay emitted browser page errors: ${errors.join(' | ')}`);
    }
    manifest.push({ file: 'desktop-keyboard-overlay.png', route: '/', viewport: '1440x1000', feature: 'keyboard image-card overlay' });
    await context.close();
  } finally {
    await browser.close();
  }

  fs.writeFileSync(path.join(outputDir, 'showcase-manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`);
  console.log(`Captured ${manifest.length} Candy Cane docs showcase assets.`);
}

main().catch((error) => {
  console.error(error.stack || error.message || error);
  process.exitCode = 1;
});
