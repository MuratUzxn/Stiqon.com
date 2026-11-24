import { test, expect } from './fixtures.js';

test.describe('home page', () => {
    test('leads with one action and offers both areas', async ({ page }) => {
        await page.goto('/');

        await expect(page.getByRole('heading', { level: 1 })).toContainText('Werbemittel, die haften bleiben.');
        await expect(page.getByRole('main').getByRole('button', { name: 'Motiv wählen' }).first()).toBeVisible();

        for (const title of ['In drei Schritten zum fertigen Werbemittel', 'Formen, Materialien und Formate', 'Gut zu wissen']) {
            await expect(page.getByRole('heading', { level: 2, name: title })).toBeVisible();
        }
    });

    test('an area tile opens the area and the navigation marks it', async ({ page, isMobile }) => {
        await page.goto('/');
        await page.getByRole('heading', { level: 3, name: 'Sticker', exact: true }).getByRole('link').click();

        await expect(page).toHaveURL(/\/sticker\/$/);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Sticker');

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

    test('the card wall stands still for visitors who prefer reduced motion', async ({ page, isMobile }) => {
        test.skip(isMobile, 'The moving wall only exists on wide screens.');

        await page.emulateMedia({ reducedMotion: 'reduce' });
        await page.goto('/');

        const column = page.locator('[x-data="cardWall"] > div').first();
        const before = await column.boundingBox();
        await page.waitForTimeout(600);
        const after = await column.boundingBox();

        expect(after.y).toBe(before.y);
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

        await expect(page.locator('header summary')).toBeHidden();
        await expect(page.getByRole('navigation', { name: 'Hauptnavigation' }).getByRole('link', { name: 'Flyer' })).toBeVisible();
    });
});
