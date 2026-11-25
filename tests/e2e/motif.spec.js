import { test, expect, fixture } from './fixtures.js';

/** hrefs of the visitor's motif on every mockup of the page. */
async function motifSources(page) {
    const images = page.locator('[data-motif-image]');

    // "Every mockup shows it" says nothing on a page without mockups.
    expect(await images.count()).toBeGreaterThan(0);

    return images.evaluateAll((nodes) => nodes.map((node) => node.getAttribute('href')));
}

/** The bar at the bottom of the screen that names the chosen motif. */
const bar = (page) => page.getByRole('group', { name: 'Ihr Motiv' });

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

        // The bar names the file, and its thumbnail proves the browser could load the blob.
        await expect(bar(page)).toContainText('motif.png');
        await expect.poll(() => bar(page).locator('img').evaluate((image) => image.naturalWidth)).toBe(800);

        await bar(page).getByRole('button', { name: 'Entfernen' }).click();

        await expect(bar(page)).toBeHidden();
        await expect(sample).toBeVisible();
        await expect(own).toBeHidden();
    });

    test('what happens is said to screen readers in a region that is there from the start', async ({ page }) => {
        await page.goto('/');

        // A live region that only appears together with its text is often not read out.
        const status = page.getByRole('status');
        const alert = page.getByRole('alert');

        await expect(status).toBeAttached();
        await expect(status).toBeEmpty();
        await expect(alert).toBeAttached();
        await expect(alert).toBeEmpty();
        expect(await status.evaluate((element) => getComputedStyle(element).display)).not.toBe('none');
        expect(await alert.evaluate((element) => getComputedStyle(element).display)).not.toBe('none');

        const input = page.locator('input[type="file"]').first();

        await input.setInputFiles(fixture('motif.png'));
        await expect(status).toHaveText('Ihr Motiv „motif.png“ ist jetzt auf allen Ansichten zu sehen.');

        await bar(page).getByRole('button', { name: 'Entfernen' }).click();
        await expect(status).toHaveText('Ihr Motiv wurde entfernt. Die Ansichten zeigen wieder Beispiele.');
    });

    test('removing the motif with the keyboard returns the focus to where it was chosen', async ({ page, isMobile }) => {
        test.skip(isMobile, 'Keyboard navigation is checked on the desktop viewport.');

        await page.goto('/');

        const chooser = page.getByRole('main').getByRole('button', { name: 'Motiv wählen' }).first();
        const dialog = page.waitForEvent('filechooser');

        await chooser.click();
        await (await dialog).setFiles(fixture('motif.png'));

        const remove = bar(page).getByRole('button', { name: 'Entfernen' });

        // Reach the button the way a keyboard does.
        await remove.focus();
        await page.keyboard.press('Shift+Tab');
        await page.keyboard.press('Tab');
        await expect(remove).toBeFocused();

        await page.keyboard.press('Enter');

        // The bar is gone – and the focus with it, if nothing takes care.
        await expect(bar(page)).toBeHidden();
        await expect(chooser).toBeFocused();
    });

    test('nothing is sent to the server when a motif is chosen', async ({ page }) => {
        await page.goto('/');

        // Pages keep fetching their own files (an image further down that loads late, for
        // example). What must not happen: a request that carries data, or one that names the file.
        const sent = [];
        page.on('request', (request) => {
            if (/^(data|blob):/.test(request.url())) {
                return;
            }

            if (request.method() !== 'GET' || request.postData() !== null || request.url().includes('motif')) {
                sent.push(`${request.method()} ${request.url()}`);
            }
        });

        await page.locator('input[type="file"]').first().setInputFiles(fixture('motif.png'));
        await expect(bar(page)).toContainText('motif.png');
        await page.waitForTimeout(500);

        expect(sent).toEqual([]);
    });

    test('a file that is not an image is refused with a message that stays until it is closed', async ({ page }) => {
        await page.goto('/sticker/');

        await page.locator('input[type="file"]').first().setInputFiles(fixture('notiz.txt'));

        const message = 'Bitte ein Bild im Format PNG, JPEG oder WebP wählen.';

        await expect(page.getByRole('alert')).toHaveText(message);
        await expect(page.getByText(message).last()).toBeVisible();
        await expect(bar(page)).toBeHidden();
        expect((await motifSources(page)).every((source) => source === null)).toBe(true);

        // It used to vanish after six seconds, read or not.
        await page.waitForTimeout(6500);
        await expect(page.getByText(message).last()).toBeVisible();

        await page.getByRole('button', { name: 'Meldung schließen' }).click();
        await expect(page.getByRole('alert')).toBeEmpty();
        await expect(page.getByRole('button', { name: 'Meldung schließen' })).toBeHidden();
    });

    test('the same mistake twice is said twice', async ({ page }) => {
        await page.goto('/sticker/');

        const input = page.locator('input[type="file"]').first();
        const alert = page.getByRole('alert');

        await input.setInputFiles(fixture('notiz.txt'));
        await expect(alert).not.toBeEmpty();

        // Unchanged text is not read out again, so the message is taken away and set anew.
        // The observer is in place before the second attempt starts.
        await alert.evaluate((element) => {
            window.alertTexts = [];
            new MutationObserver(() => window.alertTexts.push(element.textContent)).observe(element, { childList: true, characterData: true, subtree: true });
        });

        await input.setInputFiles(fixture('notiz.txt'));

        await expect.poll(() => page.evaluate(() => window.alertTexts)).toEqual(['', 'Bitte ein Bild im Format PNG, JPEG oder WebP wählen.']);
    });

    test('a refused file leaves an already chosen motif in place', async ({ page }) => {
        await page.goto('/flyer/');

        const input = page.locator('input[type="file"]').first();

        await input.setInputFiles(fixture('motif.png'));
        await expect(bar(page)).toContainText('motif.png');

        await input.setInputFiles(fixture('notiz.txt'));

        await expect(page.getByRole('alert')).not.toBeEmpty();
        await expect(bar(page)).toContainText('motif.png');
        expect((await motifSources(page)).every((source) => source?.startsWith('blob:'))).toBe(true);
    });
});
