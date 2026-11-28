import { test, expect, cli, accessibilityViolations, logInAsAdministrator } from './fixtures.js';

/**
 * The register "Design" beside "Produkte": the header names the two registers
 * on every page and marks the one a page belongs to; the list of designs
 * filters by links; a design is shown only once it is published, has its
 * preview image and is released. The test site has six example designs, one
 * published without release ("Herbstmarkt") and one released without its
 * picture ("Kaffeepause") – see tools/dev/example-designs.php.
 */

const shown = ['Neu eröffnet', 'Große Eröffnung', 'Konfetti', 'Gartenfest', 'Prozente', 'Wochenangebot'];
const shownNames = ['neu-eroeffnet', 'grosse-eroeffnung', 'konfetti', 'gartenfest', 'prozente', 'wochenangebot'].map((name) => `beispiel-${name}`);
const kept = ['Herbstmarkt', 'Kaffeepause'];

const cards = (page) => page.locator('section[aria-labelledby="alle-designs"] li');
const titles = (page) => cards(page).locator('h3');

/**
 * The registers as the header has them: [label, aria-current]. The bar's, the
 * phones' row below it and the menu's are read all – only one row is exposed at
 * a width – and must agree. In the menu the areas follow "Produkte".
 */
async function registers(page) {
    const navigations = page.locator('header nav[aria-label="Hauptnavigation"]');
    const read = (links) => links.evaluateAll((elements) => elements.map((link) => [link.textContent.trim(), link.getAttribute('aria-current')]));

    const bar = await read(navigations.nth(0).locator('a'));

    expect(await read(page.locator('header nav[aria-label="Register"] a'))).toEqual(bar);
    expect(await read(navigations.nth(1).locator('a.text-heading'))).toEqual(bar);

    return bar;
}

/** Ids of the example designs, by their address. */
function designId(name) {
    return cli('post', 'list', '--post_type=stiqon_design', `--name=${name}`, '--post_status=any', '--field=ID');
}

/** Releases a design as the box in the administration does: for the picture it has now. */
function release(id) {
    cli('post', 'meta', 'update', id, '_stiqon_design_released', cli('post', 'meta', 'get', id, '_thumbnail_id'));
}

const noScroll = (page) => page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

test.describe('the registers in the header', () => {
    test.use({ expectedStatuses: [404] });

    const pages = [
        ['/', [['Design', null], ['Produkte', 'page']]],
        ['/sticker/', [['Design', null], ['Produkte', 'true']]],
        ['/produkt/runde-sticker/', [['Design', null], ['Produkte', 'true']]],
        ['/?s=folie', [['Design', null], ['Produkte', 'true']]],
        ['/warenkorb/', [['Design', null], ['Produkte', 'true']]],
        ['/kasse/', [['Design', null], ['Produkte', 'true']]],
        ['/mein-konto/', [['Design', null], ['Produkte', 'true']]],
        // A search started on the list of designs shows the shop's results page.
        ['/design/?s=Herbstmarkt', [['Design', null], ['Produkte', 'true']]],
        ['/design/', [['Design', 'page'], ['Produkte', null]]],
        // A filter nobody can choose is no filter: still the start of the register.
        ['/design/?werbemittel=plakat', [['Design', 'page'], ['Produkte', null]]],
        ['/design/?werbemittel=flyer', [['Design', 'true'], ['Produkte', null]]],
        ['/design/beispiel-neu-eroeffnet/', [['Design', 'true'], ['Produkte', null]]],
        // Pages of neither register.
        ['/impressum/', [['Design', null], ['Produkte', null]]],
        ['/gibt-es-nicht/', [['Design', null], ['Produkte', null]]],
    ];

    for (const [path, expected] of pages) {
        test(`${path} marks its register`, async ({ page }) => {
            await page.goto(path);

            expect(await registers(page)).toEqual(expected);
        });
    }

    test('lead to the start of each register, and the areas are a click away', async ({ page, isMobile }) => {
        await page.goto('/sticker/');

        const navigation = page.getByRole('navigation', { name: 'Hauptnavigation' });

        if (isMobile) {
            await page.locator('header summary').click();
            // In the menu the registers come first, then the areas.
            await expect(navigation.getByRole('link')).toHaveText(['Design', 'Produkte', 'Sticker', 'Flyer', 'Kundenkonto']);
            await expect(navigation.getByRole('link', { name: 'Sticker' })).toHaveAttribute('aria-current', 'page');
            await page.locator('header summary').click();
        } else {
            // The bar names the registers and nothing else.
            await expect(navigation.getByRole('link')).toHaveText(['Design', 'Produkte']);
        }

        // An area page switches to the other area itself.
        const areas = page.getByRole('navigation', { name: 'Bereiche' });

        await expect(areas.getByRole('link', { name: 'Sticker' })).toHaveAttribute('aria-current', 'page');
        await areas.getByRole('link', { name: 'Flyer' }).click();
        await expect(page).toHaveURL(/\/flyer\/$/);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Flyer');
        await expect(areas.getByRole('link', { name: 'Flyer' })).toHaveAttribute('aria-current', 'page');
        await expect(areas.getByRole('link', { name: 'Sticker' })).not.toHaveAttribute('aria-current');

        if (isMobile) {
            await page.locator('header summary').click();
        }

        await navigation.getByRole('link', { name: 'Design' }).click();
        await expect(page).toHaveURL(/\/design\/$/);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Designs');

        if (isMobile) {
            await page.locator('header summary').click();
        }

        await navigation.getByRole('link', { name: 'Produkte' }).click();
        await expect(page.getByRole('heading', { level: 1, name: 'Werbemittel, die haften bleiben.' })).toBeVisible();
    });

    test('are set like the model\'s: the current one in ink, the other in grey', async ({ page, isMobile }) => {
        test.skip(isMobile, 'The bar shows the registers from 768 px on.');

        await page.goto('/design/');

        const navigation = page.getByRole('navigation', { name: 'Hauptnavigation' });
        const ink = await page.evaluate(() => getComputedStyle(document.body).color);

        await expect(navigation.getByRole('link', { name: 'Design' })).toHaveCSS('color', ink);
        await expect(navigation.getByRole('link', { name: 'Produkte' })).toHaveCSS('color', 'rgb(107, 107, 107)');
        await expect(navigation.getByRole('link', { name: 'Design' })).toHaveCSS('text-decoration-line', 'none');
    });

    test('work without JavaScript', async ({ browser, baseURL, isMobile }) => {
        const context = await browser.newContext({ javaScriptEnabled: false, baseURL, viewport: isMobile ? { width: 390, height: 844 } : { width: 1440, height: 900 } });
        const page = await context.newPage();

        await page.goto('/flyer/');
        expect(await registers(page)).toEqual([['Design', null], ['Produkte', 'true']]);

        if (isMobile) {
            await page.locator('header summary').click();
        }

        await page.getByRole('navigation', { name: 'Hauptnavigation' }).getByRole('link', { name: 'Design' }).click();
        await expect(page).toHaveURL(/\/design\/$/);
        expect(await registers(page)).toEqual([['Design', 'page'], ['Produkte', null]]);

        // The areas switch among themselves.
        await page.goto('/sticker/');
        await page.getByRole('navigation', { name: 'Bereiche' }).getByRole('link', { name: 'Flyer' }).click();
        await expect(page).toHaveURL(/\/flyer\/$/);

        await context.close();
    });

    for (const width of [375, 390]) {
        test(`stand in a row of their own on a phone of ${width} px, without scrolling sideways`, async ({ page, isMobile }) => {
            test.skip(!isMobile, 'Phone width.');

            await page.setViewportSize({ width, height: 812 });

            for (const path of ['/', '/design/', '/design/beispiel-neu-eroeffnet/', '/sticker/', '/design/?werbemittel=flyer&anlass=feier']) {
                await page.goto(path);
                expect(await noScroll(page), path).toBe(0);

                // Every page names both registers, without opening the menu, with room for a finger.
                const row = page.getByRole('navigation', { name: 'Register' });

                for (const name of ['Design', 'Produkte']) {
                    const link = row.getByRole('link', { name });

                    await expect(link, path).toBeVisible();
                    expect((await link.boundingBox()).height, `${path} ${name}`).toBeGreaterThanOrEqual(44);
                }

                await page.locator('header summary').click();

                const menu = page.getByRole('navigation', { name: 'Hauptnavigation' });

                for (const name of ['Design', 'Produkte']) {
                    expect((await menu.getByRole('link', { name, exact: true }).boundingBox()).height, `${path} menu ${name}`).toBeGreaterThanOrEqual(44);
                }

                expect(await noScroll(page), path).toBe(0);
            }
        });
    }

    test('are both set in ink in the menu, and the areas below "Produkte"', async ({ page, isMobile }) => {
        test.skip(!isMobile, 'The menu only exists on small screens.');

        await page.goto('/design/');
        await page.locator('header summary').click();

        const menu = page.getByRole('navigation', { name: 'Hauptnavigation' });
        const ink = await page.evaluate(() => getComputedStyle(document.body).color);
        const products = await menu.getByRole('link', { name: 'Produkte' }).boundingBox();
        const sticker = await menu.getByRole('link', { name: 'Sticker' }).boundingBox();

        await expect(menu.getByRole('link', { name: 'Design', exact: true })).toHaveCSS('color', ink);
        await expect(menu.getByRole('link', { name: 'Produkte' })).toHaveCSS('color', ink);
        expect(sticker.y).toBeGreaterThan(products.y);
        // Set in below the register they belong to.
        expect(await menu.getByRole('link', { name: 'Sticker' }).evaluate((link) => parseFloat(getComputedStyle(link).paddingLeft)))
            .toBeGreaterThan(await menu.getByRole('link', { name: 'Kundenkonto' }).evaluate((link) => parseFloat(getComputedStyle(link).paddingLeft)));
    });

    test('the footer leads into both', async ({ page }) => {
        await page.goto('/impressum/');

        const overview = page.getByRole('navigation', { name: 'Übersicht' });

        await expect(overview.getByRole('link')).toHaveText(['Designs', 'Sticker', 'Flyer']);
        await overview.getByRole('link', { name: 'Designs' }).click();
        await expect(page).toHaveURL(/\/design\/$/);
    });
});

test.describe('the list of designs', () => {
    test('shows the designs that are released, newest first, and what they can be filtered by', async ({ page }) => {
        const response = await page.goto('/design/');

        expect(response.status()).toBe(200);
        await expect(page).toHaveTitle(/^Designs – Stiqon/);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Designs');
        await expect(page.getByText('Vorlagen für Ihre Werbemittel. Probieren Sie jedes Design auf unseren Produkten aus.')).toBeVisible();
        await expect(titles(page)).toHaveText(shown);

        for (const title of kept) {
            await expect(page.locator('body')).not.toContainText(title);
        }

        // Below the title, medium and occasion – the card's link is the title alone.
        await expect(cards(page).first()).toContainText('Sticker · Eröffnung');
        await expect(cards(page).first().getByRole('link')).toHaveText('Neu eröffnet');

        const explore = page.getByRole('navigation', { name: 'Designs entdecken' });

        await expect(explore.getByRole('heading')).toHaveText(['Werbemittel', 'Anlässe']);
        await expect(explore.getByRole('link')).toHaveText(['Sticker', 'Flyer', 'Eröffnung', 'Feier', 'Rabattaktion']);
        await expect(explore.getByRole('link', { name: 'Feier' })).toHaveAttribute('href', /\/design\/\?anlass=feier$/);

        await expect(page.getByRole('group', { name: 'Werbemittel' }).getByRole('link')).toHaveText(['Alle', 'Sticker', 'Flyer']);
        await expect(page.getByRole('group', { name: 'Anlass' }).getByRole('link')).toHaveText(['Alle Anlässe', 'Eröffnung', 'Feier', 'Rabattaktion']);
        await expect(page.getByRole('group', { name: 'Werbemittel' }).getByRole('link', { name: 'Alle' })).toHaveAttribute('aria-current', 'page');
        await expect(page.getByText('Beispieldesigns für die Entwicklung.')).toBeVisible();

        // The pictures arrive, from this shop's own address (the guard on every test sees to that).
        for (const image of await cards(page).locator('img').all()) {
            await image.scrollIntoViewIfNeeded();
            await expect.poll(() => image.evaluate((element) => element.complete && element.naturalWidth > 0)).toBe(true);
            await expect(image).toHaveAttribute('alt', '');
        }
    });

    test('filters by links, without JavaScript, and keeps the other filter', async ({ browser, baseURL, isMobile }) => {
        const context = await browser.newContext({ javaScriptEnabled: false, baseURL, viewport: isMobile ? { width: 390, height: 844 } : { width: 1440, height: 900 } });
        const page = await context.newPage();
        const media = page.getByRole('group', { name: 'Werbemittel' });
        const occasions = page.getByRole('group', { name: 'Anlass' });

        await page.goto('/design/');
        await media.getByRole('link', { name: 'Flyer' }).click();
        await expect(page).toHaveURL(/\/design\/\?werbemittel=flyer$/);

        // Every occasion keeps the medium, and with none chosen "Alle Anlässe" is the current one.
        for (const href of await occasions.getByRole('link').evaluateAll((links) => links.map((link) => link.getAttribute('href')))) {
            expect(href).toContain('werbemittel=flyer');
        }

        await expect(occasions.getByRole('link', { name: 'Alle Anlässe' })).toHaveAttribute('aria-current', 'page');
        // The filters are one landmark, the count says what they leave – on a phone, too.
        await expect(page.getByRole('navigation', { name: 'Designs filtern' })).toBeVisible();
        await expect(page.getByText('3 Designs')).toBeVisible();
        await expect(titles(page)).toHaveText(['Große Eröffnung', 'Gartenfest', 'Wochenangebot']);
        await expect(media.getByRole('link', { name: 'Flyer' })).toHaveAttribute('aria-current', 'page');
        await expect(media.getByRole('link', { name: 'Alle' })).not.toHaveAttribute('aria-current', 'page');

        await occasions.getByRole('link', { name: 'Feier' }).click();
        await expect(page).toHaveURL(/\/design\/\?(?=.*werbemittel=flyer)(?=.*anlass=feier)/);
        await expect(titles(page)).toHaveText(['Gartenfest']);
        await expect(occasions.getByRole('link', { name: 'Feier' })).toHaveAttribute('aria-current', 'page');

        await media.getByRole('link', { name: 'Alle' }).click();
        await expect(page).toHaveURL(/\/design\/\?anlass=feier$/);
        await expect(titles(page)).toHaveText(['Konfetti', 'Gartenfest']);

        // The explore block stands on the list's start only; it leads to a filtered list, too.
        await expect(page.getByRole('navigation', { name: 'Designs entdecken' })).toHaveCount(0);
        await page.goto('/design/');
        await page.getByRole('navigation', { name: 'Designs entdecken' }).getByRole('link', { name: 'Rabattaktion' }).click();
        await expect(page).toHaveURL(/\/design\/\?anlass=rabattaktion$/);
        await expect(titles(page)).toHaveText(['Prozente', 'Wochenangebot']);

        await context.close();
    });

    test('takes an unknown filter for none', async ({ page }) => {
        await page.goto('/design/?werbemittel=plakat&anlass=%3Cb%3E');

        await expect(titles(page)).toHaveText(shown);
        await expect(page.getByRole('group', { name: 'Werbemittel' }).getByRole('link', { name: 'Alle' })).toHaveAttribute('aria-current', 'page');
        expect(await page.content()).not.toContain('<b>');
    });

    test('a card is its whole surface, its link the title alone', async ({ page }) => {
        await page.goto('/design/');

        const card = cards(page).nth(1);
        const size = await card.boundingBox();

        await card.click({ position: { x: size.width / 2, y: size.height / 3 } });
        await expect(page).toHaveURL(/\/design\/beispiel-grosse-eroeffnung\/$/);
    });

    test('passes the checks of the shop\'s own pages', async ({ page }) => {
        for (const path of ['/design/', '/design/?werbemittel=sticker&anlass=feier']) {
            await page.goto(path);

            expect(await accessibilityViolations(page), path).toEqual([]);
            expect(await noScroll(page), path).toBe(0);

            const ids = await page.evaluate(() => [...document.querySelectorAll('link[rel="stylesheet"], style[id]')].map((element) => element.id));

            expect(ids).toContain('stiqon-css');
            expect(ids.filter((id) => /block|global-styles|classic-theme|woocommerce|wc-/.test(id))).toEqual([]);
        }
    });

    test.describe('pages beyond the last', () => {
        test.use({ expectedStatuses: [404] });

        test('do not exist', async ({ page }) => {
            expect((await page.goto('/design/page/2/')).status()).toBe(404);
        });
    });
});

test.describe('a list longer than a page', () => {
    // 22 more sticker designs, newer than the examples: 28 shown, 25 of them stickers.
    let copies = [];

    test.beforeAll(() => {
        const source = designId('beispiel-konfetti');

        copies = cli('eval', `
            $source = ${source};
            $image = (int) get_post_thumbnail_id($source);
            $ids = [];
            for ($i = 1; $i <= 22; $i++) {
                $id = wp_insert_post(['post_type' => 'stiqon_design', 'post_status' => 'publish', 'post_title' => "Kopie {$i}", 'post_name' => "kopie-{$i}", 'post_date' => gmdate('Y-m-d H:i:s', time() - $i * 60)]);
                set_post_thumbnail($id, $image);
                update_post_meta($id, '_stiqon_design_released', (string) $image);
                wp_set_object_terms($id, ['sticker'], 'stiqon_design_medium');
                $ids[] = $id;
            }
            echo implode(' ', $ids);
        `).split(' ');
    });

    test.afterAll(() => {
        cli('post', 'delete', ...copies, '--force');
    });

    test('is paged, 24 designs to a page, and keeps its filter from page to page', async ({ page }) => {
        const pages = page.getByRole('navigation', { name: 'Seiten' });

        await page.goto('/design/');
        await expect(cards(page)).toHaveCount(24);
        await expect(pages).toContainText('Seite 1 von 2');
        await expect(pages.getByRole('link', { name: 'Vorherige Seite' })).toHaveCount(0);
        await pages.getByRole('link', { name: 'Nächste Seite' }).click();

        await expect(page).toHaveURL(/\/design\/page\/2\/$/);
        await expect(cards(page)).toHaveCount(4);
        // The oldest four, newest first.
        await expect(titles(page)).toHaveText(['Konfetti', 'Gartenfest', 'Prozente', 'Wochenangebot']);
        await expect(pages.getByRole('link', { name: 'Vorherige Seite' })).toHaveAttribute('href', /\/design\/$/);
        // Inside the register, not its start; and no explore block beyond the first page.
        expect(await registers(page)).toEqual([['Design', 'true'], ['Produkte', null]]);
        await expect(page.getByRole('navigation', { name: 'Designs entdecken' })).toHaveCount(0);

        await page.goto('/design/?werbemittel=sticker');
        await expect(cards(page)).toHaveCount(24);
        await expect(pages.getByRole('link', { name: 'Nächste Seite' })).toHaveAttribute('href', /\/design\/page\/2\/\?werbemittel=sticker$/);
        await pages.getByRole('link', { name: 'Nächste Seite' }).click();
        await expect(titles(page)).toHaveText(['Prozente']);
        await expect(page.getByRole('group', { name: 'Werbemittel' }).getByRole('link', { name: 'Sticker' })).toHaveAttribute('aria-current', 'page');
    });
});

test.describe('a term that only designs no one sees carry', () => {
    let term;

    test.beforeAll(() => {
        term = cli('term', 'create', 'stiqon_design_occasion', 'Herbst', '--slug=herbst', '--porcelain');
        cli('post', 'term', 'add', designId('beispiel-herbstmarkt'), 'stiqon_design_occasion', 'herbst', '--by=slug');
    });

    test.afterAll(() => {
        cli('term', 'delete', 'stiqon_design_occasion', term);
    });

    test('is neither offered nor counted, and filters nothing', async ({ page }) => {
        await page.goto('/design/?anlass=herbst');

        await expect(page.getByRole('group', { name: 'Anlass' }).getByRole('link')).toHaveText(['Alle Anlässe', 'Eröffnung', 'Feier', 'Rabattaktion']);
        await expect(page.getByRole('navigation', { name: 'Designs entdecken' })).not.toContainText('Herbst');
        await expect(titles(page)).toHaveText(shown);
    });

    test('is unknown to visitors in WordPress\' interface', async ({ page }) => {
        const list = await (await page.request.get('/wp-json/wp/v2/stiqon_design_occasion?per_page=100')).json();

        expect(list.map((item) => item.slug)).not.toContain('herbst');
        expect((await page.request.get(`/wp-json/wp/v2/stiqon_design_occasion/${term}`)).status()).toBe(404);
    });
});

test.describe('the list without designs to show', () => {
    let released;

    test.beforeAll(() => {
        released = shownNames.map(designId);
    });

    test('says that the first are being made, and leads to the products', async ({ page }) => {
        for (const id of released) {
            cli('post', 'meta', 'delete', id, '_stiqon_design_released');
        }

        try {
            const response = await page.goto('/design/');

            expect(response.status()).toBe(200);
            await expect(page.getByRole('heading', { level: 1 })).toHaveText('Designs');
            await expect(page.getByRole('main')).toContainText('Die ersten Designs entstehen gerade.');
            await expect(page.getByRole('navigation', { name: 'Designs entdecken' })).toHaveCount(0);
            await expect(page.getByRole('navigation', { name: 'Designs filtern' })).toHaveCount(0);
            await expect(cards(page)).toHaveCount(0);
            expect(await accessibilityViolations(page)).toEqual([]);

            await page.getByRole('main').getByRole('link', { name: 'Zu den Produkten' }).click();
            await expect(page.getByRole('heading', { level: 1, name: 'Werbemittel, die haften bleiben.' })).toBeVisible();
        } finally {
            for (const id of released) {
                release(id);
            }
        }
    });

    test('a choice of filters without a design says so', async ({ page }) => {
        const gartenfest = designId('beispiel-gartenfest');

        cli('post', 'meta', 'delete', gartenfest, '_stiqon_design_released');

        try {
            await page.goto('/design/?werbemittel=flyer&anlass=feier');

            await expect(cards(page)).toHaveCount(0);
            await expect(page.getByRole('main')).toContainText('Zu dieser Auswahl gibt es noch kein Design.');
            await page.getByRole('main').getByRole('link', { name: 'Alle Designs' }).click();
            await expect(titles(page)).toHaveCount(5);
        } finally {
            release(gartenfest);
        }
    });
});

test.describe('a design\'s page', () => {
    test('shows the design, where it belongs, and more of its kind', async ({ page }) => {
        const response = await page.goto('/design/beispiel-neu-eroeffnet/');

        expect(response.status()).toBe(200);
        await expect(page).toHaveTitle(/^Neu eröffnet – Stiqon/);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Neu eröffnet');

        const main = page.getByRole('main');

        await expect(main.getByRole('link', { name: 'Alle Designs' })).toHaveAttribute('href', /\/design\/$/);
        await expect(main.getByRole('link', { name: 'Sticker', exact: true })).toHaveAttribute('href', /\/design\/\?werbemittel=sticker$/);
        await expect(main.getByRole('link', { name: 'Eröffnung', exact: true })).toHaveAttribute('href', /\/design\/\?anlass=eroeffnung$/);
        await expect(main).toContainText('Ein Strahlenkranz mit Stern');
        await expect(main.getByRole('link', { name: 'Zu den Stickern' })).toHaveAttribute('href', /\/sticker\/$/);
        await expect(main.getByRole('button', { name: 'Als Motiv ausprobieren' })).toBeVisible();

        // More designs for stickers – only shown ones; "Kaffeepause" waits for its picture.
        const more = page.locator('section[aria-labelledby="weitere-designs"]');

        await expect(more.getByRole('heading', { level: 2 })).toHaveText('Weitere Designs');
        await expect(more.locator('h3')).toHaveText(['Konfetti', 'Prozente']);

        const image = page.locator('article img');

        await expect.poll(() => image.evaluate((element) => element.complete && element.naturalWidth > 0)).toBe(true);
        expect(await accessibilityViolations(page)).toEqual([]);
        expect(await noScroll(page)).toBe(0);
    });

    test('a flyer design leads to the flyers', async ({ page }) => {
        await page.goto('/design/beispiel-wochenangebot/');

        await expect(page.getByRole('main').getByRole('link', { name: 'Zu den Flyern' })).toHaveAttribute('href', /\/flyer\/$/);
    });

    test('"Als Motiv ausprobieren" makes the design the motif, and the area shows it', async ({ page }) => {
        await page.goto('/design/beispiel-neu-eroeffnet/');
        await page.getByRole('button', { name: 'Als Motiv ausprobieren' }).click();

        await expect(page).toHaveURL(/\/sticker\/$/);
        await expect(page.getByRole('group', { name: 'Ihr Motiv' })).toContainText('Neu eröffnet');

        const images = page.locator('[data-motif-image]');

        await expect(images.first()).toBeVisible();
        expect(await images.count()).toBeGreaterThan(10);
        expect((await images.evaluateAll((nodes) => nodes.map((node) => node.getAttribute('href')))).every((href) => href?.startsWith('blob:'))).toBe(true);

        // Kept like a motif chosen as a file: the next page shows it as well.
        await page.goto('/flyer/');
        await expect(page.getByRole('group', { name: 'Ihr Motiv' })).toContainText('Neu eröffnet');
    });

    test('where the browser keeps nothing, the page stays and says so', async ({ page }) => {
        // Private windows and blocked site data look like this to a page (as in motif.spec.js).
        await page.addInitScript(() => {
            Object.defineProperty(window, 'indexedDB', { value: { open() { throw new DOMException('denied', 'SecurityError'); } } });
        });

        await page.goto('/design/beispiel-neu-eroeffnet/');

        const button = page.getByRole('button', { name: 'Als Motiv ausprobieren' });

        await button.click();

        const told = 'Dieses Fenster erlaubt nicht, das Design als Motiv für die nächste Seite zu behalten.';

        await expect(page.getByText(told).last()).toBeVisible();
        await expect(page.getByRole('status')).toContainText(told);
        await expect(page).toHaveURL(/\/design\/beispiel-neu-eroeffnet\/$/);
        // Ready for another try.
        await expect(button).not.toHaveAttribute('aria-disabled', 'true');
        await expect(button).not.toHaveAttribute('aria-busy', 'true');
    });

    test.describe('when the picture does not come', () => {
        test.use({ expectedStatuses: [500] });

        for (const [what, answer] of [
            ['the server fails', { status: 500, body: 'Fehler' }],
            // A type the motif does not take: the visitor chose no file, so no word about formats.
            ['it is of a kind the motif does not take', { status: 200, contentType: 'image/gif', body: 'GIF89a' }],
        ]) {
            test(`says so and stays, where ${what}`, async ({ page }) => {
                await page.goto('/design/beispiel-neu-eroeffnet/');

                const button = page.getByRole('button', { name: 'Als Motiv ausprobieren' });
                const image = await page.locator('[x-data="designMotif"]').getAttribute('data-image');

                await page.route(image, (route) => route.fulfill(answer));
                await button.click();

                await expect(page.getByText('Das Design lässt sich gerade nicht laden. Bitte versuchen Sie es noch einmal.').last()).toBeVisible();
                await expect(page.getByText('Bitte ein Bild im Format PNG, JPEG oder WebP wählen.')).toHaveCount(0);
                await expect(page).toHaveURL(/\/design\/beispiel-neu-eroeffnet\/$/);
                await expect(button).not.toHaveAttribute('aria-disabled', 'true');
                await expect(page.getByRole('group', { name: 'Ihr Motiv' })).toBeHidden();
            });
        }
    });

    test('without JavaScript offers only what works', async ({ browser, baseURL }) => {
        const context = await browser.newContext({ javaScriptEnabled: false, baseURL });
        const page = await context.newPage();

        await page.goto('/design/beispiel-gartenfest/');
        await expect(page.getByRole('button', { name: 'Als Motiv ausprobieren' })).toHaveCount(0);
        await page.getByRole('main').getByRole('link', { name: 'Zu den Flyern' }).click();
        await expect(page).toHaveURL(/\/flyer\/$/);

        await context.close();
    });
});

test.describe('designs the shop does not show', () => {
    test.use({ expectedStatuses: [404] });

    for (const [name, title] of [['beispiel-herbstmarkt', 'Herbstmarkt'], ['beispiel-kaffeepause', 'Kaffeepause']]) {
        test(`"${title}" has no address and is not listed anywhere`, async ({ page }) => {
            const response = await page.goto(`/design/${name}/`);

            expect(response.status()).toBe(404);
            await expect(page.getByRole('heading', { level: 1 })).toHaveText('Diese Seite gibt es nicht.');
            expect(await page.content()).not.toContain(title);

            // Nor through WordPress' interface, which lists the shown ones only.
            const id = designId(name);
            const list = await page.request.get('/wp-json/wp/v2/stiqon_design?per_page=100');

            expect((await list.json()).map((design) => design.title.rendered).sort()).toEqual([...shown].sort());
            expect((await page.request.get(`/wp-json/wp/v2/stiqon_design/${id}`)).status()).toBe(404);

            // No other way of WordPress' names it either: not by its number – no redirect
            // to its address –, not as an embed, not in a feed of the type.
            for (const path of [`/?p=${id}`, `/?p=${id}&post_type=stiqon_design`, `/?p=${id}&embed=true`, `/design/${name}/embed/`, `/design/${name.slice(0, -3)}/`]) {
                const response = await page.request.get(path, { maxRedirects: 0 });

                expect(response.status(), path).toBe(404);
                expect(await response.text(), path).not.toContain(title);
            }

            for (const feed of ['/?post_type=stiqon_design&feed=rss2', '/?post_type[]=stiqon_design&feed=atom']) {
                const response = await page.request.get(feed);

                expect(await response.text(), feed).not.toContain(title);
            }

            // Nor the interface's search, which looks through all types itself.
            const search = await page.request.get(`/wp-json/wp/v2/search?search=${encodeURIComponent(title)}`);

            expect(await search.json()).toEqual([]);
            expect(await (await page.request.get(`/wp-json/wp/v2/search?search=${encodeURIComponent(title)}&subtype=stiqon_design`)).json()).toEqual([]);
        });
    }

    test('the picture under review is not in the media library', async ({ page }) => {
        const id = designId('beispiel-herbstmarkt');
        const picture = cli('post', 'meta', 'get', id, '_thumbnail_id');

        expect(await (await page.request.get(`/wp-json/wp/v2/media?parent=${id}`)).json()).toEqual([]);
        expect((await page.request.get(`/wp-json/wp/v2/media/${picture}`)).status()).toBe(404);

        const library = await (await page.request.get('/wp-json/wp/v2/media?per_page=100')).json();
        const names = library.map((item) => item.slug);

        expect(names).not.toContain('herbstmarkt');
        expect(names).toContain('konfetti');

        // Its page would name it, and the redirect to its file as well.
        expect((await page.request.get(`/?attachment_id=${picture}`, { maxRedirects: 0 })).status()).toBe(404);
    });

    test('the interface counts the occasions of shown designs only', async ({ page }) => {
        const occasions = await (await page.request.get('/wp-json/wp/v2/stiqon_design_occasion')).json();

        // Feier: "Konfetti" and "Gartenfest" – not "Herbstmarkt" and "Kaffeepause".
        expect(occasions.map((term) => [term.slug, term.count])).toEqual([['eroeffnung', 2], ['feier', 2], ['rabattaktion', 2]]);
    });

    test('a released design that loses its release goes', async ({ page }) => {
        const id = designId('beispiel-konfetti');

        cli('post', 'meta', 'delete', id, '_stiqon_design_released');

        try {
            expect((await page.goto('/design/beispiel-konfetti/')).status()).toBe(404);
            await page.goto('/design/');
            await expect(titles(page)).toHaveCount(5);
        } finally {
            release(id);
        }
    });

    test('a new picture needs a release of its own', async ({ page }) => {
        const konfetti = designId('beispiel-konfetti');
        const kaffeepause = designId('beispiel-kaffeepause');
        const checked = cli('post', 'meta', 'get', konfetti, '_thumbnail_id');
        const other = cli('post', 'meta', 'get', designId('beispiel-herbstmarkt'), '_thumbnail_id');

        // Exchanged after the release: the new picture was not checked.
        cli('post', 'meta', 'update', konfetti, '_thumbnail_id', other);
        // Released while it had none: its first picture was not checked either.
        cli('post', 'meta', 'update', kaffeepause, '_thumbnail_id', other);

        try {
            expect((await page.goto('/design/beispiel-konfetti/')).status()).toBe(404);
            expect((await page.goto('/design/beispiel-kaffeepause/')).status()).toBe(404);
            await page.goto('/design/');
            await expect(titles(page)).toHaveText(shown.filter((title) => title !== 'Konfetti'));
        } finally {
            cli('post', 'meta', 'update', konfetti, '_thumbnail_id', checked);
            cli('post', 'meta', 'delete', kaffeepause, '_thumbnail_id');
        }

        expect((await page.goto('/design/beispiel-konfetti/')).status()).toBe(200);
    });
});

test.describe('the release in the administration', () => {
    test.use({ expectedStatuses: [404] });

    test('is given for the picture, taken back by unticking, and lost with a new picture', async ({ page, request, isMobile }) => {
        test.skip(isMobile, 'The administration is tested on the desktop.');

        const id = designId('beispiel-herbstmarkt');
        const picture = cli('post', 'meta', 'get', id, '_thumbnail_id');
        const visitor = async () => (await request.get('/design/beispiel-herbstmarkt/')).status();
        const edit = async () => {
            await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);

            return page.getByLabel('Freigegeben: Ähnlichkeits- und Markenprüfung erledigt');
        };
        const save = async () => {
            await page.locator('#publish').click();
            await expect(page.locator('#message')).toContainText('aktualisiert');
        };
        const release = async () => page.locator('#the-list tr', { hasText: 'Herbstmarkt' }).locator('td.column-stiqon_release').textContent();

        await logInAsAdministrator(page);

        try {
            let box = await edit();

            // What it takes for a design to appear, said under the title; and the box for its provenance.
            await expect(page.getByText('Ein Design erscheint im Shop erst, wenn es veröffentlicht ist, ein Vorschaubild hat und dieses Bild')).toBeVisible();
            await expect(page.locator('#stiqon-design-provenance').getByRole('heading', { name: 'Herkunft und Freigabe' })).toBeVisible();
            await expect(box).not.toBeChecked();

            // Before the release the editor may look at it; nobody else.
            expect((await page.goto('/design/beispiel-herbstmarkt/?preview=true')).status()).toBe(200);
            await expect(page.getByRole('heading', { level: 1 })).toHaveText('Herbstmarkt');
            expect((await request.get('/design/beispiel-herbstmarkt/?preview=true')).status()).toBe(404);

            box = await edit();
            await box.check();
            await save();
            expect(cli('post', 'meta', 'get', id, '_stiqon_design_released')).toBe(picture);
            expect(await visitor()).toBe(200);

            await page.goto('/wp-admin/edit.php?post_type=stiqon_design');
            expect(await release()).toBe('Ja – im Shop');

            // Another picture: not the one that was checked.
            cli('post', 'meta', 'update', id, '_thumbnail_id', cli('post', 'meta', 'get', designId('beispiel-konfetti'), '_thumbnail_id'));
            expect(await visitor()).toBe(404);
            await page.reload();
            expect(await release()).toBe('Nein – neues Vorschaubild, nicht freigegeben');
            box = await edit();
            await expect(box).not.toBeChecked();
            await expect(page.getByText('Das Vorschaubild ist nicht mehr das freigegebene.')).toBeVisible();
            cli('post', 'meta', 'update', id, '_thumbnail_id', picture);

            box = await edit();
            await expect(box).toBeChecked();
            await box.uncheck();
            await save();
            expect(cli('post', 'meta', 'list', id, '--keys=_stiqon_design_released', '--format=count')).toBe('0');
            expect(await visitor()).toBe(404);

            await page.goto('/wp-admin/edit.php?post_type=stiqon_design');
            expect(await release()).toBe('Nein – nicht freigegeben');
        } finally {
            cli('post', 'meta', 'update', id, '_thumbnail_id', picture);
            cli('eval', `delete_post_meta(${Number(id)}, '_stiqon_design_released');`);
        }
    });
});
