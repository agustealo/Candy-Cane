import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { chromium } from 'playwright';

const root = path.resolve(import.meta.dirname, '../..');
const composeFile = path.join(root, 'tests/docker-compose.runtime.yml');
const outputDir = path.join(root, 'docs-showcase-artifacts');
const siteUrl = process.env.CANDY_CANE_SITE_URL || `http://127.0.0.1:${process.env.CANDY_CANE_RUNTIME_PORT || '8080'}`;
const projectName = process.env.COMPOSE_PROJECT_NAME || `candy-cane-showcase-${process.env.GITHUB_RUN_ID || 'local'}`;

const captures = [
  // The README hero is intentionally viewport-cropped so it presents the real
  // home composition without CI-style dead canvas below the footer.
  { name: 'desktop-home', path: '/', width: 1440, height: 720, fullPage: false },
  // WordPress.org recommends a 1200x900 (4:3) theme screenshot. Generate it
  // from the same deterministic real WordPress runtime, not a hand-built mock.
  { name: 'theme-screenshot', path: '/', width: 1200, height: 900, fullPage: false },
  { name: 'desktop-single', path: '/?name=candy-colors-reframed', width: 1440, height: 1000, fullPage: true },
  { name: 'desktop-page', path: '/?pagename=about-candy-cane', width: 1440, height: 1000, fullPage: true },
  { name: 'tablet-home', path: '/', width: 820, height: 1180, fullPage: true },
  { name: 'mobile-home', path: '/', width: 390, height: 844, fullPage: true },
  { name: 'mobile-single', path: '/?name=candy-colors-reframed', width: 390, height: 844, fullPage: true },
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

function stabilizeFixtureDates() {
  wp(
    'eval',
    `
      global $wpdb;
      $updated = $wpdb->update(
        $wpdb->comments,
        array(
          'comment_date'     => '2026-09-19 12:00:00',
          'comment_date_gmt' => '2026-09-19 12:00:00',
        ),
        array( 'comment_author_email' => 'reader@example.test' ),
        array( '%s', '%s' ),
        array( '%s' )
      );
      if ( false === $updated || $updated < 1 ) {
        throw new RuntimeException( 'Could not stabilize showcase comment date.' );
      }
    `,
  );
}

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

  // WordPress assigns the current time when the showcase comment is created.
  // Freeze that database value before browser capture so the canonical single-
  // post screenshots are reproducible byte-for-byte across CI runs.
  stabilizeFixtureDates();

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
      viewport: { width: 1440, height: 720 },
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
    manifest.push({ file: 'desktop-keyboard-overlay.png', route: '/', viewport: '1440x720', feature: 'keyboard image-card overlay' });
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
