import { test, expect, accessibilityViolations } from './fixtures.js';

/**
 * The shop's areas beyond stickers and flyers: every area page shows its
 * products, each card drawn, and a product of each new kind can be chosen,
 * drawn as chosen and put into the cart. The assortment is the example one
 * (tools/dev/example-assortment.php).
 */

// slug => [heading, a product, the kind of drawing it is]
const areas = {
    sticker: ['Sticker', 'Runde Sticker', 'sticker'],
    flyer: ['Flyer', 'Faltblatt mit Wickelfalz', 'leaflet'],
    visitenkarten: ['Visitenkarten', 'Visitenkarten', 'card'],
    karten: ['Karten', 'Klappkarten', 'card'],
    briefpapier: ['Briefpapier', 'Briefumschläge DIN lang', 'envelope'],
    broschueren: ['Broschüren', 'Broschüren mit Klebebindung', 'brochure'],
    plakate: ['Plakate', 'Plakate', 'poster'],
    kalender: ['Kalender', 'Wandkalender', 'calendar'],
    werbetechnik: ['Werbetechnik', 'Roll-ups', 'rollup'],
    verpackungen: ['Verpackungen', 'Versandkartons', 'box'],
    textilien: ['Textilien', 'T-Shirts', 'shirt'],
    werbeartikel: ['Werbeartikel', 'Tassen', 'mug'],
};

const cards = (page) => page.locator('[data-cards] [data-card]');

test.describe('every area of the shop', () => {
    for (const [slug, [heading, product, kind]] of Object.entries(areas)) {
        test(`/${slug}/ shows its products, every card drawn`, async ({ page }) => {
            await page.goto(`/${slug}/`);

            await expect(page.getByRole('heading', { level: 1 })).toHaveText(heading);
            await expect(page.getByRole('navigation', { name: 'Pfad' }).getByRole('link', { name: 'Start' })).toBeVisible();
            expect(await cards(page).count()).toBeGreaterThan(1);
            // No card without its drawing, and the drawing is of the product's kind.
            await expect(cards(page).filter({ hasNot: page.locator('svg[data-mockup]') })).toHaveCount(0);
            await expect(cards(page).filter({ hasText: product }).first().locator(`svg[data-mockup="${kind}"]`)).toBeVisible();
            // Prices are examples until the supplier's arrive, and the page says so.
            await expect(page.getByText('Beispielsortiment mit Beispielpreisen.')).toBeVisible();
        });
    }

    test('a new area passes the same checks as the others', async ({ page }) => {
        for (const slug of ['visitenkarten', 'werbetechnik', 'textilien']) {
            await page.goto(`/${slug}/`);
            expect(await accessibilityViolations(page), slug).toEqual([]);
        }
    });
});

test.describe('products of the new kinds', () => {
    test('a shirt is drawn in the colour chosen, and goes into the cart with colour, size and quantity', async ({ page }) => {
        await page.goto('/produkt/t-shirts/?attribute_pa_farbe=schwarz&attribute_pa_textilgroesse=l');

        await expect(page.getByRole('heading', { level: 1 })).toHaveText('T-Shirts');
        await expect(page.locator('main svg[data-mockup="shirt"]').first()).toBeVisible();

        await page.getByRole('button', { name: 'In den Warenkorb' }).click();
        await expect(page.getByText('wurde Ihrem Warenkorb hinzugefügt')).toBeVisible();

        await page.goto('/warenkorb/');

        const row = page.locator('.woocommerce-cart-form .cart_item').filter({ hasText: 'T-Shirts' });

        await expect(row.locator('.product-name')).toContainText('Schwarz');
        await expect(row.locator('.product-name')).toContainText('L');
        await expect(row.locator('.product-name')).toContainText('10 Stück');
        await expect(row.locator('.product-thumbnail svg[data-mockup="shirt"]')).toBeVisible();
    });

    test('business cards switch their drawing with the paper, and a search finds them', async ({ page }) => {
        await page.goto('/produkt/visitenkarten/');

        await expect(page.locator('main svg[data-mockup="card"]').first()).toBeVisible();
        await page.getByRole('radio', { name: '300 g Bilderdruck glänzend' }).check();
        await expect(page).toHaveURL(/attribute_pa_papier=300-g-glaenzend/);

        await page.goto('/?s=visitenkarte');
        await expect(page.locator('[data-card]').filter({ hasText: 'Visitenkarten mit Goldprägung' }).first()).toBeVisible();
    });
});
