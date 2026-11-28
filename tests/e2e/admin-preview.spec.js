import { test, expect, cli, logInAsAdministrator } from './fixtures.js';

/**
 * The tab "Vorschau" of a product in the administration: it shows how the shop
 * draws the product, and what is chosen there decides the drawing in the shop.
 */

const panel = (page) => page.locator('#stiqon_preview_product_data');
const field = (page, name) => panel(page).locator(`select[name="stiqon_mockup[${name}]"]`);
const outline = (page) => page.locator('[data-view]:not([hidden]) clipPath[id$="-print"] path').getAttribute('d');

/** What is stored for the drawing of a product; null when nothing is. */
function stored(id) {
    const value = cli('eval', `echo wp_json_encode(get_post_meta(${Number(id)}, '_stiqon_mockup', true) ?: null);`);

    return JSON.parse(value);
}

async function openTab(page, id) {
    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    await page.locator('#woocommerce-product-data li.stiqon_preview_options a').click();
    await expect(panel(page)).toBeVisible();
}

async function save(page) {
    await page.locator('#publish').click();
    await page.waitForURL(/message=1/);
}

test.describe('the tab "Vorschau" of a product', () => {
    test.skip(({ isMobile }) => isMobile, 'The administration is used at a desk.');

    let id;
    const asSetUp = { type: 'sticker', shape: 'oval', motif: 'danke' };

    test.beforeAll(() => {
        id = cli('post', 'list', '--post_type=product', '--name=ovale-sticker', '--field=ID');
    });

    test.afterAll(() => {
        cli('eval', `update_post_meta(${Number(id)}, '_stiqon_mockup', json_decode('${JSON.stringify(asSetUp)}', true)); wc_delete_product_transients(${Number(id)});`);
    });

    test('shows the product drawn in each of its materials, and what is stored for it', async ({ page }) => {
        await logInAsAdministrator(page);
        await openTab(page, id);

        const views = panel(page).getByRole('group', { name: 'So wird das Produkt gezeichnet' }).locator('figure');

        await expect(views).toHaveCount(4);
        await expect(views.nth(0).getByRole('img', { name: 'Ovale Sticker, Weißes Vinyl' })).toBeVisible();
        await expect(views.nth(3)).toContainText('Kraftpapier');
        await expect(views.nth(3)).toContainText('gezeichnet als Kraftpapier, matt');

        // The fields are found by their labels.
        // Exactly: a folded leaflet's field is called "Falzart".
        await expect(panel(page).getByLabel('Art', { exact: true })).toHaveAttribute('name', 'stiqon_mockup[type]');
        await expect(panel(page).getByLabel('Form', { exact: true })).toHaveAttribute('name', 'stiqon_mockup[shape]');

        await expect(field(page, 'type')).toHaveValue('sticker');
        await expect(field(page, 'shape')).toHaveValue('oval');
        await expect(field(page, 'sticker_motif')).toHaveValue('danke');
        // "Automatisch" says what the names alone make of the product.
        await expect(field(page, 'type').locator('option').first()).toHaveText('Automatisch – erkannt: Sticker');
        await expect(field(page, 'shape').locator('option').first()).toHaveText('Automatisch – erkannt: Oval');

        // A sticker has a shape, a flyer a format: only the fields of the chosen kind are offered.
        await expect(field(page, 'format')).toBeHidden();
        await field(page, 'type').selectOption('flyer');
        await expect(field(page, 'format')).toBeVisible();
        await expect(field(page, 'shape')).toBeHidden();
    });

    test('what is chosen there is stored and decides the drawing in the shop', async ({ page }) => {
        await page.goto('/produkt/ovale-sticker/');

        const oval = await outline(page);

        await logInAsAdministrator(page);
        await openTab(page, id);
        await field(page, 'shape').selectOption('square');
        await field(page, 'sticker_motif').selectOption('sonne');
        await save(page);

        expect(stored(id)).toEqual({ type: 'sticker', shape: 'square', motif: 'sonne' });

        await page.locator('#woocommerce-product-data li.stiqon_preview_options a').click();
        await expect(field(page, 'shape')).toHaveValue('square');

        await page.goto('/produkt/ovale-sticker/');
        expect(await outline(page)).not.toBe(oval);

        // Back to "Automatisch" everywhere: nothing is stored, and the name decides again.
        await openTab(page, id);
        await field(page, 'type').selectOption('');
        await field(page, 'shape').selectOption('');
        await field(page, 'sticker_motif').selectOption('');
        await save(page);

        expect(stored(id)).toBeNull();

        await page.goto('/produkt/ovale-sticker/');
        expect(await outline(page)).toBe(oval);
    });
});
