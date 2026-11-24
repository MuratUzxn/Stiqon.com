import { test, expect, fixture, accessibilityViolations } from './fixtures.js';

test.describe('accessibility (axe, WCAG 2.2 AA)', () => {
    for (const path of ['/', '/sticker', '/flyer', '/musterbogen']) {
        test(`${path} has no violations`, async ({ page }) => {
            await page.goto(path);

            expect(await accessibilityViolations(page)).toEqual([]);
        });
    }

    test('the home page with a chosen motif has no violations', async ({ page }) => {
        await page.goto('/');
        await page.locator('input[type="file"]').first().setInputFiles(fixture('motif.png'));
        // The status bar fades in; contrast is only meaningful once it is fully opaque.
        await expect(page.getByRole('status')).toHaveCSS('opacity', '1');

        expect(await accessibilityViolations(page)).toEqual([]);
    });

    test('the open mobile menu has no violations', async ({ page, isMobile }) => {
        test.skip(!isMobile, 'The menu only exists on small screens.');

        await page.goto('/');
        await page.getByRole('button', { name: 'Menü' }).click();
        await expect(page.getByRole('navigation', { name: 'Hauptnavigation' })).toBeVisible();

        expect(await accessibilityViolations(page)).toEqual([]);
    });
});

test.describe('error page', () => {
    test.use({ expectedStatuses: [404] });

    test('an unknown address explains itself inside the shop frame', async ({ page }) => {
        const response = await page.goto('/gibt-es-nicht');

        expect(response.status()).toBe(404);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Diese Seite gibt es nicht.');
        await expect(page.getByRole('main').getByRole('link', { name: 'Zur Startseite' })).toBeVisible();
        expect(await accessibilityViolations(page)).toEqual([]);
    });
});

test.describe('security headers', () => {
    test('shop pages run without unsafe-eval on the published script builds', async ({ page }) => {
        const response = await page.goto('/');
        const policy = response.headers()['content-security-policy'];

        expect(policy).toContain("default-src 'self'");
        expect(policy).toMatch(/script-src 'self' 'nonce-[^']+'(;|$)/);
        expect(policy).not.toContain('unsafe-eval');

        // Alpine came up under that policy: the header component reacted.
        await expect(page.locator('header[x-data="siteHeader"] [aria-controls="mobile-navigation"]')).toHaveAttribute('aria-expanded', 'false');
    });
});

test.describe('layout', () => {
    for (const path of ['/', '/sticker', '/flyer', '/musterbogen']) {
        test(`${path} does not scroll sideways`, async ({ page }) => {
            await page.goto(path);

            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

            expect(overflow).toBe(0);
        });
    }
});
