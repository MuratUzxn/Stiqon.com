/**
 * Takes screenshots of pages of the running application.
 *
 *   node tests/e2e/tools/shots.mjs <output-dir> [--scale=2] [--full] <path> [<path> ...]
 *
 * Expects the server on http://127.0.0.1:8123 (see tests/e2e/serve.sh). Each
 * path is captured at a desktop (1440 px) and a phone (390 px) width.
 */
import { mkdirSync } from 'node:fs';
import { join } from 'node:path';
import { chromium } from '@playwright/test';

const args = process.argv.slice(2);
const outDir = args.shift();
const scale = Number((args.find((a) => a.startsWith('--scale=')) ?? '--scale=1').split('=')[1]);
const fullPage = args.includes('--full');
const paths = args.filter((a) => !a.startsWith('--'));

if (!outDir || paths.length === 0) {
    console.error('Usage: node tests/e2e/tools/shots.mjs <output-dir> [--scale=2] [--full] <path> [...]');
    process.exit(1);
}

mkdirSync(outDir, { recursive: true });

const viewports = [
    { name: 'desktop', width: 1440, height: 900, isMobile: false },
    { name: 'mobile', width: 390, height: 844, isMobile: true },
];

const browser = await chromium.launch();

for (const viewport of viewports) {
    const context = await browser.newContext({
        viewport: { width: viewport.width, height: viewport.height },
        deviceScaleFactor: scale,
        isMobile: viewport.isMobile,
        hasTouch: viewport.isMobile,
        locale: 'de-DE',
        reducedMotion: 'reduce',
    });
    const page = await context.newPage();

    for (const path of paths) {
        await page.goto(`http://127.0.0.1:8123${path}`, { waitUntil: 'networkidle' });
        await page.evaluate(() => document.fonts.ready);
        const slug = path.replace(/^\//, '').replace(/[^a-z0-9]+/gi, '-') || 'home';
        const file = join(outDir, `${slug}-${viewport.name}.png`);
        await page.screenshot({ path: file, fullPage });
        console.log(file);
    }

    await context.close();
}

await browser.close();
