import { test, expect, accessibilityViolations, cli, logInAsAdministrator } from './fixtures.js';

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
    for (const path of ['/', '/sticker/', '/flyer/', '/?s=folie']) {
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

    for (const path of ['/', '/sticker/', '/warenkorb/', '/mein-konto/']) {
        test(`${path} announces the shop in the theme's own words and look`, async ({ page, baseURL }) => {
            await page.goto(path);

            // WooCommerce decides that the announcement is shown; the theme says what it says.
            await expect(page.locator('meta[name="woo-coming-soon-page"]')).toBeAttached();
            await expect(page.getByRole('heading', { level: 1 })).toHaveText('Stiqon öffnet bald.');
            await expect(page.getByRole('img', { name: 'Stiqon' })).toBeVisible();
            expect(await stylesheets(page)).toContain('stiqon-css');

            // In the middle of the page, not in its top left corner.
            const heading = await page.getByRole('heading', { level: 1 }).boundingBox();
            const { width, height } = page.viewportSize();

            expect(Math.abs(heading.x + heading.width / 2 - width / 2)).toBeLessThan(width * 0.1);
            expect(heading.y).toBeGreaterThan(height * 0.2);

            // Nothing of the shop, and nothing of WooCommerce's stand-in page: no log-in
            // button, no icons of networks the shop is not on. Every link stays on the site.
            await expect(page.locator('[data-card], [data-mockup], [data-cart-count]')).toHaveCount(0);
            await expect(page.getByRole('link', { name: /Anmelden|Log in/ })).toHaveCount(0);

            const links = await page.getByRole('link').evaluateAll((all) => all.map((link) => link.href));

            expect(links.filter((href) => !href.startsWith(baseURL))).toEqual([]);
            expect(await accessibilityViolations(page)).toEqual([]);
            expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBe(0);
        });
    }

    test('who runs the site and how it treats data stays readable', async ({ page }) => {
        await page.goto('/');

        for (const [name, heading] of [['Impressum', 'Impressum'], ['Datenschutzerklärung', 'Datenschutzerklärung']]) {
            await page.getByRole('link', { name }).click();
            await expect(page.getByRole('heading', { level: 1 })).toHaveText(heading);
            await expect(page.locator('meta[name="woo-coming-soon-page"]')).toHaveCount(0);
            await page.goBack();
        }

        // The other legal pages belong to a shop that is not open yet.
        await page.goto('/agb/');
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Stiqon öffnet bald.');
    });

    test('search tells visitors nothing the announcement does not', async ({ request }) => {
        const response = await request.get('/wp-json/stiqon/v1/suchindex');

        expect(response.status()).toBe(200);
        expect(await response.json()).toEqual({ entries: [] });
        // Who asks decides the answer, so it must not be kept for the next one who asks.
        expect(response.headers()['cache-control']).toBe('no-store');
        expect(response.headers().etag).toBeUndefined();
    });

    test('whoever runs the shop sees it, and can search it', async ({ page }) => {
        await logInAsAdministrator(page);
        await page.goto('/');
        await expect(page.getByRole('heading', { level: 1, name: 'Werbemittel, die haften bleiben.' })).toBeVisible();

        await page.locator('header [data-search-open]').click();
        await expect(page.getByRole('dialog', { name: 'Suche' }).getByRole('option')).toHaveCount(5);

        await page.goto('/?s=kraft');
        await expect(page.locator('[data-card]')).toHaveCount(5);
    });
});

test.describe('"coming soon" for the shop pages only', () => {
    test.beforeAll(() => {
        set('woocommerce_coming_soon', 'yes');
        set('woocommerce_store_pages_only', 'yes');
    });

    test.afterAll(() => {
        set('woocommerce_coming_soon', 'no');
    });

    test('the home page is the shop\'s own, the pages of the shop announce it inside the theme', async ({ page }) => {
        await page.goto('/');
        await expect(page.getByRole('heading', { level: 1, name: 'Werbemittel, die haften bleiben.' })).toBeVisible();

        // Products are shown on the area pages, so they count as pages of the shop –
        // like the product, the cart and the checkout, which WooCommerce knows as such.
        for (const path of ['/sticker/', '/flyer/', '/produkt/runde-sticker/', '/warenkorb/']) {
            await page.goto(path);
            await expect(page.getByRole('heading', { level: 1 }), path).toHaveText('Der Shop öffnet bald.');
            await expect(page.locator('header nav[aria-label="Hauptnavigation"]').first(), path).toBeAttached();
            await expect(page.locator('footer nav[aria-labelledby="footer-legal"]'), path).toBeAttached();
            await expect(page.getByRole('button', { name: 'In den Warenkorb' }), path).toHaveCount(0);
            await expect(page.locator('main [data-mockup], main [data-card]'), path).toHaveCount(0);
            await expect(page.getByRole('main'), path).not.toContainText('€');
        }

        expect(await accessibilityViolations(page)).toEqual([]);

        // Pages that sell nothing stay what they are.
        await page.goto('/impressum/');
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Impressum');
    });

    test('search finds nothing for visitors while the products are not on show', async ({ page }) => {
        await page.goto('/?s=sticker');
        await expect(page.getByRole('main')).toContainText('Zu „sticker“ haben wir nichts gefunden.');
        await expect(page.locator('[data-card]')).toHaveCount(0);

        await page.locator('header [data-search-open]').click();

        const field = page.getByRole('dialog', { name: 'Suche' }).getByRole('combobox');

        await field.fill('sticker');
        await expect(page.getByRole('dialog', { name: 'Suche' })).toContainText('Dazu haben wir nichts gefunden.');
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
