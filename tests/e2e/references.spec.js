import { test, expect, cli, fixture, accessibilityViolations } from './fixtures.js';

/**
 * References: work the shop has delivered. The page and every link to it only
 * exist once there is a reference to show – published, and with its photo.
 */

const cards = (page) => page.locator('li[id^="referenz-"]');
const linksToTheList = (page) => page.locator('a[href$="/referenzen/"]');

test.describe('references, before there are any', () => {
    test.use({ expectedStatuses: [404] });

    test('the page does not exist, and nothing leads to it', async ({ page }) => {
        const response = await page.goto('/referenzen/');

        expect(response.status()).toBe(404);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Diese Seite gibt es nicht.');

        await page.goto('/');
        await expect(linksToTheList(page)).toHaveCount(0);
        await expect(page.getByRole('heading', { name: 'So sieht es bei anderen aus' })).toHaveCount(0);
    });
});

test.describe('references', () => {
    // The last test takes the references away again and asks for the page once more.
    test.use({ expectedStatuses: [404] });

    let withPhoto;
    let withoutPhoto;
    let photo;

    test.beforeAll(() => {
        withPhoto = cli('post', 'create', '--post_type=stiqon_referenz', '--post_title=Café Morgenrot', '--post_excerpt=Runde Sticker auf Kraftpapier für die Becher zum Mitnehmen.', '--post_status=publish', '--porcelain');
        photo = cli('media', 'import', fixture('motif.png'), `--post_id=${withPhoto}`, '--featured_image', '--porcelain');
        withoutPhoto = cli('post', 'create', '--post_type=stiqon_referenz', '--post_title=Yogastudio Lichtung', '--post_excerpt=Flyer mit dem Kursplan für den Herbst.', '--post_status=publish', '--porcelain');
    });

    test.afterAll(() => {
        cli('post', 'delete', withPhoto, photo, withoutPhoto, '--force');
    });

    test('a published reference with its photo is shown; one without waits for its photo', async ({ page }) => {
        const response = await page.goto('/referenzen/');

        expect(response.status()).toBe(200);
        await expect(page).toHaveTitle('Referenzen – Stiqon');
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Referenzen');
        await expect(cards(page)).toHaveCount(1);
        await expect(cards(page).first().getByRole('heading', { level: 3 })).toHaveText('Café Morgenrot');
        await expect(cards(page).first()).toContainText('Runde Sticker auf Kraftpapier für die Becher zum Mitnehmen.');
        await expect(page.getByRole('main')).not.toContainText('Yogastudio Lichtung');

        // The photo arrives, from this shop's own address (the guard on every test sees to that).
        const image = cards(page).first().locator('img');

        await image.scrollIntoViewIfNeeded();
        await expect.poll(() => image.evaluate((element) => element.complete && element.naturalWidth > 0)).toBe(true);
    });

    test('header and footer lead to the list, and the home page shows the newest', async ({ page }) => {
        await page.goto('/');

        // In the menu for small screens, in the footer, and below the cards on this page. The bar
        // of the header names the registers only – the references belong to neither.
        await expect(linksToTheList(page)).toHaveCount(3);
        await expect(page.locator('header nav[aria-label="Hauptnavigation"]').first().locator('a[href$="/referenzen/"]')).toHaveCount(0);
        await expect(page.locator('footer').getByRole('link', { name: 'Referenzen' })).toBeVisible();

        const section = page.locator('section[aria-labelledby="referenzen"]');

        await expect(section.getByRole('heading', { level: 2 })).toHaveText('So sieht es bei anderen aus');
        await expect(section.locator('li[id^="referenz-"]')).toHaveCount(1);
        await section.getByRole('link', { name: 'Alle Referenzen' }).click();
        await expect(page).toHaveURL(/\/referenzen\/$/);
        // The menu marks the list; no register claims it.
        await expect(page.locator('header nav[aria-label="Hauptnavigation"] a[aria-current]')).toHaveText(['Referenzen']);
    });

    test('the address of a single reference leads to the list', async ({ page }) => {
        await page.goto('/referenzen/cafe-morgenrot/');

        await expect(page).toHaveURL(new RegExp(`/referenzen/#referenz-${withPhoto}$`));
        await expect(cards(page)).toHaveCount(1);
    });

    test('a reference that waits for its photo has no address', async ({ page }) => {
        const response = await page.goto('/referenzen/yogastudio-lichtung/');

        expect(response.status()).toBe(404);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Diese Seite gibt es nicht.');
        await expect(page).not.toHaveTitle(/Yogastudio/);
        expect(await page.content()).not.toContain('Yogastudio');
    });

    test('the list passes the checks of the shop\'s own pages', async ({ page }) => {
        await page.goto('/referenzen/');

        expect(await accessibilityViolations(page)).toEqual([]);
        expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBe(0);

        // One of the theme's own pages: no stylesheets for editor content or shop markup.
        const ids = await page.evaluate(() => [...document.querySelectorAll('link[rel="stylesheet"], style[id]')].map((element) => element.id));

        expect(ids).toContain('stiqon-css');
        expect(ids.filter((id) => /block|global-styles|classic-theme|woocommerce|wc-/.test(id))).toEqual([]);
    });

    test('search does not look through references', async ({ page }) => {
        await page.goto('/?s=Morgenrot');

        await expect(page.getByRole('main')).toContainText('Zu „Morgenrot“ haben wir nichts gefunden.');
    });

    test('taken back to a draft, the last reference takes the page with it', async ({ page }) => {
        cli('post', 'update', withPhoto, '--post_status=draft');

        try {
            const response = await page.goto('/referenzen/');

            expect(response.status()).toBe(404);

            await page.goto('/');
            await expect(linksToTheList(page)).toHaveCount(0);

            // Without a list there is nowhere a single reference could lead.
            expect((await page.goto('/referenzen/yogastudio-lichtung/')).status()).toBe(404);
        } finally {
            cli('post', 'update', withPhoto, '--post_status=publish');
        }
    });
});
