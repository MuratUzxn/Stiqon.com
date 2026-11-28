import { test, expect } from './fixtures.js';

/**
 * The area pages show the assortment: one card per product and per option that
 * can be seen (a sticker's material, a flyer's paper), with filters above.
 * The test site carries the example assortment (tools/dev/example-catalogue.php).
 */

const cards = (page) => page.locator('[data-card]');
const shown = (page) => page.locator('[data-card]:not([hidden])');
// A product's chip and its cards carry the same name; the chips are the links with a filter.
const chip = (page, name) => page.locator('a[data-filter]').getByText(name, { exact: true });

test.describe('gallery', () => {
    test('every product is shown once per material, drawn in that material', async ({ page }) => {
        await page.goto('/sticker/');

        // Five shapes on four materials.
        await expect(cards(page)).toHaveCount(20);
        await expect(shown(page)).toHaveCount(20);
        await expect(page.locator('[data-count]')).toHaveText('20 Ausführungen');

        const card = cards(page).filter({ hasText: 'Runde Sticker' }).filter({ hasText: 'Kraftpapier' });

        await expect(card).toHaveCount(1);
        await expect(card.getByRole('img', { name: 'Runde Sticker, Kraftpapier' })).toBeVisible();
        await expect(card).toContainText('ab 26,90 €');

        // The card opens the product with that material chosen.
        await expect(card.getByRole('link')).toHaveAttribute('href', /\/produkt\/runde-sticker\/\?attribute_pa_material=kraftpapier$/);
        await expect(card.getByRole('link')).toHaveText('Runde Sticker');
    });

    test('flyers and folded leaflets are shown once per paper', async ({ page }) => {
        await page.goto('/flyer/');

        // Five flyers and two folded leaflets, each in 135 g and in 250 g.
        await expect(cards(page)).toHaveCount(14);
        await expect(page.getByRole('group', { name: 'Nach Papier filtern' }).getByRole('link')).toHaveText(['Jedes Papier', '135 g Bilderdruck', '250 g Bilderdruck']);
        await expect(cards(page).filter({ hasText: 'Flyer DIN lang' }).filter({ hasText: '250 g' })).toContainText('ab 29,90 €');
    });

    test('a click on a filter narrows the cards in place and the address follows', async ({ page }) => {
        await page.goto('/sticker/');

        let loads = 0;
        page.on('load', () => { loads += 1; });

        await chip(page, 'Ovale Sticker').click();

        await expect(shown(page)).toHaveCount(4);
        await expect(page.locator('[data-count]')).toHaveText('4 von 20 Ausführungen');
        await expect(page).toHaveURL(/\/sticker\/\?produkt=ovale-sticker$/);
        await expect(chip(page, 'Ovale Sticker')).toHaveAttribute('aria-current', 'true');
        await expect(chip(page, 'Alle')).toHaveAttribute('aria-current', 'false');

        await chip(page, 'Holografische Folie').click();

        await expect(shown(page)).toHaveCount(1);
        await expect(shown(page)).toContainText('Ovale Sticker');
        await expect(shown(page)).toContainText('Holografische Folie');
        await expect(page).toHaveURL(/\/sticker\/\?produkt=ovale-sticker&material=holografisch$/);

        // Each chip leads to the choice it stands for, keeping the other filter.
        await expect(chip(page, 'Runde Sticker')).toHaveAttribute('href', /\?produkt=runde-sticker&material=holografisch$/);
        await expect(chip(page, 'Alle')).toHaveAttribute('href', /\/sticker\/\?material=holografisch$/);

        await chip(page, 'Alle').click();
        await expect(shown(page)).toHaveCount(5);
        await chip(page, 'Jedes Material').click();
        await expect(shown(page)).toHaveCount(20);
        await expect(page).toHaveURL(/\/sticker\/$/);

        expect(loads).toBe(0);
    });

    test('the address alone gives the same result', async ({ page }) => {
        await page.goto('/sticker/?produkt=ovale-sticker&material=holografisch');

        await expect(shown(page)).toHaveCount(1);
        await expect(page.locator('[data-count]')).toHaveText('1 von 20 Ausführungen');
        await expect(chip(page, 'Ovale Sticker')).toHaveAttribute('aria-current', 'true');
        await expect(chip(page, 'Holografische Folie')).toHaveAttribute('aria-current', 'true');

        // What is not one of the gallery's own products or materials is ignored.
        await page.goto('/sticker/?produkt=%22%3E%3Cscript%3E&material=gold');
        await expect(shown(page)).toHaveCount(20);
        await expect(chip(page, 'Alle')).toHaveAttribute('aria-current', 'true');
    });

    test('the filters are plain links and work without JavaScript', async ({ browser, baseURL, isMobile }) => {
        const context = await browser.newContext({ javaScriptEnabled: false, baseURL, viewport: isMobile ? { width: 390, height: 844 } : { width: 1440, height: 900 } });
        const page = await context.newPage();

        await page.goto('/flyer/');
        await chip(page, '250 g Bilderdruck').click();

        await expect(page).toHaveURL(/\/flyer\/\?papier=250-g$/);
        await expect(shown(page)).toHaveCount(7);
        await expect(page.locator('[data-count]')).toHaveText('7 von 14 Ausführungen');

        await chip(page, 'Flyer Quadrat').click();
        await expect(page).toHaveURL(/\/flyer\/\?produkt=flyer-quadrat&papier=250-g$/);
        await expect(shown(page)).toHaveCount(1);

        await context.close();
    });

    test('a card is one target: its title is the link, the whole card opens it', async ({ page }) => {
        await page.goto('/sticker/?material=transparent');

        const card = shown(page).filter({ hasText: 'Quadratische Sticker' });
        const size = await card.boundingBox();

        // Top left corner of the drawing's surface – far from the title.
        await card.click({ position: { x: 12, y: 12 } });

        // The product's address – as its page on a phone, as the quick view on a wide screen.
        await expect(page).toHaveURL(/\/produkt\/quadratische-sticker\/\?attribute_pa_material=transparent$/);
        expect(size.width).toBeGreaterThan(100);
    });
});
