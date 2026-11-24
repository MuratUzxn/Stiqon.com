import { test, expect, fixture } from './fixtures.js';

/** hrefs of the visitor's motif on every mockup of the page. */
async function motifSources(page) {
    return page.locator('[data-motif-image]').evaluateAll((images) => images.map((image) => image.getAttribute('href')));
}

test.describe('own motif', () => {
    test('a chosen image appears on every mockup and can be removed again', async ({ page }) => {
        await page.goto('/');

        const card = page.locator('section[aria-labelledby="sortiment"] li').first();
        const sample = card.locator('svg[x-show="idle"]');
        const own = card.locator('[data-motif-image]');

        await expect(sample).toBeVisible();
        await expect(own).toBeHidden();

        // The visible button opens the file dialog of its hidden input.
        const dialog = page.waitForEvent('filechooser');
        await page.getByRole('main').getByRole('button', { name: 'Motiv wählen' }).first().click();
        await (await dialog).setFiles(fixture('motif.png'));

        await expect(own).toBeVisible();
        await expect(sample).toBeHidden();

        const sources = await motifSources(page);

        expect(sources.length).toBeGreaterThan(10);
        expect(sources.every((source) => source?.startsWith('blob:'))).toBe(true);

        // The status bar names the file, and its thumbnail proves the browser could load the blob.
        const status = page.getByRole('status');

        await expect(status).toContainText('motif.png');
        await expect.poll(() => status.locator('img').evaluate((image) => image.naturalWidth)).toBe(800);

        await status.getByRole('button', { name: 'Entfernen' }).click();

        await expect(status).toBeHidden();
        await expect(sample).toBeVisible();
        await expect(own).toBeHidden();
    });

    test('nothing is sent to the server when a motif is chosen', async ({ page }) => {
        await page.goto('/');

        const sent = [];
        page.on('request', (request) => {
            if (!/^(data|blob):/.test(request.url())) {
                sent.push(`${request.method()} ${request.url()}`);
            }
        });

        await page.locator('input[type="file"]').first().setInputFiles(fixture('motif.png'));
        await expect(page.getByRole('status')).toContainText('motif.png');
        await page.waitForTimeout(500);

        expect(sent).toEqual([]);
    });

    test('a file that is not an image is refused with a message', async ({ page }) => {
        await page.goto('/sticker');

        await page.locator('input[type="file"]').first().setInputFiles(fixture('notiz.txt'));

        await expect(page.getByRole('alert')).toHaveText('Bitte ein Bild im Format PNG, JPEG oder WebP wählen.');
        await expect(page.getByRole('status')).toBeHidden();
        expect((await motifSources(page)).every((source) => source === null)).toBe(true);
    });

    test('a refused file leaves an already chosen motif in place', async ({ page }) => {
        await page.goto('/flyer');

        const input = page.locator('input[type="file"]').first();

        await input.setInputFiles(fixture('motif.png'));
        await expect(page.getByRole('status')).toContainText('motif.png');

        await input.setInputFiles(fixture('notiz.txt'));

        await expect(page.getByRole('alert')).toBeVisible();
        await expect(page.getByRole('status')).toContainText('motif.png');
        expect((await motifSources(page)).every((source) => source?.startsWith('blob:'))).toBe(true);
    });
});
