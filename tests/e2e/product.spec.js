import { test, expect, accessibilityViolations, fixture } from './fixtures.js';

/**
 * The product page: the product drawn large, its options as pills, the price of
 * the current choice, the button. It is WooCommerce's page with this theme's
 * templates, so WooCommerce's and Germanized's own parts have to keep working.
 */

/** The price above the options (Germanized keeps an empty element of the same class beside it). */
const price = (page) => page.locator('.summary p.price:not(.wc-gzd-additional-info)');
const net = (page) => page.locator('[data-net-price]');
const shownView = (page) => page.locator('[data-product-stage] [data-view]:not([hidden])');
const option = (page, name) => page.getByRole('radio', { name, exact: true });

/** What is in the visitor's cart, as WooCommerce's own interface reports it. */
async function cart(page) {
    const response = await page.request.get('/wp-json/wc/store/v1/cart');

    return (await response.json()).items.map((item) => ({
        name: item.name,
        options: item.variation.map((entry) => entry.value).join(', '),
        quantity: item.quantity,
        price: Number(item.prices.price) / 100,
    }));
}

test.describe('product page', () => {
    test('a card of the gallery leads to the product with its material chosen', async ({ page, isMobile }) => {
        await page.goto('/sticker/');
        await page.locator('[data-card]').filter({ hasText: 'Runde Sticker' }).filter({ hasText: 'Kraftpapier' }).getByRole('link').click();

        // A wide screen shows the card large first; from there it is one more click.
        if (!isMobile) {
            await page.getByRole('dialog').getByRole('link', { name: 'Produkt ansehen' }).click();
        }

        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Runde Sticker');
        await expect(option(page, 'Kraftpapier')).toBeChecked();
        await expect(shownView(page)).toHaveAttribute('data-view', 'kraftpapier');
        await expect(shownView(page).getByRole('img', { name: 'Runde Sticker, Kraftpapier' })).toBeVisible();

        // The price is the one of this choice, gross and net, with the notes German law asks for.
        await expect(price(page)).toHaveText('26,90 €');
        await expect(net(page)).toHaveText('22,61 € netto');
        await expect(page.locator('.summary')).toContainText('inkl. MwSt.');
        await expect(page.locator('.summary')).toContainText('zzgl. Versandkosten');

        // The way back.
        await expect(page.getByRole('navigation', { name: 'Pfad' }).getByRole('link', { name: 'Sticker' })).toHaveAttribute('href', /\/sticker\/$/);
    });

    test('the drawing and the price follow the choice', async ({ page }) => {
        await page.goto('/produkt/runde-sticker/');

        await expect(option(page, 'Weißes Vinyl')).toBeChecked();
        await expect(price(page)).toHaveText('24,90 €');

        await option(page, 'Holografische Folie').check();
        await expect(shownView(page)).toHaveAttribute('data-view', 'holografisch');
        await expect(shownView(page)).toHaveCount(1);
        await expect(price(page)).toHaveText('34,90 €');
        await expect(net(page)).toHaveText('29,33 € netto');

        await option(page, '7,5 cm').check();
        await option(page, '500 Stück').check();
        await expect(price(page)).toHaveText('119,90 €');
        await expect(net(page)).toHaveText('100,76 € netto');

        // WooCommerce's own script has followed: it knows the variation.
        await expect(page.locator('input.variation_id')).not.toHaveValue('0');
    });

    test('the chosen variation goes into the cart, and loading the page again adds nothing', async ({ page }) => {
        await page.goto('/produkt/runde-sticker/?attribute_pa_material=transparent');

        await option(page, '250 Stück').check();
        await expect(price(page)).toHaveText('44,90 €');
        await page.getByRole('button', { name: 'In den Warenkorb' }).click();

        await expect(page.getByRole('alert').filter({ hasText: 'wurde Ihrem Warenkorb hinzugefügt' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Warenkorb anzeigen' })).toBeVisible();
        expect(await cart(page)).toEqual([{ name: 'Runde Sticker', options: 'Transparente Folie, 5 cm, 250 Stück', quantity: 1, price: 44.9 }]);

        // Back on the product's own address, with the choice still shown.
        await expect(page).toHaveURL(/\/produkt\/runde-sticker\/\?attribute_pa_material=transparent&attribute_pa_groesse=5-cm&attribute_pa_auflage=250$/);
        await expect(option(page, 'Transparente Folie')).toBeChecked();
        await expect(option(page, '250 Stück')).toBeChecked();

        await page.reload();
        expect(await cart(page)).toHaveLength(1);
    });

    test('it works without JavaScript: options, price of the address, cart', async ({ browser, baseURL, isMobile }) => {
        const context = await browser.newContext({ javaScriptEnabled: false, baseURL, viewport: isMobile ? { width: 390, height: 844 } : { width: 1440, height: 900 } });
        const page = await context.newPage();

        await page.goto('/produkt/flyer-din-lang/?attribute_pa_papier=250-g');

        await expect(option(page, '250 g Bilderdruck')).toBeChecked();
        await expect(price(page)).toHaveText('29,90 €');
        await expect(net(page)).toHaveText('25,13 € netto');

        // WooCommerce's script would work out the variation; without it the plugin does.
        await option(page, '1.000 Stück').check();
        await page.getByRole('button', { name: 'In den Warenkorb' }).click();

        await expect(page.getByText('wurde Ihrem Warenkorb hinzugefügt')).toBeVisible();
        await expect(price(page)).toHaveText('84,90 €');
        expect(await cart(page)).toEqual([{ name: 'Flyer DIN lang', options: '250 g Bilderdruck, 1.000 Stück', quantity: 1, price: 84.9 }]);

        await context.close();
    });

    test('an option the product does not have is ignored', async ({ page }) => {
        await page.goto('/produkt/ovale-sticker/?attribute_pa_material=gold&attribute_pa_auflage=%22%3E%3Cscript%3E');

        await expect(option(page, 'Weißes Vinyl')).toBeChecked();
        await expect(option(page, '100 Stück')).toBeChecked();
        await expect(price(page)).toHaveText('24,90 €');
    });

    test('the other products of the area are one click away', async ({ page }) => {
        await page.goto('/produkt/flyer-din-a6/');

        const others = page.getByRole('region', { name: 'Weitere Flyer' }).or(page.locator('section[aria-labelledby="weitere"]'));

        await expect(others.locator('[data-card]')).toHaveCount(4);
        await expect(others.locator('[data-card]').filter({ hasText: 'Flyer DIN A6' })).toHaveCount(0);

        await others.getByRole('link', { name: 'Flyer DIN A5' }).click();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Flyer DIN A5');
    });

    test('the own motif appears on the product and on the other products below', async ({ page }) => {
        await page.goto('/produkt/quadratische-sticker/');
        await page.locator('input[type="file"]').first().setInputFiles(new URL('../fixtures/motif.png', import.meta.url).pathname);

        await expect(page.getByRole('group', { name: 'Ihr Motiv' })).toContainText('motif.png');
        await expect(shownView(page).locator('[data-motif-image]')).toHaveAttribute('href', /^blob:/);
    });

    for (const path of ['/produkt/runde-sticker/', '/produkt/flyer-quadrat/?attribute_pa_papier=250-g']) {
        test(`${path} has no accessibility violations and does not scroll sideways`, async ({ page }) => {
            await page.goto(path);

            expect(await accessibilityViolations(page)).toEqual([]);
            expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBe(0);
        });
    }

    test('the bar that reports the own motif keeps clear of the cart button', async ({ page, isMobile }) => {
        test.skip(isMobile, 'On a phone the page is one column, and the bar passes over it while scrolling.');

        // At these heights the button sits where the bar would be, were it in the middle of the screen.
        for (const height of [900, 860, 940]) {
            await page.setViewportSize({ width: 1440, height });
            await page.goto('/produkt/runde-sticker/');
            await page.locator('input[type="file"]').first().setInputFiles(fixture('motif.png'));

            const bar = page.getByRole('group', { name: 'Ihr Motiv' });

            await expect(bar).toHaveCSS('opacity', '1');

            const [barBox, buttonBox] = [await bar.boundingBox(), await page.getByRole('button', { name: 'In den Warenkorb' }).boundingBox()];
            const apart = barBox.x + barBox.width <= buttonBox.x || buttonBox.x + buttonBox.width <= barBox.x
                || barBox.y + barBox.height <= buttonBox.y || buttonBox.y + buttonBox.height <= barBox.y;

            expect(apart, `at ${height} px the bar (${JSON.stringify(barBox)}) lies on the button (${JSON.stringify(buttonBox)})`).toBe(true);
            // It stands under the drawing, in the drawing's column.
            const stage = await page.locator('[data-product-stage]').boundingBox();

            expect(barBox.x).toBeGreaterThanOrEqual(stage.x);
            expect(barBox.x + barBox.width).toBeLessThanOrEqual(stage.x + stage.width);
        }
    });

    test('the options can be chosen with the keyboard', async ({ page, isMobile }) => {
        test.skip(isMobile, 'Keyboard navigation is checked on the desktop viewport.');

        await page.goto('/produkt/runde-sticker/');

        await option(page, 'Weißes Vinyl').focus();
        await page.keyboard.press('ArrowRight');

        await expect(option(page, 'Transparente Folie')).toBeChecked();
        await expect(option(page, 'Transparente Folie')).toBeFocused();
        await expect(shownView(page)).toHaveAttribute('data-view', 'transparent');
        await expect(price(page)).toHaveText('27,90 €');
    });
});
