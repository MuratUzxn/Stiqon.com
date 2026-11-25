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

    test('shop pages are served by WooCommerce inside the theme', async ({ page }) => {
        const response = await page.goto('/shop/');

        expect(response.status()).toBe(200);
        await expect(page.locator('main#inhalt')).toBeVisible();
        await expect(page.locator('body')).toHaveClass(/woocommerce/);
    });

    for (const [path, title] of [['/warenkorb/', 'Warenkorb'], ['/kasse/', 'Warenkorb'], ['/mein-konto/', 'Ihr Konto']]) {
        // The theme's own pages leave WooCommerce's styles and scripts out. That
        // must never reach the pages WooCommerce draws. (The checkout sends an
        // empty cart back to the cart page.)
        test(`${path} keeps the styles and scripts of WooCommerce`, async ({ page }) => {
            const response = await page.goto(path);

            expect(response.status()).toBe(200);
            await expect(page.getByRole('heading', { level: 1 })).toHaveText(title);

            const styles = await page.evaluate(() => [...document.querySelectorAll('link[rel="stylesheet"]')].map((link) => link.id));
            const scripts = await page.evaluate(() => [...document.querySelectorAll('script[src]')].map((script) => new URL(script.src).pathname));

            expect(styles).toContain('woocommerce-general-css');
            expect(styles).toContain('stiqon-css');
            expect(scripts.some((script) => script.includes('/plugins/woocommerce/'))).toBe(true);
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
