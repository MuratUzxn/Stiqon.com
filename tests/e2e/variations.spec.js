import { fileURLToPath } from 'node:url';
import { test, expect, cart, cli } from './fixtures.js';

/**
 * Products as a real assortment brings them, and the example assortment does
 * not have: not every combination of options exists; values are ticked that
 * nothing can be bought in; more variations than WooCommerce writes into the
 * page; a product behind a password. See tools/odd-products.php for what
 * exactly is made.
 */

const tool = fileURLToPath(new URL('./tools/odd-products.php', import.meta.url));

/** The price above the options (Germanized keeps an empty element of the same class beside it). */
const price = (page) => page.locator('.summary p.price:not(.wc-gzd-additional-info)');
const net = (page) => page.locator('[data-net-price]');
const note = (page) => page.locator('[data-options-note]');
const shownView = (page) => page.locator('[data-product-stage] [data-view]:not([hidden])');
const option = (page, name) => page.getByRole('radio', { name, exact: true });
const chosen = (page) => page.getByRole('radio', { checked: true }).evaluateAll((radios) => radios.map((radio) => radio.dataset.label));

/** What the page's script gets to search through, asked afresh. */
const searchable = async (request) => (await (await request.get('/wp-json/stiqon/v1/suchindex')).json()).entries;

let ids;

test.beforeAll(() => {
    ids = JSON.parse(cli('eval-file', tool, 'create'));
});

test.afterAll(() => {
    cli('eval-file', tool, 'remove');
});

test.describe('a product whose options do not all go together', () => {
    test('only what can be bought is offered: as pills, drawings, cards and to search', async ({ page, request }) => {
        await page.goto('/produkt/teststicker-mit-luecken/');

        // Holographic foil is ticked on the product, but its only variation has no price.
        await expect(page.getByRole('radio')).toHaveCount(6);
        await expect(page.getByRole('group', { name: 'Material' }).getByRole('radio')).toHaveCount(2);
        await expect(option(page, 'Holografische Folie')).toHaveCount(0);
        await expect(page.locator('[data-product-stage] [data-view]')).toHaveCount(2);

        await page.goto('/sticker/');

        const cards = page.locator('[data-card]').filter({ hasText: 'Teststicker mit Lücken' });

        await expect(cards.locator('[data-subtitle]')).toHaveText(['Weißes Vinyl', 'Transparente Folie']);
        await expect(cards.locator('[data-price]')).toHaveText(['ab 20,90 €', 'ab 25,90 €']);

        const entries = (await searchable(request)).filter((entry) => entry.n === 'Teststicker mit Lücken');

        expect(entries.map((entry) => entry.s)).toEqual(['Sticker', 'Weißes Vinyl', 'Transparente Folie']);
    });

    test('a link to a value that cannot be bought opens the product as it is sold', async ({ page }) => {
        await page.goto('/produkt/teststicker-mit-luecken/?attribute_pa_material=holografisch');

        expect(await chosen(page)).toEqual(['Weißes Vinyl', '5 cm', '100 Stück']);
        await expect(shownView(page)).toHaveAttribute('data-view', 'vinyl');
        await expect(price(page)).toHaveText('20,90 €');
    });

    test('a pill that does not go with the rest of the choice takes the rest along', async ({ page }) => {
        await page.goto('/produkt/teststicker-mit-luecken/');

        expect(await chosen(page)).toEqual(['Weißes Vinyl', '5 cm', '100 Stück']);
        await expect(note(page)).toBeHidden();

        // Transparent foil comes in 7,5 cm only.
        await option(page, 'Transparente Folie').check();
        expect(await chosen(page)).toEqual(['Transparente Folie', '7,5 cm', '100 Stück']);
        await expect(note(page)).toHaveText('Passend dazu gewählt: Größe 7,5 cm.');
        await expect(page.getByRole('status').filter({ hasText: 'Passend dazu gewählt' })).toHaveText('Passend dazu gewählt: Größe 7,5 cm.');
        await expect(shownView(page)).toHaveAttribute('data-view', 'transparent');
        await expect(price(page)).toHaveText('25,90 €');
        await expect(net(page)).toHaveText('21,76 € netto');

        // 250 pieces come on white vinyl only – no pill is out of reach, wherever one stands.
        await option(page, '250 Stück').check();
        expect(await chosen(page)).toEqual(['Weißes Vinyl', '7,5 cm', '250 Stück']);
        await expect(note(page)).toHaveText('Passend dazu gewählt: Material Weißes Vinyl.');
        await expect(shownView(page)).toHaveAttribute('data-view', 'vinyl');
        await expect(price(page)).toHaveText('30,90 €');

        // WooCommerce's own script has followed, and the choice can be bought.
        await expect(page.locator('input.variation_id')).toHaveValue(/^[1-9]\d*$/);
        await page.getByRole('button', { name: 'In den Warenkorb' }).click();
        await expect(page.getByRole('alert').filter({ hasText: 'wurde Ihrem Warenkorb hinzugefügt' })).toBeVisible();
        expect(await cart(page)).toEqual([{ name: 'Teststicker mit Lücken', options: 'Weißes Vinyl, 7,5 cm, 250 Stück', quantity: 1, price: 30.9 }]);
    });

    test('an address that asks for one option opens a combination that exists – also without JavaScript', async ({ browser, baseURL, isMobile }) => {
        const context = await browser.newContext({ javaScriptEnabled: false, baseURL, viewport: isMobile ? { width: 390, height: 844 } : { width: 1440, height: 900 } });
        const page = await context.newPage();

        // The gallery's card for transparent foil leads here.
        await page.goto('/produkt/teststicker-mit-luecken/?attribute_pa_material=transparent');
        expect(await chosen(page)).toEqual(['Transparente Folie', '7,5 cm', '100 Stück']);
        await expect(price(page)).toHaveText('25,90 €');
        await expect(net(page)).toHaveText('21,76 € netto');
        await expect(shownView(page)).toHaveAttribute('data-view', 'transparent');

        await page.getByRole('button', { name: 'In den Warenkorb' }).click();
        await expect(page.getByText('wurde Ihrem Warenkorb hinzugefügt')).toBeVisible();
        expect(await cart(page)).toEqual([{ name: 'Teststicker mit Lücken', options: 'Transparente Folie, 7,5 cm, 100 Stück', quantity: 1, price: 25.9 }]);

        await page.goto('/produkt/teststicker-mit-luecken/?attribute_pa_auflage=250');
        expect(await chosen(page)).toEqual(['Weißes Vinyl', '7,5 cm', '250 Stück']);
        await expect(price(page)).toHaveText('30,90 €');

        await context.close();
    });
});

test.describe('a product with more variations than WooCommerce writes into the page', () => {
    test('price, price without VAT and drawing follow the choice all the same', async ({ page }) => {
        await page.goto('/produkt/teststicker-mit-vielen-varianten/');

        // WooCommerce asks the server for the variation of a choice instead.
        await expect(page.locator('form.variations_form')).toHaveAttribute('data-product_variations', 'false');
        await expect(page.getByRole('radio')).toHaveCount(10);
        await expect(price(page)).toHaveText('20,90 €');
        await expect(net(page)).toHaveText('17,56 € netto');

        await option(page, '250 Stück').check();
        await expect(price(page)).toHaveText('34,90 €');
        await expect(net(page)).toHaveText('29,33 € netto');

        await option(page, 'Holografische Folie').check();
        await expect(price(page)).toHaveText('43,90 €');
        await expect(net(page)).toHaveText('36,89 € netto');
        await expect(shownView(page)).toHaveAttribute('data-view', 'holografisch');

        expect(await chosen(page)).toEqual(['Holografische Folie', '5 cm', '250 Stück']);
        await expect(page.locator('input.variation_id')).toHaveValue(/^[1-9]\d*$/);
    });
});

test.describe('a product behind a password', () => {
    test('is for whoever was given its address: no card, no result, and the page asks for the password', async ({ page, request }) => {
        await page.goto('/sticker/');
        await expect(page.locator('[data-card]').filter({ hasText: 'Teststicker mit Lücken' })).toHaveCount(2);
        await expect(page.locator('[data-card]').filter({ hasText: 'hinter Passwort' })).toHaveCount(0);
        expect(await page.content()).not.toContain('Geheimsache');

        expect((await searchable(request)).filter((entry) => /Passwort/.test(entry.n))).toEqual([]);

        await page.goto('/?s=Teststicker');
        await expect(page.locator('[data-card]').filter({ hasText: 'Teststicker mit Lücken' })).toHaveCount(1);
        await expect(page.getByRole('main')).not.toContainText('hinter Passwort');

        await page.goto('/produkt/teststicker-hinter-passwort/');
        await expect(page.locator('input[name="post_password"]')).toBeVisible();
        await expect(page.getByRole('main')).not.toContainText('11,90');
        await expect(page.getByRole('main')).not.toContainText('Geheimsache');
    });
});

test.describe('a product that leaves the shop', () => {
    test('is gone from search the moment it goes to the bin, and back when it is restored', async ({ page, request }) => {
        const id = String(ids['teststicker-mit-luecken']);
        const listed = async () => (await searchable(request)).filter((entry) => entry.n === 'Teststicker mit Lücken').length;

        expect(await listed()).toBe(3);

        // What "Move to bin" in the list of products does.
        cli('eval', `wp_trash_post(${Number(id)});`);

        try {
            expect(await listed()).toBe(0);

            await page.goto('/?s=Teststicker');
            await expect(page.locator('[data-card]').filter({ hasText: 'Teststicker mit Lücken' })).toHaveCount(0);
        } finally {
            cli('eval', `wp_untrash_post(${Number(id)});`);
        }

        expect(await listed()).toBe(3);
    });
});
