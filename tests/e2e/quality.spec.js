import { test, expect, fixture, accessibilityViolations, logInAsAdministrator, cli } from './fixtures.js';

const pages = ['/', '/sticker/', '/flyer/'];

test.describe('accessibility (axe, WCAG 2.2 AA)', () => {
    for (const path of pages) {
        test(`${path} has no violations`, async ({ page }) => {
            await page.goto(path);

            expect(await accessibilityViolations(page)).toEqual([]);
        });
    }

    test('the home page with a chosen motif has no violations', async ({ page }) => {
        await page.goto('/');
        await page.locator('input[type="file"]').first().setInputFiles(fixture('motif.png'));
        // The bar fades in; contrast is only meaningful once it is fully opaque.
        await expect(page.getByRole('group', { name: 'Ihr Motiv' })).toHaveCSS('opacity', '1');

        expect(await accessibilityViolations(page)).toEqual([]);
    });

    test('a page with an error message has no violations', async ({ page }) => {
        await page.goto('/sticker/');
        await page.locator('input[type="file"]').first().setInputFiles(fixture('notiz.txt'));
        await expect(page.getByRole('button', { name: 'Meldung schließen' })).toBeVisible();

        expect(await accessibilityViolations(page)).toEqual([]);
    });

    test('the open mobile menu has no violations', async ({ page, isMobile }) => {
        test.skip(!isMobile, 'The menu only exists on small screens.');

        await page.goto('/');
        await page.locator('header summary').click();
        await expect(page.getByRole('navigation', { name: 'Hauptnavigation' })).toBeVisible();

        expect(await accessibilityViolations(page)).toEqual([]);
    });
});

test.describe('error page', () => {
    test.use({ expectedStatuses: [404] });

    test('an unknown address explains itself inside the shop frame', async ({ page }) => {
        const response = await page.goto('/gibt-es-nicht/');

        expect(response.status()).toBe(404);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Diese Seite gibt es nicht.');
        await expect(page.getByRole('main').getByRole('link', { name: 'Zur Startseite' })).toBeVisible();
        expect(await accessibilityViolations(page)).toEqual([]);
    });

    test('the sample sheet does not exist for visitors', async ({ page }) => {
        const response = await page.goto('/musterbogen/');

        expect(response.status()).toBe(404);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Diese Seite gibt es nicht.');
    });
});

test.describe('sample sheet published by mistake', () => {
    test.use({ expectedStatuses: [404] });

    // The page is meant to be private. A page is quickly published – the template must not rely on it.
    let id;

    test.beforeAll(() => {
        // Not looked up by name: for a single private page WP-CLI, which is nobody, gets no answer.
        const pages = JSON.parse(cli('post', 'list', '--post_type=page', '--post_status=any', '--fields=ID,post_name', '--format=json'));

        id = String(pages.find((page) => page.post_name === 'musterbogen').ID);
        cli('post', 'update', id, '--post_status=publish');
    });

    test.afterAll(() => {
        cli('post', 'update', id, '--post_status=private');
    });

    test('visitors still get "not found"', async ({ page }) => {
        const response = await page.goto('/musterbogen/');

        expect(response.status()).toBe(404);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Diese Seite gibt es nicht.');
        await expect(page.locator('[data-mockup]')).toHaveCount(0);
    });

    test('the administrator still sees it', async ({ page }) => {
        await logInAsAdministrator(page);

        const response = await page.goto('/musterbogen/');

        expect(response.status()).toBe(200);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Musterbogen');
    });
});

test.describe('sample sheet', () => {
    test('an administrator sees it, and it passes the same checks as the shop pages', async ({ page }) => {
        await logInAsAdministrator(page);

        const response = await page.goto('/musterbogen/');

        expect(response.status()).toBe(200);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Musterbogen');
        await expect(page.locator('[data-mockup]')).toHaveCount(20);

        expect(await accessibilityViolations(page)).toEqual([]);
        expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBe(0);
    });
});

test.describe('layout', () => {
    for (const path of pages) {
        test(`${path} does not scroll sideways`, async ({ page }) => {
            await page.goto(path);

            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

            expect(overflow).toBe(0);
        });
    }
});

test.describe('what the pages bring along', () => {
    for (const path of pages) {
        test(`${path} sets no cookies and loads nothing but the theme's own files`, async ({ page, context }) => {
            const scripts = [];
            page.on('request', (request) => {
                if (request.resourceType() === 'script') {
                    scripts.push(new URL(request.url()).pathname);
                }
            });

            await page.goto(path);
            await expect.poll(() => page.evaluate(() => typeof window.Alpine?.version)).toBe('string');

            expect(await context.cookies()).toEqual([]);
            expect(scripts.filter((script) => !script.startsWith('/wp-content/themes/stiqon/'))).toEqual([]);
        });
    }

    for (const path of [...pages, '/shop/', '/warenkorb/']) {
        // The guard on every test sees requests. A hint in the page head – preconnect,
        // dns-prefetch – makes the browser contact a server without any request the
        // guard could see, so the markup itself is read here.
        test(`${path} names no other server in its markup`, async ({ page }) => {
            await page.goto(path);

            const elsewhere = await page.evaluate(() => [...document.querySelectorAll('link[href], script[src], img[src], source[src], iframe[src], form[action]')]
                .filter((element) => !(element instanceof HTMLLinkElement) || /icon|stylesheet|preload|prefetch|preconnect|prerender|modulepreload|manifest/.test(element.rel))
                .map((element) => element.href || element.src || element.action)
                .filter((address) => /^https?:/.test(address) && new URL(address).origin !== location.origin));

            expect(elsewhere).toEqual([]);
        });
    }
});
