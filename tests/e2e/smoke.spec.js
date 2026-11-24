import { test, expect } from './fixtures.js';

test.describe('smoke', () => {
    test('the home page renders', async ({ page }) => {
        const response = await page.goto('/');

        expect(response.status()).toBe(200);
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

        // The stylesheet is built and applied (Instrument Sans is self-hosted).
        const fontFamily = await page.evaluate(() => getComputedStyle(document.body).fontFamily);
        expect(fontFamily).toContain('Instrument Sans');
    });

    test('the admin login renders', async ({ page }) => {
        const response = await page.goto('/admin/login');

        expect(response.status()).toBe(200);
        await expect(page.locator('input[type="email"]')).toBeVisible();
        await expect(page.locator('input[type="password"]')).toBeVisible();
    });

    test('the admin area redirects guests to the login', async ({ page }) => {
        await page.goto('/admin');

        await expect(page).toHaveURL(/\/admin\/login$/);
    });
});
