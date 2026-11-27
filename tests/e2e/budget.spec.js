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
//
// Every document carries the search that opens over the page as a <template>,
// about 1 kB with its control in the header: the search opens the moment it is
// asked for, and its markup stays a PHP template like the rest. Its script and
// the list it searches are fetched only when a visitor opens it.
const pages = {
    // The home page's assortment: ten pictures of together 53 kB. The test's browser fetches them
    // with the page – without a known connection type Chromium loads lazy pictures up to about
    // 5000 px ahead – where a phone on 4G waits until they are about 1250 px away.
    '/': { document: 14, total: 175, elements: 1500, image: 80, requests: 17 },
    // Twenty cards, each an inline drawing: about 800 elements. On top of what the
    // home page loads come the gallery's own script and the serif face of the samples.
    // Every page carries about 0.3 kB for a header that knows when it is older than
    // the cart (the Back button, a cache in front of the shop) and asks again.
    // The switch between the two areas at the top took the place of the path and the button to the other area.
    '/sticker/': { document: 11.6, total: 138, elements: 900 },
    // The designs: six example pictures of together 68 kB, in the small sizes WordPress keeps of
    // each (a card is half the phone wide). One request per picture on top of the shared ones.
    '/design/': { document: 7.5, total: 195, elements: 260, image: 125, requests: 13 },
    // One design large (51 kB), two more below it, and the small script that hands it to the motif.
    '/design/beispiel-neu-eroeffnet/': { document: 7, total: 175, elements: 200, image: 105, requests: 11 },
    // A page of text from the editor – the legal pages. It carries the styles for
    // editor content in its document and nothing of WooCommerce: no stylesheet, no
    // script, no jQuery (it used to load 180 kB of each).
    '/impressum/': { document: 9, total: 113, elements: 160 },
    // The pages WooCommerce draws, measured with a product in the cart. They bring
    // what its cart and checkout run on: jQuery (30 kB), the script behind the
    // select fields (21 kB), its own scripts and Germanized's. The classic pages,
    // that is – the ones built from blocks weighed 1,5 MB each.
    '/warenkorb/': { withCart: true, document: 10.5, total: 222, elements: 330, stylesheet: 17.5, script: 96, requests: 22 },
    // Mollie adds three small files of its own to the checkout (2 kB): the styles of its card fields
    // and payment icons, and a script for surcharges. With an API key its card fields come on top (W4a).
    '/kasse/': { withCart: true, document: 13.2, total: 234, elements: 520, stylesheet: 18.5, script: 105, requests: 28 },
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

    for (const [path, own] of Object.entries(pages)) {
        const limits = { ...shared, ...own };

        test(`${path} stays within its budget`, async ({ page }) => {
            if (limits.withCart) {
                await page.goto('/produkt/runde-sticker/');
                await page.getByRole('button', { name: 'In den Warenkorb' }).click();
                await expect(page.getByText('wurde Ihrem Warenkorb hinzugefügt')).toBeVisible();
                // Whatever the product page is still fetching is not the weight of the next page.
                await page.goto('about:blank');
            }

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
            // The checkout fetches its overview once more after loading; that counts.
            await page.waitForTimeout(limits.withCart ? 1500 : 500);

            const responses = (await Promise.all(loaded)).filter(Boolean);
            const sum = (type) => responses.filter((response) => response.type === type).reduce((total, response) => total + response.size, 0);
            const total = responses.reduce((sum, response) => sum + response.size, 0);

            expect(responses.length, 'number of requests').toBeLessThanOrEqual(limits.requests);

            expect(sum('document') / KB, 'HTML document (kB)').toBeLessThanOrEqual(limits.document);
            expect(sum('stylesheet') / KB, 'stylesheets (kB)').toBeLessThanOrEqual(limits.stylesheet);
            expect(sum('script') / KB, 'scripts (kB)').toBeLessThanOrEqual(limits.script);
            expect(sum('font') / KB, 'fonts (kB)').toBeLessThanOrEqual(limits.font);
            expect(sum('image') / KB, 'images (kB)').toBeLessThanOrEqual(limits.image);
            expect(total / KB, 'everything (kB)').toBeLessThanOrEqual(limits.total);

            expect(await page.evaluate(() => document.querySelectorAll('*').length), 'elements in the page').toBeLessThanOrEqual(limits.elements);
            expect(await page.evaluate(() => window.layoutShift), 'cumulative layout shift').toBeLessThanOrEqual(limits.layoutShift);

            test.info().annotations.push({
                type: 'weight',
                description: `${path}: ${(total / KB).toFixed(1)} kB in ${responses.length} requests `
                    + `(document ${(sum('document') / KB).toFixed(1)}, css ${(sum('stylesheet') / KB).toFixed(1)}, js ${(sum('script') / KB).toFixed(1)}, `
                    + `fonts ${(sum('font') / KB).toFixed(1)}, images ${(sum('image') / KB).toFixed(1)})`,
            });
        });
    }
});
