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

test.describe('own motif across pages', () => {
    const chooseOnHome = async (page) => {
        await page.goto('/');
        await page.locator('input[type="file"]').first().setInputFiles(fixture('motif.png'));
        await expect(bar(page)).toContainText('motif.png');
        // Stored, not just shown: the note for the next page is written last.
        await expect.poll(() => page.evaluate(() => window.localStorage.getItem('stiqon:motif'))).not.toBeNull();
    };

    test('it is still there on the next page, on the product and after loading again', async ({ page }) => {
        await chooseOnHome(page);

        await page.goto('/sticker/');
        await expect(bar(page)).toContainText('motif.png');
        expect((await motifSources(page)).every((source) => source?.startsWith('blob:'))).toBe(true);
        expect(await motifSources(page)).toHaveLength(20);

        // On to the product (on a wide screen a click on the card would open the quick view first).
        await page.goto(await page.locator('[data-card]').first().getByRole('link').getAttribute('href'));
        await expect(page.locator('[data-product-stage] [data-view]:not([hidden]) [data-motif-image]')).toHaveAttribute('href', /^blob:/);

        await page.reload();
        await expect(bar(page)).toContainText('motif.png');
        await expect(page.locator('[data-product-stage] [data-view]:not([hidden]) [data-motif-image]')).toBeVisible();
    });

    test('the samples do not flash up before a stored motif appears', async ({ page }) => {
        await chooseOnHome(page);

        // Hold the page's script back, to look at the page before it has run.
        let release;
        const held = new Promise((resolve) => { release = resolve; });

        await page.route('**/assets/build/assets/app-*.js', async (route) => {
            await held;
            await route.continue();
        });

        await page.goto('/flyer/', { waitUntil: 'commit' });

        await expect(page.locator('html')).toHaveAttribute('data-motif', 'pending');
        await expect(page.locator('[data-motif-sample]').first()).toHaveCSS('visibility', 'hidden');

        // Once the script has run, the motif is in place and nothing is held back any more.
        release();
        await expect(bar(page)).toContainText('motif.png');
        await expect(page.locator('html')).not.toHaveAttribute('data-motif', 'pending');
    });

    test('should the script never run, the samples come back by themselves', async ({ page }) => {
        await chooseOnHome(page);

        // The script arrives empty: nothing will ever put the motif in place.
        await page.route('**/assets/build/assets/app-*.js', (route) => route.fulfill({ contentType: 'text/javascript', body: '' }));
        await page.goto('/flyer/', { waitUntil: 'commit' });

        await expect(page.locator('html')).toHaveAttribute('data-motif', 'pending');
        await expect(page.locator('html')).not.toHaveAttribute('data-motif', 'pending', { timeout: 4000 });
        await expect(page.locator('[data-motif-sample]').first()).toHaveCSS('visibility', 'visible');
    });

    test('a note without a stored motif does not hide the samples', async ({ page }) => {
        // What is left when site data is cleared half-way, or a second tab removes the motif.
        await page.addInitScript(() => window.localStorage.setItem('stiqon:motif', '1'));
        await page.goto('/sticker/');

        const sample = page.locator('[data-motif-sample]').first();

        await expect(page.locator('html')).not.toHaveAttribute('data-motif', 'pending');
        await expect(sample).toBeVisible();
        await expect(sample).toHaveCSS('visibility', 'visible');
        await expect(bar(page)).toBeHidden();
    });

    test('removing it removes it from the next page too', async ({ page }) => {
        await chooseOnHome(page);

        await bar(page).getByRole('button', { name: 'Entfernen' }).click();
        await expect(bar(page)).toBeHidden();

        await page.goto('/sticker/');
        await expect(page.locator('[data-motif-sample]').first()).toBeVisible();
        await expect(bar(page)).toBeHidden();
        expect((await motifSources(page)).every((source) => source === null)).toBe(true);
    });

    test('another tab of the shop follows', async ({ page, context }) => {
        const other = await context.newPage();

        await other.goto('/flyer/');
        await chooseOnHome(page);

        await expect(bar(other)).toContainText('motif.png');
        await expect(other.locator('[data-motif-image]').first()).toHaveAttribute('href', /^blob:/);

        await bar(page).getByRole('button', { name: 'Entfernen' }).click();
        await expect(bar(other)).toBeHidden();

        await other.close();
    });

    test('a large image is kept as a copy no larger than the shop shows it', async ({ page }) => {
        await page.goto('/');

        // Drawn in the browser: 3000 × 2000 pixels.
        await page.evaluate(async () => {
            const canvas = Object.assign(document.createElement('canvas'), { width: 3000, height: 2000 });
            const context = canvas.getContext('2d');

            context.fillStyle = '#1f6f5c';
            context.fillRect(0, 0, 3000, 2000);

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));

            await window.Alpine.store('motif').choose(new File([blob], 'gross.png', { type: 'image/png' }));
        });

        await expect(bar(page)).toContainText('gross.png');
        await expect.poll(() => bar(page).locator('img').evaluate((image) => [image.naturalWidth, image.naturalHeight])).toEqual([1600, 1067]);
    });

    test('a file that only claims to be an image is refused', async ({ page }) => {
        await page.goto('/sticker/');
        await page.locator('input[type="file"]').first().setInputFiles(fixture('kein-bild.png'));

        await expect(page.getByRole('alert')).toHaveText('Dieses Bild lässt sich nicht lesen. Bitte eine andere Datei wählen.');
        await expect(bar(page)).toBeHidden();
    });

    test('where the browser stores nothing, the motif shows on the page and the visitor is told', async ({ page }) => {
        // Private windows and blocked site data look like this to a page.
        await page.addInitScript(() => {
            Object.defineProperty(window, 'indexedDB', { value: { open() { throw new DOMException('denied', 'SecurityError'); } } });
        });

        await page.goto('/');
        await page.locator('input[type="file"]').first().setInputFiles(fixture('motif.png'));

        await expect(bar(page)).toContainText('motif.png');
        expect((await motifSources(page)).every((source) => source?.startsWith('blob:'))).toBe(true);

        const told = 'Ihr Motiv ist auf dieser Seite zu sehen. Dieses Fenster erlaubt nicht, es für die nächste Seite zu behalten.';

        await expect(page.getByText(told).last()).toBeVisible();
        await expect(page.getByRole('status')).toContainText(told);

        await page.goto('/sticker/');
        await expect(bar(page)).toBeHidden();
    });
});

