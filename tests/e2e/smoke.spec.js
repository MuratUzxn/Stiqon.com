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
