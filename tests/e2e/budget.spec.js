import { gzipSync } from 'node:zlib';
import { test, expect } from './fixtures.js';

/**
 * Weight limits for the first load on a phone.
 *
 * The test server sends everything uncompressed, so each response is
 * compressed here the way a production web server would (gzip); the limits
 * are transfer sizes in kB. They sit a little above today's values: reaching
 * one is a prompt to look at what grew, not a law.
 */
const KB = 1024;

// The document limits are tight on purpose: stylesheets that WordPress puts into
// the page inline (the ones for editor content weigh about 3 kB) add no request,
// so only the size of the document shows them.
const pages = {
    '/': { document: 12.5, total: 130, elements: 1500 },
    // Twenty cards, each an inline drawing: about 800 elements.
    '/sticker/': { document: 9.5, total: 130, elements: 900 },
};

// Scripts: the theme's own script with Alpine bundled in.
// Images: the two logo versions (header and footer), kept lossless on purpose – a lossy
// encoding saved a third of the bytes but left visible fringes on the letter edges.
const shared = { stylesheet: 12, script: 25, font: 60, image: 45, requests: 10, layoutShift: 0.05 };

function transferSize(resourceType, body) {
    // Fonts (woff2) and images (webp, png) are compressed formats already.
    return ['font', 'image'].includes(resourceType) ? body.length : gzipSync(body).length;
}

test.describe('weight of the first load on a phone', () => {
    test.skip(({ isMobile }) => !isMobile, 'The budget applies to the phone viewport.');

    for (const [path, limits] of Object.entries(pages)) {
        test(`${path} stays within its budget`, async ({ page }) => {
            const loaded = [];

            page.on('response', (response) => {
                loaded.push(
                    response.body().then(
                        (body) => ({ type: response.request().resourceType(), url: response.url(), size: transferSize(response.request().resourceType(), body) }),
                        () => null,
                    ),
                );
            });

            // Collect layout shifts from the very start of the page.
            await page.addInitScript(() => {
                window.layoutShift = 0;
                new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        if (!entry.hadRecentInput) {
                            window.layoutShift += entry.value;
                        }
                    }
                }).observe({ type: 'layout-shift', buffered: true });
            });

            await page.goto(path, { waitUntil: 'load' });
            await page.evaluate(() => document.fonts.ready);
            await page.waitForTimeout(500);

            const responses = (await Promise.all(loaded)).filter(Boolean);
            const sum = (type) => responses.filter((response) => response.type === type).reduce((total, response) => total + response.size, 0);
            const total = responses.reduce((sum, response) => sum + response.size, 0);

            expect(responses.length, 'number of requests').toBeLessThanOrEqual(shared.requests);

            expect(sum('document') / KB, 'HTML document (kB)').toBeLessThanOrEqual(limits.document);
            expect(sum('stylesheet') / KB, 'stylesheets (kB)').toBeLessThanOrEqual(shared.stylesheet);
            expect(sum('script') / KB, 'scripts (kB)').toBeLessThanOrEqual(shared.script);
            expect(sum('font') / KB, 'fonts (kB)').toBeLessThanOrEqual(shared.font);
            expect(sum('image') / KB, 'images (kB)').toBeLessThanOrEqual(shared.image);
            expect(total / KB, 'everything (kB)').toBeLessThanOrEqual(limits.total);

            expect(await page.evaluate(() => document.querySelectorAll('*').length), 'elements in the page').toBeLessThanOrEqual(limits.elements);
            expect(await page.evaluate(() => window.layoutShift), 'cumulative layout shift').toBeLessThanOrEqual(shared.layoutShift);

            test.info().annotations.push({
                type: 'weight',
                description: `${path}: ${(total / KB).toFixed(1)} kB in ${responses.length} requests `
                    + `(document ${(sum('document') / KB).toFixed(1)}, css ${(sum('stylesheet') / KB).toFixed(1)}, js ${(sum('script') / KB).toFixed(1)}, `
                    + `fonts ${(sum('font') / KB).toFixed(1)}, images ${(sum('image') / KB).toFixed(1)})`,
            });
        });
    }
});
