import { gzipSync } from 'node:zlib';
import { test, expect, accessibilityViolations, logInAsAdministrator } from './fixtures.js';

/**
 * Search: the control in the header opens it over the page and lists what fits
 * while the visitor types; Enter leads to the results page, which the server
 * renders by the same rules and which works without any script.
 */

const control = (page) => page.locator('header [data-search-open]');
const panel = (page) => page.getByRole('dialog', { name: 'Suche' });
const field = (page) => panel(page).getByRole('combobox');
const rows = (page) => panel(page).getByRole('option');
const cards = (page) => page.locator('[data-card]');

async function open(page, path = '/') {
    await page.goto(path);
    await control(page).click();
    // The rows come from a list that is fetched when the search first opens.
    await expect(rows(page).first()).toBeVisible();
}

test.describe('search over the page', () => {
    test('the control in the header opens it, with the areas as a way in', async ({ page }) => {
        await open(page);

        await expect(panel(page)).toBeVisible();
        await expect(field(page)).toBeFocused();
        await expect(field(page)).toHaveAttribute('aria-expanded', 'true');
        await expect(panel(page)).toContainText('Vorschläge');
        // The first areas of the shop, in the order of the header.
        await expect(rows(page)).toHaveCount(5);
        await expect(rows(page).nth(0)).toContainText('Sticker');
        await expect(rows(page).nth(0)).toContainText('5 Produkte');
        await expect(rows(page).nth(0)).toHaveAttribute('href', /\/sticker\/$/);
        await expect(rows(page).nth(1)).toHaveAttribute('href', /\/flyer\/$/);
        await expect(rows(page).nth(2)).toContainText('Visitenkarten');
        await expect(rows(page).nth(2)).toHaveAttribute('href', /\/visitenkarten\/$/);
        await expect(rows(page).nth(4)).toHaveAttribute('href', /\/briefpapier\/$/);

        // The page below has not moved on.
        await expect(page).toHaveURL(/127\.0\.0\.1:\d+\/$/);
    });

    test('typing lists what fits: variants by their own name, a product for all its variants', async ({ page }) => {
        await open(page);

        await field(page).fill('kraft');
        await expect(panel(page)).toContainText('Treffer');
        await expect(rows(page)).toHaveCount(5);
        await expect(rows(page).first()).toContainText('Runde Sticker');
        await expect(rows(page).first()).toContainText('Kraftpapier');
        await expect(rows(page).first()).toContainText('ab 26,90 €');
        await expect(rows(page).first()).toHaveAttribute('href', /\/produkt\/runde-sticker\/\?attribute_pa_material=kraftpapier$/);
        await expect(panel(page).getByRole('status')).toHaveText('5 Treffer');

        await field(page).fill('sticker');
        await expect(rows(page)).toHaveCount(6);
        await expect(rows(page).first()).toContainText('5 Produkte');
        await expect(rows(page).nth(1)).toHaveAttribute('href', /\/produkt\/runde-sticker\/$/);

        // With an ending of German grammar, and by another word for the product.
        await field(page).fill('runder Aufkleber');
        await expect(rows(page)).toHaveCount(1);
        await expect(rows(page).first()).toContainText('Runde Sticker');

        await field(page).fill('a5 250');
        await expect(rows(page)).toHaveCount(1);
        await expect(rows(page).first()).toContainText('Flyer DIN A5');
        await expect(rows(page).first()).toContainText('250 g Bilderdruck');

        // More fit than are listed: stickers on film, and business cards laminated or hot-foil stamped.
        await field(page).fill('folie');
        await expect(rows(page)).toHaveCount(8);
        await expect(panel(page).getByRole('status')).toHaveText('15 Treffer, die ersten 8 sind aufgelistet');
        await expect(panel(page).getByRole('button', { name: 'Alle Treffer anzeigen' })).toBeVisible();
    });

    test('a query that finds nothing says so and offers no results page', async ({ page }) => {
        await open(page);
        await field(page).fill('regenschirm');

        await expect(rows(page)).toHaveCount(0);
        await expect(panel(page)).toContainText('Dazu haben wir nichts gefunden.');
        await expect(panel(page).getByRole('status')).toHaveText('Keine Treffer');
        await expect(field(page)).toHaveAttribute('aria-expanded', 'false');
        await expect(panel(page).getByRole('button', { name: 'Alle Treffer anzeigen' })).toBeHidden();
    });

    test('Enter without a chosen row leads to the results page', async ({ page }) => {
        await open(page);
        await field(page).fill('holo');
        await page.keyboard.press('Enter');

        await expect(page).toHaveURL(/\/\?s=holo$/);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Suche');
        await expect(page.getByRole('main').getByRole('searchbox')).toHaveValue('holo');
        await expect(page.getByRole('main')).toContainText('5 Treffer für „holo“');
        await expect(cards(page)).toHaveCount(5);
    });

    test('the button below the rows leads there too', async ({ page }) => {
        await open(page, '/flyer/');
        await field(page).fill('bilderdruck');
        await panel(page).getByRole('button', { name: 'Alle Treffer anzeigen' }).click();

        await expect(page).toHaveURL(/\/\?s=bilderdruck$/);
        // Flyers, folded leaflets, business cards, posters and calendars on art paper.
        await expect(cards(page)).toHaveCount(27);
    });

    test('the close button and a tap beside the sheet close it; what was typed is kept', async ({ page }) => {
        await open(page);
        await field(page).fill('oval');

        await panel(page).getByRole('button', { name: 'Suche schließen' }).click();
        await expect(panel(page)).toBeHidden();

        await control(page).click();
        await expect(panel(page)).toBeVisible();
        await expect(field(page)).toHaveValue('oval');
        await expect(rows(page).first()).toContainText('Ovale Sticker');

        // Beside the sheet: its left edge is at least six pixels from the window's.
        await page.mouse.click(3, page.viewportSize().height - 3);
        await expect(panel(page)).toBeHidden();
    });

    test('the open search has no accessibility violations', async ({ page }) => {
        await open(page);
        await field(page).fill('kraft');
        await expect(rows(page)).toHaveCount(5);
        await page.keyboard.press('ArrowDown');
        await expect(rows(page).first()).toHaveAttribute('aria-selected', 'true');

        expect(await accessibilityViolations(page)).toEqual([]);
    });

    test('the open search does not make the page scroll sideways', async ({ page }) => {
        await open(page, '/sticker/');
        await field(page).fill('sticker mit konturschnitt');
        await expect(rows(page).first()).toBeVisible();

        // While the search is open the page keeps the room of its scrollbar, so it is narrower than the window, never wider.
        expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(0);

        const sheet = await panel(page).boundingBox();

        expect(sheet.x).toBeGreaterThanOrEqual(6);
        expect(sheet.x + sheet.width).toBeLessThanOrEqual(page.viewportSize().width - 6);
    });
});

test.describe('search with the keyboard', () => {
    test.skip(({ isMobile }) => isMobile, 'Arrow keys and shortcuts belong to a keyboard.');

    test('the arrow keys choose a row, Enter opens it', async ({ page }) => {
        await open(page);
        await field(page).fill('kraft');
        await expect(rows(page)).toHaveCount(5);
        await expect(field(page)).not.toHaveAttribute('aria-activedescendant');

        await page.keyboard.press('ArrowDown');
        await expect(rows(page).nth(0)).toHaveAttribute('aria-selected', 'true');
        await expect(field(page)).toHaveAttribute('aria-activedescendant', await rows(page).nth(0).getAttribute('id'));

        await page.keyboard.press('ArrowDown');
        await expect(rows(page).nth(1)).toHaveAttribute('aria-selected', 'true');
        await expect(rows(page).nth(0)).toHaveAttribute('aria-selected', 'false');

        // Up from the first row goes round to the last, down from the last to the first.
        await page.keyboard.press('ArrowUp');
        await page.keyboard.press('ArrowUp');
        await expect(rows(page).nth(4)).toHaveAttribute('aria-selected', 'true');
        await page.keyboard.press('ArrowDown');
        await expect(rows(page).nth(0)).toHaveAttribute('aria-selected', 'true');
        await page.keyboard.press('ArrowUp');

        // The keyboard stays in the field the whole time: typing goes on.
        await expect(field(page)).toBeFocused();

        await page.keyboard.press('Enter');
        await expect(page).toHaveURL(/\/produkt\/sticker-mit-konturschnitt\/\?attribute_pa_material=kraftpapier$/);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Sticker mit Konturschnitt');
        await expect(page.getByRole('radio', { name: 'Kraftpapier' })).toBeChecked();
    });

    test('typing on takes the choice back: Enter searches again', async ({ page }) => {
        await open(page);
        await field(page).fill('kraft');
        await page.keyboard.press('ArrowDown');
        await expect(rows(page).first()).toHaveAttribute('aria-selected', 'true');

        await page.keyboard.type('papier');
        await expect(rows(page).first()).toHaveAttribute('aria-selected', 'false');
        await expect(field(page)).not.toHaveAttribute('aria-activedescendant');

        await page.keyboard.press('Enter');
        await expect(page).toHaveURL(/\/\?s=kraftpapier$/);
    });

    test('the pointer chooses a row the way the arrow keys do, so one row is marked at a time', async ({ page }) => {
        await open(page);
        await field(page).fill('vinyl');
        await page.keyboard.press('ArrowDown');

        await rows(page).nth(3).hover();
        await expect(rows(page).nth(3)).toHaveAttribute('aria-selected', 'true');
        await expect(panel(page).locator('[aria-selected="true"]')).toHaveCount(1);

        await rows(page).nth(3).click();
        await expect(page).toHaveURL(/\/produkt\/ovale-sticker\/\?attribute_pa_material=vinyl$/);
    });

    test('Ctrl + K opens it from anywhere, Escape closes it and gives the keyboard back', async ({ page }) => {
        await page.goto('/sticker/');

        const chip = page.getByRole('link', { name: 'Kraftpapier', exact: true });

        await chip.focus();
        await page.keyboard.press('Control+k');
        await expect(panel(page)).toBeVisible();
        await expect(field(page)).toBeFocused();

        await page.keyboard.press('Escape');
        await expect(panel(page)).toBeHidden();
        await expect(chip).toBeFocused();

        // The control says which keys open it.
        await expect(control(page)).toHaveAttribute('aria-keyshortcuts', 'Control+K Meta+K');
        await expect(control(page).locator('kbd')).toHaveText('Strg K');
    });

    test('with a modifier key the control is the link it is', async ({ page, context }) => {
        await page.goto('/');

        const opened = context.waitForEvent('page');

        await control(page).click({ modifiers: ['ControlOrMeta'] });

        const tab = await opened;

        await tab.waitForLoadState();
        await expect(tab).toHaveURL(/\/\?s=$/);
        await expect(panel(page)).toHaveCount(0);
    });
});

test.describe('search, what it costs', () => {
    test('nothing of it is fetched before a visitor asks for it', async ({ page }) => {
        const fetched = [];

        page.on('request', (request) => fetched.push(new URL(request.url()).pathname));

        await page.goto('/');
        await expect.poll(() => page.evaluate(() => typeof window.Alpine?.version)).toBe('string');

        expect(fetched.filter((path) => /search|suchindex/.test(path))).toEqual([]);
        // Its markup waits in a template: no dialog in the page, nothing for a screen reader to find.
        await expect(page.locator('dialog#suche')).toHaveCount(0);

        await control(page).click();
        await expect(rows(page).first()).toBeVisible();

        expect(fetched.filter((path) => /\/assets\/build\/assets\/search-[\w-]+\.js$/.test(path))).toHaveLength(1);
        expect(fetched.filter((path) => path.endsWith('/stiqon/v1/suchindex'))).toHaveLength(1);
    });

    test('the list of what there is to find is small, cacheable and holds nothing but what a result shows', async ({ request }) => {
        const response = await request.get('/wp-json/stiqon/v1/suchindex');

        expect(response.status()).toBe(200);
        expect(response.headers()['cache-control']).toBe('public, max-age=300');

        const { entries } = await response.json();

        // Twelve areas, the 43 products of the example assortment, and a variant for each value of
        // the option a product's drawing shows (a sticker's material, a flyer's paper, a shirt's colour): 96.
        expect(entries).toHaveLength(12 + 43 + 96);
        expect([...new Set(entries.flatMap((entry) => Object.keys(entry)))].sort()).toEqual(['g', 'k', 'm', 'n', 'p', 's', 't', 'u', 'w']);
        expect(entries[0]).toEqual({ n: 'Sticker', s: '5 Produkte', u: expect.stringMatching(/\/sticker\/$/), g: 'sticker', t: 'sticker', k: expect.stringContaining('aufkleber'), w: 3 });
        // It grows with the assortment, but compresses well: names, keywords and addresses repeat.
        // About 48 kB as sent by the test server, about 6 kB as a server that compresses sends it.
        const body = await response.body();

        expect(gzipSync(body).length).toBeLessThan(8 * 1024);
        expect(body.length).toBeLessThan(64 * 1024);

        // A browser that has the list asks whether it changed, and is told "no" without getting it again.
        const tag = response.headers().etag;
        const again = await request.get('/wp-json/stiqon/v1/suchindex', { headers: { 'If-None-Match': tag } });

        expect(tag).toMatch(/^"[0-9a-f]{32}"$/);
        expect(again.status()).toBe(304);
        expect((await again.body()).length).toBe(0);

        // A server that compresses responses hands the tag on marked as weak.
        const weak = await request.get('/wp-json/stiqon/v1/suchindex', { headers: { 'If-None-Match': `"something-else", W/${tag}` } });

        expect(weak.status()).toBe(304);
    });
});

test.describe('search when the list cannot be fetched', () => {
    test.use({ expectedStatuses: [503] });

    test('the field still leads to the results page', async ({ page }) => {
        await page.route('**/stiqon/v1/suchindex', (route) => route.fulfill({ status: 503, body: '' }));

        await page.goto('/');
        await control(page).click();

        await expect(panel(page)).toContainText('Die Vorschläge lassen sich gerade nicht laden.');
        await expect(rows(page)).toHaveCount(0);

        await field(page).fill('kraft');
        await expect(panel(page).getByRole('button', { name: 'Alle Treffer anzeigen' })).toBeVisible();
        await page.keyboard.press('Enter');

        await expect(page).toHaveURL(/\/\?s=kraft$/);
        await expect(cards(page)).toHaveCount(5);
    });
});

test.describe('search for someone who is logged in', () => {
    test.use({ expectedStatuses: [403] });

    test('a page that has been open too long still lists what fits', async ({ page }) => {
        await logInAsAdministrator(page);
        await page.goto('/');

        // The request says who it is from with a token that WordPress takes for a day. This one is no longer taken.
        await page.evaluate(() => {
            document.getElementById('suche-vorlage').content.querySelector('dialog').dataset.nonce = 'abgelaufen';
        });

        const answers = [];

        page.on('response', (response) => {
            if (response.url().endsWith('/stiqon/v1/suchindex')) {
                answers.push(response.status());
            }
        });

        await control(page).click();
        await expect(rows(page)).toHaveCount(5);
        expect(answers).toEqual([403, 200]);
    });
});

test.describe('search without script', () => {
    test.use({ javaScriptEnabled: false });

    test('the control leads to the results page, whose field searches', async ({ page }) => {
        await page.goto('/sticker/');

        // The shortcut only exists with the script; without it, it is not advertised.
        await expect(control(page).locator('kbd')).toBeHidden();

        await control(page).click();
        await expect(page).toHaveURL(/\/\?s=$/);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Suche');
        await expect(page.getByRole('main')).toContainText('Wonach suchen Sie?');
        await expect(cards(page)).toHaveCount(0);

        await page.getByRole('main').getByRole('searchbox').fill('DIN A5');
        await page.getByRole('main').getByRole('button', { name: 'Suchen' }).click();

        await expect(page).toHaveURL(/\/\?s=DIN\+A5$/);
        // The flyer by its name first, then what comes in A5: a notepad and two brochures.
        await expect(cards(page)).toHaveCount(4);
        await expect(cards(page).first()).toContainText('Flyer DIN A5');
    });
});

test.describe('results page', () => {
    test('a product stands for its variants, and its area is offered', async ({ page }) => {
        await page.goto('/?s=sticker');

        await expect(page).toHaveTitle('Suche nach „sticker“ – Stiqon');
        await expect(page.getByRole('main')).toContainText('6 Treffer für „sticker“');
        await expect(page.getByRole('main').getByRole('link', { name: /Bereich Sticker/ })).toHaveAttribute('href', /\/sticker\/$/);
        await expect(cards(page)).toHaveCount(5);
        await expect(cards(page).first().getByRole('link')).toHaveText('Runde Sticker');
        await expect(cards(page).first().getByRole('link')).toHaveAttribute('href', /\/produkt\/runde-sticker\/$/);
        await expect(cards(page).first()).toContainText('ab 24,90 €');
        // Drawn the way the product page opens: on the material the product preselects, white vinyl.
        await expect(cards(page).first().getByRole('img', { name: 'Runde Sticker', exact: true })).toBeVisible();
        await expect(cards(page).first().locator('linearGradient[id$="-holo"]')).toHaveCount(0);
    });

    test('a variant that is asked for by name is the card, drawn and priced as that variant', async ({ page }) => {
        await page.goto('/?s=runde+sticker+holografisch');

        await expect(cards(page)).toHaveCount(1);
        await expect(cards(page).first()).toContainText('Holografische Folie');
        await expect(cards(page).first()).toContainText('ab 34,90 €');
        await expect(cards(page).first().getByRole('link')).toHaveAttribute('href', /\/produkt\/runde-sticker\/\?attribute_pa_material=holografisch$/);
        await expect(cards(page).first().getByRole('img', { name: 'Runde Sticker, Holografische Folie' })).toBeVisible();
        await expect(cards(page).first().locator('linearGradient[id$="-holo"]')).toHaveCount(1);

        await cards(page).first().getByRole('link').click();
        await expect(page.getByRole('radio', { name: 'Holografische Folie' })).toBeChecked();
    });

    test('a search for products only – WooCommerce\'s own field, an old link – ends at the same page', async ({ page }) => {
        await page.goto('/?s=runde+sticker&post_type=product');

        await expect(page).toHaveURL(/\/\?s=runde(\+|%20)sticker$/);
        await expect(page.locator('[data-card]').filter({ hasText: 'Runde Sticker' })).toHaveCount(1);
    });

    test('nothing found: the page says so and shows the way to the areas', async ({ page }) => {
        await page.goto('/?s=regenschirm');

        await expect(page.getByRole('main')).toContainText('Zu „regenschirm“ haben wir nichts gefunden.');
        await expect(cards(page)).toHaveCount(0);
        await expect(page.getByRole('main')).toContainText('Oder sehen Sie sich in einem Bereich um:');
        // Every area, each named as it is in the header.
        await expect(page.getByRole('main').getByRole('link', { name: 'Sticker', exact: true })).toHaveAttribute('href', /\/sticker\/$/);
        await expect(page.getByRole('main').getByRole('link', { name: 'Flyer', exact: true })).toHaveAttribute('href', /\/flyer\/$/);
        await expect(page.getByRole('main').getByRole('link', { name: 'Werbetechnik', exact: true })).toHaveAttribute('href', /\/werbetechnik\/$/);
        await expect(page.getByRole('main').getByRole('link', { name: 'Werbeartikel', exact: true })).toHaveAttribute('href', /\/werbeartikel\/$/);
    });

    test('what was typed is shown as text, whatever it is, and cut to a length', async ({ page }) => {
        const markup = '</title><img src=x onerror=alert(1)>"\'';

        // The guard on every test fails on a request for "x" and on any script error.
        await page.goto(`/?s=${encodeURIComponent(markup)}`);

        await expect(page.getByRole('main').getByRole('searchbox')).toHaveValue(markup);
        await expect(page.getByRole('main')).toContainText(`Zu „${markup}“ haben wir nichts gefunden.`);
        await expect(page.locator('img[src="x"]')).toHaveCount(0);
        // In the title WordPress sets the quotation marks typographically; the rest is the text as typed.
        await expect(page).toHaveTitle(/^Suche nach „<\/title><img src=x onerror=alert\(1\)>.{2}“ – Stiqon$/);

        await page.goto(`/?s=${'kraftpapier '.repeat(20)}`);
        expect((await page.getByRole('main').getByRole('searchbox').inputValue()).length).toBe(80);
        await expect(cards(page)).toHaveCount(5);
    });

    test('search engines are asked to leave the results out', async ({ page }) => {
        await page.goto('/?s=flyer');

        await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', /noindex/);
    });
});
