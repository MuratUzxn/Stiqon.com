import { test, expect } from './fixtures.js';

test.describe('smoke', () => {
    test('the front page renders in the theme, in German', async ({ page }) => {
        const response = await page.goto('/');

        expect(response.status()).toBe(200);
        await expect(page.locator('html')).toHaveAttribute('lang', /^de/);
        await expect(page.getByRole('link', { name: 'Zum Inhalt springen' })).toBeAttached();
        await expect(page.locator('main#inhalt')).toBeVisible();

        // The built stylesheet is applied (Instrument Sans is bundled with the theme) …
        const fontFamily = await page.evaluate(() => getComputedStyle(document.body).fontFamily);
        expect(fontFamily).toContain('Instrument Sans');

        // … and the built script is running.
        await expect.poll(() => page.evaluate(() => typeof window.Alpine?.version)).toBe('string');
    });

    test('a product page is served by WooCommerce inside the theme', async ({ page }) => {
        const response = await page.goto('/produkt/runde-sticker/');

        expect(response.status()).toBe(200);
        await expect(page.locator('main#inhalt')).toBeVisible();
        await expect(page.locator('body')).toHaveClass(/woocommerce/);
        await expect(page.locator('header nav[aria-label="Hauptnavigation"]').first()).toBeAttached();
    });

    test('WooCommerce\'s own list pages lead to the shop\'s areas', async ({ page }) => {
        // The shop's lists are the area pages; nobody should land on an unstyled archive.
        await page.goto('/produktkategorie/sticker/');
        await expect(page).toHaveURL(/\/sticker\/$/);

        await page.goto('/produktkategorie/flyer/');
        await expect(page).toHaveURL(/\/flyer\/$/);

        await page.goto('/shop/');
        await expect(page).toHaveURL(/127\.0\.0\.1:\d+\/$/);
        await expect(page.getByRole('heading', { level: 1, name: 'Werbemittel, die haften bleiben.' })).toBeVisible();
    });

    for (const [path, title] of [['/warenkorb/', 'Warenkorb'], ['/kasse/', 'Warenkorb'], ['/mein-konto/', 'Ihr Konto']]) {
        // WooCommerce draws these pages inside the theme. Its scripts stay – they
        // make cart and checkout work, and the theme's own pages leaving them out
        // must never reach here. Its stylesheets go: the theme gives the markup
        // its look (assets/src/css/shop.css). (The checkout sends an empty cart
        // back to the cart page.)
        test(`${path} keeps WooCommerce's scripts and gets its look from the theme`, async ({ page }) => {
            const response = await page.goto(path);

            expect(response.status()).toBe(200);
            await expect(page.getByRole('heading', { level: 1 })).toHaveText(title);

            const styles = await page.evaluate(() => [...document.querySelectorAll('link[rel="stylesheet"], style[id]')].map((element) => element.id));
            const scripts = await page.evaluate(() => [...document.querySelectorAll('script[src]')].map((script) => new URL(script.src).pathname));

            expect(styles).toContain('stiqon-css');
            expect(styles).toContain('stiqon-shop-css');
            expect(styles.filter((id) => /^woocommerce-|^wc-|block-library|global-styles|classic-theme/.test(id))).toEqual([]);
            expect(scripts.some((script) => script.includes('/plugins/woocommerce/'))).toBe(true);

            // In the theme's look: its face, and its page frame around WooCommerce's markup.
            expect(await page.evaluate(() => getComputedStyle(document.querySelector('.woocommerce')).fontFamily)).toContain('Instrument Sans');
            await expect(page.locator('header nav[aria-label="Hauptnavigation"]').first()).toBeAttached();
        });
    }

    test('the login page renders', async ({ page }) => {
        const response = await page.goto('/wp-login.php');

        expect(response.status()).toBe(200);
        await expect(page.locator('#user_login')).toBeVisible();
        await expect(page.locator('#user_pass')).toBeVisible();
    });

    test('the admin area sends guests to the login', async ({ page }) => {
        await page.goto('/wp-admin/');

        await expect(page).toHaveURL(/\/wp-login\.php/);
    });
});
