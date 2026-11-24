import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { gzipSync } from 'node:zlib';
import { test, expect } from './fixtures.js';

/**
 * Weight limits for the first load on a phone.
 *
 * The test server sends everything uncompressed, so each response is
 * compressed here the way a production web server would (gzip); the limits
 * are transfer sizes in kB. They sit a little above today's values: reaching
 * one is a prompt to look at what grew, not a law.
 *
 * The Livewire/Alpine runtime is counted separately from the published
 * production file, because the test server delivers the unminified build.
 */
const KB = 1024;

const pages = {
    '/': { document: 25, total: 130, elements: 1500 },
    '/sticker': { document: 15, total: 120, elements: 600 },
};

// Images: the two logo versions (header and footer), kept lossless on purpose – a lossy
// encoding saved a third of the bytes but left visible fringes on the letter edges.
const shared = { stylesheet: 12, script: 5, font: 60, image: 45, requests: 10, layoutShift: 0.05 };

const runtimeFile = fileURLToPath(new URL('../../public/vendor/livewire/livewire.csp.min.js', import.meta.url));

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

            await page.goto(path, { waitUntil: 'networkidle' });
            await page.evaluate(() => document.fonts.ready);

            const responses = (await Promise.all(loaded)).filter(Boolean);
            const runtime = responses.filter((response) => response.url.includes('/vendor/livewire/'));
            const own = responses.filter((response) => !runtime.includes(response));

            const sum = (type) => own.filter((response) => response.type === type).reduce((total, response) => total + response.size, 0);
            const total = own.reduce((sum, response) => sum + response.size, 0);

            expect(runtime, 'the Livewire runtime is loaded exactly once').toHaveLength(1);
            expect(responses.length, 'number of requests').toBeLessThanOrEqual(shared.requests);

            expect(sum('document') / KB, 'HTML document (kB)').toBeLessThanOrEqual(limits.document);
            expect(sum('stylesheet') / KB, 'stylesheets (kB)').toBeLessThanOrEqual(shared.stylesheet);
            expect(sum('script') / KB, 'own scripts (kB)').toBeLessThanOrEqual(shared.script);
            expect(sum('font') / KB, 'fonts (kB)').toBeLessThanOrEqual(shared.font);
            expect(sum('image') / KB, 'images (kB)').toBeLessThanOrEqual(shared.image);
            expect(total / KB, 'everything except the runtime (kB)').toBeLessThanOrEqual(limits.total);

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

    test('the Livewire runtime served in production stays under 110 kB', () => {
        expect(gzipSync(readFileSync(runtimeFile)).length / KB).toBeLessThanOrEqual(110);
    });
});
