import { test, expect, cli } from './fixtures.js';

/**
 * The theme's own pages leave out stylesheets and scripts they do not use. Some
 * settings in WooCommerce change what a page needs – and on the very same
 * addresses. Each of these was broken once, unnoticed, because the test site
 * has them switched off.
 */

/** Ids of the stylesheets a page carries, linked or inline. */
const stylesheets = (page) => page.evaluate(() => [...document.querySelectorAll('link[rel="stylesheet"], style[id]')].map((element) => element.id));

const set = (option, value) => cli('option', 'update', option, value);

test.describe('the theme\'s own pages', () => {
    for (const path of ['/', '/sticker/', '/flyer/']) {
        test(`${path} carries the theme's stylesheet and none for editor content or shop markup`, async ({ page }) => {
            await page.goto(path);

            const ids = await stylesheets(page);

            expect(ids).toContain('stiqon-css');
            expect(ids.filter((id) => /block|global-styles|classic-theme|woocommerce|wc-/.test(id))).toEqual([]);
        });
    }
});

test.describe('"coming soon" for the whole site', () => {
    test.beforeAll(() => {
        set('woocommerce_coming_soon', 'yes');
        set('woocommerce_store_pages_only', 'no');
    });

    test.afterAll(() => {
        set('woocommerce_coming_soon', 'no');
        set('woocommerce_store_pages_only', 'yes');
    });

    for (const path of ['/', '/sticker/']) {
        test(`${path} shows WooCommerce's page with the styles it is built from`, async ({ page }) => {
            await page.goto(path);

            // WooCommerce draws this page from blocks, on the addresses of the theme's own pages.
            await expect(page.locator('meta[name="woo-coming-soon-page"]')).toBeAttached();
            await expect(page.getByRole('heading', { name: /demnächst verfügbar/ })).toBeVisible();

            const ids = await stylesheets(page);

            expect(ids).toContain('wp-block-library-inline-css');
            expect(ids).toContain('global-styles-inline-css');

            // Without the block styles everything sits in the top left corner.
            const heading = await page.getByRole('heading', { name: /demnächst verfügbar/ }).boundingBox();
            const width = page.viewportSize().width;

            expect(Math.abs(heading.x + heading.width / 2 - width / 2)).toBeLessThan(width * 0.1);
        });
    }
});

test.describe('"coming soon" for the shop pages only', () => {
    test.beforeAll(() => {
        set('woocommerce_coming_soon', 'yes');
        set('woocommerce_store_pages_only', 'yes');
    });

    test.afterAll(() => {
        set('woocommerce_coming_soon', 'no');
    });

    test('the home page is the shop\'s own, the shop page announces itself inside the theme', async ({ page }) => {
        await page.goto('/');
        await expect(page.getByRole('heading', { level: 1, name: 'Werbemittel, die haften bleiben.' })).toBeVisible();

        await page.goto('/shop/');
        await expect(page.locator('header nav[aria-label="Hauptnavigation"]').first()).toBeAttached();
        await expect(page.locator('[data-mockup]')).toHaveCount(0);
        expect(await stylesheets(page)).toContain('wp-block-library-inline-css');
    });
});

test.describe('store notice', () => {
    test.beforeAll(() => {
        set('woocommerce_demo_store', 'yes');
        set('woocommerce_demo_store_notice', 'Betriebsferien bis 6. Januar.');
    });

    test.afterAll(() => {
        set('woocommerce_demo_store', 'no');
        cli('option', 'delete', 'woocommerce_demo_store_notice');
    });

    for (const path of ['/', '/sticker/']) {
        // WooCommerce prints the notice hidden on every page; its own script reveals it.
        test(`${path} shows it`, async ({ page }) => {
            await page.goto(path);

            await expect(page.getByText('Betriebsferien bis 6. Januar.')).toBeVisible();
        });
    }
});

test.describe('order attribution switched on by the owner', () => {
    test.beforeAll(() => {
        set('woocommerce_feature_order_attribution_enabled', 'yes');
    });

    test.afterAll(() => {
        set('woocommerce_feature_order_attribution_enabled', 'no');
    });

    test('its scripts run on the page a visitor arrives on', async ({ page, context }) => {
        const scripts = [];
        page.on('request', (request) => {
            if (request.resourceType() === 'script') {
                scripts.push(new URL(request.url()).pathname);
            }
        });

        await page.goto('/?utm_source=test');

        // Off by default for privacy. Once the owner decides otherwise, it has to see the landing page.
        await expect.poll(() => scripts.some((script) => script.includes('sourcebuster'))).toBe(true);
        await expect.poll(() => scripts.some((script) => script.includes('order-attribution'))).toBe(true);
        await expect.poll(async () => (await context.cookies()).some((cookie) => cookie.name.startsWith('sbjs_'))).toBe(true);
    });
});

test.describe('icons', () => {
    test('/favicon.ico leads to the shop\'s icon', async ({ page }) => {
        // Browsers ask for this address on pages that name no icon: the login page, the administration.
        const response = await page.request.get('/favicon.ico');

        expect(response.status()).toBe(200);
        expect(response.url()).toMatch(/\/wp-content\/themes\/stiqon\/assets\/brand\/favicon\.ico$/);
        expect((await response.body()).length).toBeGreaterThan(1000);
    });
});
