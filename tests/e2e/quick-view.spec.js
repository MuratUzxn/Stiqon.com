import { test, expect, fixture, accessibilityViolations } from './fixtures.js';

/**
 * The quick view: on a wide screen a card opens large over the gallery, the way
 * mobbin.com opens a screen, with arrows to the cards beside it.
 */

const shown = (page) => page.locator('[data-card]:not([hidden])');
const viewer = (page) => page.getByRole('dialog');

test.describe('quick view', () => {
    test.skip(({ isMobile }) => isMobile, 'On a phone a card leads straight to its product; see the last test.');

    test('a card opens large over the gallery, under the address of its product', async ({ page }) => {
        await page.goto('/sticker/');
        await shown(page).filter({ hasText: 'Ovale Sticker' }).filter({ hasText: 'Kraftpapier' }).click();

        await expect(viewer(page)).toBeVisible();
        await expect(viewer(page).getByRole('heading', { level: 2 })).toHaveText('Ovale Sticker');
        await expect(viewer(page)).toContainText('Kraftpapier');
        await expect(viewer(page)).toContainText('ab 26,90 €');
        await expect(viewer(page)).toContainText('Die freundliche Form für Dankeschön und Gruß.');
        await expect(viewer(page).getByRole('img', { name: 'Ovale Sticker, Kraftpapier' })).toBeVisible();
        await expect(viewer(page).getByRole('status')).toHaveText('16 von 20');

        // The gallery is still the page; the address is the product's, so a shared link works.
        await expect(page).toHaveURL(/\/produkt\/ovale-sticker\/\?attribute_pa_material=kraftpapier$/);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Sticker');
        await expect(viewer(page).getByRole('link', { name: 'Produkt ansehen' })).toHaveAttribute('href', /\/produkt\/ovale-sticker\/\?attribute_pa_material=kraftpapier$/);
    });

    test('the arrows and the arrow keys step through the cards the filters show', async ({ page }) => {
        await page.goto('/sticker/?material=holografisch');
        await shown(page).first().click();

        await expect(viewer(page).getByRole('status')).toHaveText('1 von 5');
        await expect(viewer(page).getByRole('button', { name: 'Vorherige Ausführung' })).toBeDisabled();

        await page.keyboard.press('ArrowRight');
        await expect(viewer(page).getByRole('heading', { level: 2 })).toHaveText('Quadratische Sticker');
        await expect(viewer(page)).toContainText('Holografische Folie');
        await expect(page).toHaveURL(/\/produkt\/quadratische-sticker\/\?attribute_pa_material=holografisch$/);

        await viewer(page).getByRole('button', { name: 'Nächste Ausführung' }).click();
        await expect(viewer(page).getByRole('status')).toHaveText('3 von 5');

        await page.keyboard.press('ArrowLeft');
        await expect(viewer(page).getByRole('status')).toHaveText('2 von 5');

        // At the end the arrow goes out of use, and nothing wraps around.
        for (let presses = 0; presses < 6; presses += 1) {
            await page.keyboard.press('ArrowRight');
        }

        await expect(viewer(page).getByRole('status')).toHaveText('5 von 5');
        await expect(viewer(page).getByRole('heading', { level: 2 })).toHaveText('Sticker mit Konturschnitt');
        await expect(viewer(page).getByRole('button', { name: 'Nächste Ausführung' })).toBeDisabled();
    });

    test('Escape, the close button and a click beside the sheet close it and give the address back', async ({ page }) => {
        await page.goto('/flyer/?papier=250-g');

        const card = shown(page).filter({ hasText: 'Flyer DIN A4' });

        await card.click();
        await expect(viewer(page)).toBeVisible();
        await page.keyboard.press('Escape');
        await expect(viewer(page)).toBeHidden();
        await expect(page).toHaveURL(/\/flyer\/\?papier=250-g$/);
        // The keyboard is back where it was.
        await expect(card.getByRole('link')).toBeFocused();

        await card.click();
        await viewer(page).getByRole('button', { name: 'Schnellansicht schließen' }).click();
        await expect(viewer(page)).toBeHidden();
        await expect(page).toHaveURL(/\/flyer\/\?papier=250-g$/);

        await card.click();
        await expect(viewer(page)).toBeVisible();
        await page.mouse.click(12, 12);
        await expect(viewer(page)).toBeHidden();
        await expect(page).toHaveURL(/\/flyer\/\?papier=250-g$/);
    });

    test('the Back button closes it, Forward opens it again, and a reload shows the product', async ({ page }) => {
        await page.goto('/sticker/');
        await shown(page).nth(2).click();
        await expect(viewer(page)).toBeVisible();

        await page.goBack();
        await expect(viewer(page)).toBeHidden();
        await expect(page).toHaveURL(/\/sticker\/$/);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Sticker');

        await page.goForward();
        await expect(viewer(page)).toBeVisible();
        await expect(viewer(page).getByRole('heading', { level: 2 })).toHaveText('Runde Sticker');

        // The address is a real page.
        await page.reload();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Runde Sticker');
        await expect(page.getByRole('radio', { name: 'Holografische Folie' })).toBeChecked();

        // And from there, Back leads to the gallery.
        await page.goBack();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Sticker');
        await expect(viewer(page)).toBeHidden();
    });

    test('"Produkt ansehen" leads to the product with the option chosen', async ({ page }) => {
        await page.goto('/sticker/');
        await shown(page).filter({ hasText: 'Rechteckige Sticker' }).filter({ hasText: 'Transparente Folie' }).click();
        await viewer(page).getByRole('link', { name: 'Produkt ansehen' }).click();

        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Rechteckige Sticker');
        await expect(page.getByRole('radio', { name: 'Transparente Folie' })).toBeChecked();
    });

    test('the own motif shows in it, and can be chosen from it', async ({ page }) => {
        await page.goto('/sticker/');
        await shown(page).first().click();

        await viewer(page).locator('input[type="file"]').setInputFiles(fixture('motif.png'));

        await expect(viewer(page).locator('[data-motif-image]')).toHaveAttribute('href', /^blob:/);
        await expect(viewer(page).locator('[data-motif-image]')).toBeVisible();

        await page.keyboard.press('ArrowRight');
        await expect(viewer(page).locator('[data-motif-image]')).toHaveAttribute('href', /^blob:/);
    });

    test('the open quick view has no accessibility violations and no id twice', async ({ page }) => {
        await page.goto('/sticker/');
        await shown(page).nth(5).click();
        await expect(viewer(page)).toBeVisible();

        expect(await accessibilityViolations(page)).toEqual([]);

        // The drawing is a copy of the card's; its ids must not collide with the original's.
        const twice = await page.evaluate(() => {
            const seen = new Set();

            return [...document.querySelectorAll('[id]')].map((element) => element.id).filter((id) => seen.size === seen.add(id).size);
        });

        expect(twice).toEqual([]);
    });
});

test.describe('quick view on a phone', () => {
    test('a card leads straight to its product', async ({ page, isMobile }) => {
        test.skip(!isMobile, 'Phone viewport only.');

        await page.goto('/sticker/');
        await shown(page).first().click();

        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Runde Sticker');
        await expect(page.getByRole('dialog')).toHaveCount(0);
    });
});
