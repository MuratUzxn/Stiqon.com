import { test, expect, cli } from './fixtures.js';

test.describe('without the plugin', () => {
    // The shop's product views come from the plugin "Stiqon Shop-Funktionen". If it is
    // switched off – by mistake, or during an update – the pages must still come up.
    test.beforeAll(() => {
        cli('plugin', 'deactivate', 'stiqon-shop');
    });

    test.afterAll(() => {
        cli('plugin', 'activate', 'stiqon-shop');
    });

    for (const path of ['/', '/sticker/', '/flyer/']) {
        test(`${path} still renders, just without the product views`, async ({ page }) => {
            const response = await page.goto(path);

            expect(response.status()).toBe(200);
            await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
            await expect(page.getByRole('navigation', { name: 'Bereiche' })).toBeAttached();
            await expect(page.locator('[data-mockup]')).toHaveCount(0);
        });
    }
});

test.describe('with the plugin back on', () => {
    test('the product views are there again', async ({ page }) => {
        await page.goto('/sticker/');

        await expect(page.locator('[data-mockup]')).toHaveCount(8);
    });
});
