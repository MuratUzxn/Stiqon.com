import { test, expect } from './fixtures.js';

test.describe('home page', () => {
    test('leads with one action and offers both areas', async ({ page }) => {
        await page.goto('/');

        // The headline carries two product views between its words. They are decoration:
        // the heading's name is the sentence alone.
        const headline = page.getByRole('heading', { level: 1, name: 'Werbemittel, die haften bleiben.', exact: true });

        await expect(headline).toBeVisible();
        await expect(headline.locator('[data-mockup]')).toHaveCount(2);

        const main = page.getByRole('main');

        await expect(main.getByRole('button', { name: 'Motiv wählen' }).first()).toBeVisible();
        await expect(main.getByRole('link', { name: 'Sticker ansehen' }).first()).toHaveAttribute('href', /\/sticker\/$/);
        await expect(main.getByRole('link', { name: 'Flyer ansehen' }).first()).toHaveAttribute('href', /\/flyer\/$/);

        for (const title of ['Erst ansehen, dann bestellen.', 'Formen, Materialien und Formate', 'In drei Schritten zum fertigen Werbemittel', 'Gut zu wissen']) {
            await expect(page.getByRole('heading', { level: 2, name: title })).toBeVisible();
        }
    });

    test('a tile opens its area and the navigation marks it', async ({ page, isMobile }) => {
        await page.goto('/');
        await page.getByRole('link', { name: 'Runde Sticker', exact: true }).click();

        await expect(page).toHaveURL(/\/sticker\/$/);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Sticker');
        await expect(page.getByRole('navigation', { name: 'Pfad' }).getByRole('link', { name: 'Start' })).toBeVisible();

        if (isMobile) {
            await page.locator('header summary').click();
        }

        // Only one of the two header navigations (wide or small screens) is exposed at a time.
        const navigation = page.getByRole('navigation', { name: 'Hauptnavigation' });

        await expect(navigation.getByRole('link', { name: 'Sticker' })).toHaveAttribute('aria-current', 'page');
        await expect(navigation.getByRole('link', { name: 'Flyer' })).not.toHaveAttribute('aria-current', 'page');
    });

    test('a question opens its answer', async ({ page }) => {
        await page.goto('/');

        const answer = page.getByText('Für die Vorschau bleibt das Bild in Ihrem Browser.');

        await expect(answer).toBeHidden();
        await page.getByText('Wird mein Bild dabei hochgeladen?').click();
        await expect(answer).toBeVisible();
    });

    test('the skip link is the first stop for the keyboard and jumps to the content', async ({ page, isMobile }) => {
        test.skip(isMobile, 'Keyboard navigation is checked on the desktop viewport.');

        await page.goto('/');
        await page.keyboard.press('Tab');

        const skipLink = page.getByRole('link', { name: 'Zum Inhalt springen' });

        await expect(skipLink).toBeFocused();
        await expect(skipLink).toBeInViewport();

        await page.keyboard.press('Enter');
        await expect(page).toHaveURL(/#inhalt$/);
    });

    test('the row of examples starts on the line of the page column', async ({ page }) => {
        await page.goto('/');

        // Scroll snapping once pulled the first card out to the window's edge.
        const row = page.getByRole('region', { name: 'Beispiele: Motive auf Stickern und Flyern' });
        const heading = await page.getByRole('heading', { level: 2, name: 'Erst ansehen, dann bestellen.' }).boundingBox();
        const first = await row.locator('> *').first().boundingBox();

        expect(await row.evaluate((element) => element.scrollLeft)).toBe(0);
        expect(Math.round(first.x)).toBe(Math.round(heading.x));
    });

    test('the row of examples moves with its buttons', async ({ page, isMobile }) => {
        test.skip(isMobile, 'The buttons only exist on wide screens; a phone swipes.');

        await page.goto('/');

        const row = page.getByRole('region', { name: 'Beispiele: Motive auf Stickern und Flyern' });
        const previous = page.getByRole('button', { name: 'Vorherige Beispiele' });
        const next = page.getByRole('button', { name: 'Weitere Beispiele' });
        const position = () => row.evaluate((element) => element.scrollLeft);

        await expect(previous).toBeDisabled();
        await expect(next).toBeEnabled();

        await next.click();
        await expect.poll(position).toBeGreaterThan(100);
        await expect(previous).toBeEnabled();

        await previous.click();
        await expect.poll(position).toBe(0);
        await expect(previous).toBeDisabled();
    });

    test('the row of examples can be reached and moved with the keyboard', async ({ page, isMobile }) => {
        test.skip(isMobile, 'Keyboard navigation is checked on the desktop viewport.');

        await page.goto('/');

        const row = page.getByRole('region', { name: 'Beispiele: Motive auf Stickern und Flyern' });

        await row.focus();
        await expect(row).toBeFocused();
        await page.keyboard.press('ArrowRight');
        await expect.poll(() => row.evaluate((element) => element.scrollLeft)).toBeGreaterThan(0);
    });
});

test.describe('mobile navigation', () => {
    test('opens and closes', async ({ page, isMobile }) => {
        test.skip(!isMobile, 'The menu only exists on small screens.');

        await page.goto('/');

        const menu = page.locator('header details');
        const toggle = menu.locator('summary');
        const navigation = page.getByRole('navigation', { name: 'Hauptnavigation' });

        await expect(toggle).toHaveAccessibleName('Menü');
        await expect(menu).not.toHaveAttribute('open');
        await expect(navigation).toBeHidden();

        await toggle.click();
        await expect(menu).toHaveAttribute('open');
        await expect(navigation.getByRole('link', { name: 'Flyer' })).toBeVisible();
        await expect(navigation.getByRole('button', { name: 'Eigenes Motiv ausprobieren' })).toBeVisible();

        await toggle.click();
        await expect(menu).not.toHaveAttribute('open');
        await expect(navigation).toBeHidden();
    });

    test('works without JavaScript', async ({ browser, isMobile, baseURL }) => {
        test.skip(!isMobile, 'The menu only exists on small screens.');

        const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 844 }, baseURL });
        const page = await context.newPage();

        await page.goto('/');
        await page.locator('header summary').click();
        await page.getByRole('navigation', { name: 'Hauptnavigation' }).getByRole('link', { name: 'Flyer' }).click();

        await expect(page).toHaveURL(/\/flyer\/$/);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Flyer');

        await context.close();
    });

    test('the desktop navigation needs no menu', async ({ page, isMobile }) => {
        test.skip(isMobile, 'Desktop viewport only.');

        await page.goto('/');

        const link = page.getByRole('navigation', { name: 'Hauptnavigation' }).getByRole('link', { name: 'Flyer' });

        await expect(page.locator('header summary')).toBeHidden();
        await expect(link).toBeVisible();
        // Links are set in the text colour. (A type style once shared its name with a colour and greyed them.)
        await expect(link).toHaveCSS('color', await page.evaluate(() => getComputedStyle(document.body).color));
    });
});
