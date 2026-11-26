import { test, expect, accessibilityViolations, cli, logInAsAdministrator, mails } from './fixtures.js';

/**
 * Buying: cart, checkout, the confirmation with the bank details, the account
 * and the e-mails – WooCommerce's and Germanized's pages in this theme's look.
 * The test site is set up for it by tools/dev/shop-settings.php: delivery
 * within Germany at one price, payment in advance, legal pages as placeholders.
 */

const cartLink = (page) => page.locator('header [data-cart-count]');
const rows = (page) => page.locator('.woocommerce-cart-form .cart_item');
const sums = (page) => page.locator('.cart_totals');
const review = (page) => page.locator('#order_review');

/** Puts a product into the cart, with the options its address chooses. */
async function add(page, path) {
    await page.goto(path);
    await page.getByRole('button', { name: 'In den Warenkorb' }).click();
    await expect(page.getByText('wurde Ihrem Warenkorb hinzugefügt')).toBeVisible();
}

/**
 * Opens the checkout and waits until it is the page a person fills in.
 * WooCommerce's script fetches the overview once more right after loading and
 * puts fresh markup in its place – a tick set before that would be gone.
 */
async function openCheckout(page) {
    const refreshed = page.waitForResponse((response) => response.url().includes('wc-ajax=update_order_review'));

    await page.goto('/kasse/');
    await refreshed;
    await expect(page.locator('.blockUI')).toHaveCount(0);
}

/** An address nobody else in this run uses, so its e-mails can be told apart. */
const address = (testInfo, who) => `${who}-${testInfo.project.name}-${Date.now()}@example.com`;

async function fillIn(page, email) {
    await page.locator('#billing_first_name').fill('Erika');
    await page.locator('#billing_last_name').fill('Mustermann');
    await page.locator('#billing_address_1').fill('Musterstraße 1');
    await page.locator('#billing_postcode').fill('10115');
    await page.locator('#billing_city').fill('Berlin');
    await page.locator('#billing_email').fill(email);
}

/** Number of the order whose confirmation the page shows. */
async function placed(page) {
    await page.waitForURL(/\/order-received\/\d+\//);
    await expect(page.getByText('Vielen Dank. Ihre Bestellung ist eingegangen.')).toBeVisible();

    return Number(/\/order-received\/(\d+)\//.exec(page.url())[1]);
}

/** The order as the shop has it. */
function stored(id) {
    return JSON.parse(cli('eval', `
        $order = wc_get_order(${Number(id)});
        echo wp_json_encode([
            'status' => $order->get_status(),
            'total' => (float) $order->get_total(),
            'tax' => (float) $order->get_total_tax(),
            'shipping' => round((float) $order->get_shipping_total() + (float) $order->get_shipping_tax(), 2),
            'payment' => $order->get_payment_method(),
            'customer' => $order->get_customer_id() > 0,
            'country' => $order->get_shipping_country(),
            'items' => array_values(array_map(static fn ($item) => [$item->get_name(), $item->get_meta('pa_material'), $item->get_meta('pa_auflage'), $item->get_quantity()], $order->get_items())),
        ]);
    `));
}

/** The e-mails that went to an address, as { subject, message }. */
const sentTo = (email) => mails().filter((mail) => mail.to.includes(email));

/** Addresses of everything an e-mail would fetch when opened. */
const fetched = (html) => [...html.matchAll(/<(?:img|link|script|iframe)\b[^>]*?\b(?:src|href)=["']([^"']+)["']/gi)].map((match) => match[1]);

test.describe('cart', () => {
    test('the header counts what is in the cart, also when the cart page takes something out', async ({ page }) => {
        await page.goto('/');
        await expect(cartLink(page)).toHaveAccessibleName('Warenkorb, leer');
        await expect(cartLink(page).locator('span')).toBeHidden();

        await add(page, '/produkt/runde-sticker/?attribute_pa_material=kraftpapier');
        await expect(cartLink(page)).toHaveAccessibleName('Warenkorb, 1 Artikel');
        await add(page, '/produkt/flyer-din-a5/');
        await expect(cartLink(page)).toHaveAccessibleName('Warenkorb, 2 Artikel');
        await expect(cartLink(page).locator('span')).toHaveText('2');

        await cartLink(page).click();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Warenkorb');
        await expect(rows(page)).toHaveCount(2);

        // WooCommerce's script takes the row out without loading the page.
        await rows(page).filter({ hasText: 'Flyer DIN A5' }).locator('a.remove').click();
        await expect(rows(page)).toHaveCount(1);
        await expect(cartLink(page)).toHaveAccessibleName('Warenkorb, 1 Artikel');
        await expect(cartLink(page).locator('span')).toHaveText('1');

        await rows(page).locator('a.remove').click();
        await expect(page.getByText('Ihr Warenkorb ist derzeit leer.')).toBeVisible();
        await expect(cartLink(page)).toHaveAccessibleName('Warenkorb, leer');
        await expect(cartLink(page).locator('span')).toBeHidden();

        // From the empty cart the way leads to the products – not to WooCommerce's own list page.
        await page.getByRole('link', { name: 'Sticker und Flyer ansehen' }).click();
        await expect(page.getByRole('heading', { level: 1, name: 'Werbemittel, die haften bleiben.' })).toBeVisible();
    });

    test('a page the browser kept shows the cart as it is now, not as it was', async ({ page }) => {
        await page.goto('/sticker/');
        await page.goto('/produkt/runde-sticker/?attribute_pa_material=kraftpapier');
        await expect(cartLink(page)).toHaveAccessibleName('Warenkorb, leer');

        await page.getByRole('button', { name: 'In den Warenkorb' }).click();
        await expect(page.getByText('wurde Ihrem Warenkorb hinzugefügt')).toBeVisible();
        await expect(cartLink(page)).toHaveAccessibleName('Warenkorb, 1 Artikel');

        // "Back" shows pages as the browser kept them: written for an empty cart.
        await page.goBack();
        await expect(page.getByText('wurde Ihrem Warenkorb hinzugefügt')).toHaveCount(0);
        await expect(cartLink(page)).toHaveAccessibleName('Warenkorb, 1 Artikel');
        await expect(cartLink(page).locator('span')).toHaveText('1');
        await page.goBack();
        await expect(page).toHaveURL(/\/sticker\/$/);
        await expect(cartLink(page)).toHaveAccessibleName('Warenkorb, 1 Artikel');

        // The other way round: a page written for a full cart, seen again after the cart was emptied.
        await page.goto('/flyer/');
        await expect(cartLink(page)).toHaveAccessibleName('Warenkorb, 1 Artikel');
        await page.goto('/warenkorb/');
        await rows(page).locator('a.remove').click();
        await expect(page.getByText('Ihr Warenkorb ist derzeit leer.')).toBeVisible();
        await page.goBack();
        await expect(page).toHaveURL(/\/flyer\/$/);
        await expect(cartLink(page)).toHaveAccessibleName('Warenkorb, leer');
        await expect(cartLink(page).locator('span')).toBeHidden();
    });

    test('it lists what was chosen, drawn as chosen, with the sums a customer has to see', async ({ page }) => {
        await add(page, '/produkt/runde-sticker/?attribute_pa_material=kraftpapier');
        await add(page, '/produkt/flyer-din-a5/');
        await page.goto('/warenkorb/');

        const sticker = rows(page).filter({ hasText: 'Runde Sticker' });

        await expect(sticker.locator('.product-name')).toContainText('Kraftpapier');
        await expect(sticker.locator('.product-name')).toContainText('100 Stück');
        await expect(sticker.locator('.delivery-time-info')).toHaveText('Lieferzeit: 3–5 Werktage (Beispiel)');
        await expect(sticker.locator('.product-subtotal')).toHaveText('26,90 €');
        // The drawing is the one of this variant, and names the link WooCommerce makes of it.
        await expect(sticker.locator('.product-thumbnail').getByRole('link', { name: 'Runde Sticker, Kraftpapier' })).toHaveAttribute('href', /\/produkt\/runde-sticker\/\?attribute_pa_material=kraftpapier/);
        await expect(sticker.locator('.product-thumbnail svg[data-mockup="sticker"]')).toBeVisible();
        await expect(rows(page).filter({ hasText: 'Flyer DIN A5' }).locator('.product-thumbnail svg[data-mockup="flyer"]')).toBeVisible();

        // A row is called what its product is called, and lists what was chosen – whether the
        // product has three options or two (WooCommerce would write two into the name).
        const flyer = rows(page).filter({ hasText: 'Flyer DIN A5' });

        await expect(sticker.locator('.product-name > a')).toHaveText('Runde Sticker');
        await expect(flyer.locator('.product-name > a')).toHaveText('Flyer DIN A5');
        await expect(sticker.locator('.product-name dl.variation dt')).toHaveText(['Material:', 'Größe:', 'Auflage:']);
        await expect(flyer.locator('.product-name dl.variation dt')).toHaveText(['Papier:', 'Auflage:']);
        await expect(flyer.locator('.product-name dl.variation dd')).toHaveText(['135 g Bilderdruck', '100 Stück']);

        expect(await accessibilityViolations(page)).toEqual([]);
        expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBe(0);

        // How many is one of the options: the cart asks for no second number.
        await expect(page.locator('.woocommerce-cart-form input.qty:not([type="hidden"])')).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Warenkorb aktualisieren' })).toBeHidden();

        await expect(sums(page).locator('.cart-subtotal td')).toHaveText('52,80 €');
        await expect(sums(page).locator('.shipping th')).toHaveText('Versand');
        await expect(sums(page).locator('.shipping td')).toContainText('Standardversand (Beispielpreis): 4,90 €');
        await expect(sums(page).locator('.order-total td')).toContainText('57,70 €');
        await expect(sums(page).locator('.order-tax')).toContainText('inkl. 19 % MwSt.');
        await expect(sums(page).locator('.order-tax td')).toHaveText('9,21 €');

        // One country, one price: nothing to calculate, nothing that "will be updated".
        await expect(page.locator('.shipping-calculator-button')).toHaveCount(0);
        await expect(sums(page)).not.toContainText('Bezahlvorgang');

        await sums(page).getByRole('link', { name: 'Weiter zur Kasse' }).click();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Kasse');
    });
});

test.describe('checkout', () => {
    test('what is ordered stands directly above the button, with what the law wants beside it', async ({ page }) => {
        await add(page, '/produkt/runde-sticker/?attribute_pa_material=kraftpapier');
        await openCheckout(page);

        const row = review(page).locator('.cart_item');

        await expect(row.locator('.product-name')).toContainText('Runde Sticker');
        await expect(row.locator('.product-name')).toContainText('× 1');
        await expect(row.locator('.product-name')).toContainText('Kraftpapier');
        // WooCommerce prints the name through a filter that takes drawings apart and
        // leaves their lettering behind. The drawing arrives whole, and beside it
        // stands the name and nothing else.
        await expect(row.locator('.stiqon-order-drawing svg[data-mockup="sticker"]')).toBeVisible();
        expect(await row.locator('.product-name').evaluate((cell) => {
            const copy = cell.cloneNode(true);

            copy.querySelectorAll('svg, dl, p').forEach((element) => element.remove());

            return copy.textContent.replace(/\s+/g, ' ').trim();
        })).toBe('Runde Sticker × 1');
        // How long delivery takes has to be said here, too.
        await expect(row.locator('.delivery-time-info')).toHaveText('Lieferzeit: 3–5 Werktage (Beispiel)');
        await expect(row.locator('.product-total')).toHaveText('26,90 €');

        await expect(review(page).locator('.shipping td')).toContainText('Standardversand (Beispielpreis): 4,90 €');
        await expect(review(page).locator('.order-total td')).toContainText('31,80 €');
        await expect(review(page).locator('.order-tax')).toContainText('inkl. 19 % MwSt.');

        // The button says that it costs money, and nothing stands between the order and it.
        const button = page.locator('#place_order');

        await expect(button).toHaveText('Jetzt kaufen');

        const parts = ['#order_payment_heading', '#legal', '#order_review_heading', '#order_review table', '#place_order'];
        const boxes = await page.evaluate((selectors) => selectors.map((selector) => {
            const box = document.querySelector(selector).getBoundingClientRect();

            return [Math.round(box.top), Math.round(box.bottom)];
        }), parts);
        const tops = boxes.map(([top]) => top);

        // How to pay, the sentence to agree to, the heading, what is ordered, the button.
        expect(tops).toEqual([...tops].sort((a, b) => a - b));
        expect(boxes[4][0] - boxes[3][1]).toBeLessThan(40);

        // The sentence to agree to names the texts and leads to them.
        const sentence = page.locator('label[for="legal"]');

        await expect(sentence.getByRole('link', { name: 'Allgemeinen Geschäftsbedingungen' })).toHaveAttribute('href', /\/agb\/$/);
        await expect(sentence.getByRole('link', { name: 'Widerrufsbestimmungen' })).toHaveAttribute('href', /\/widerruf\/$/);

        // Delivered within Germany: the country is said, not asked for.
        await expect(page.locator('#billing_country_field')).toContainText('Deutschland');
    });

    test('a product the shop cannot draw keeps the picture WooCommerce has for it', async ({ page }) => {
        // A sample set, a voucher: a simple product with a photo – here WooCommerce's stand-in.
        const id = Number(cli('eval', `
            $product = new WC_Product_Simple();
            $product->set_name('Musterset zur Prüfung');
            $product->set_sku('test-musterset');
            $product->set_regular_price('5.00');
            $product->set_status('publish');
            $product->set_catalog_visibility('hidden');
            echo $product->save();
        `));

        try {
            await page.goto(`/?add-to-cart=${id}`);
            await add(page, '/produkt/runde-sticker/');
            await openCheckout(page);

            const sample = review(page).locator('.cart_item').filter({ hasText: 'Musterset zur Prüfung' });
            const sticker = review(page).locator('.cart_item').filter({ hasText: 'Runde Sticker' });
            const [photo, drawing, cell] = [await sample.locator('.wc-gzd-cart-item-thumbnail img').boundingBox(), await sticker.locator('.stiqon-order-drawing').boundingBox(), await sample.locator('td.product-name').boundingBox()];

            // In the place a drawing has in the other row: in front of the name, the same width.
            expect(photo).not.toBeNull();
            expect(Math.round(photo.x)).toBe(Math.round(cell.x));
            expect(Math.round(photo.x)).toBe(Math.round(drawing.x));
            expect(Math.round(photo.width)).toBe(Math.round(drawing.width));
            // Where a drawing follows, Germanized's own picture stays away.
            await expect(sticker.locator('.wc-gzd-cart-item-thumbnail')).toBeHidden();
        } finally {
            cli('eval', `wp_delete_post(${id}, true);`);
        }
    });

    test('it says what is missing: at each field, and in a list that leads there', async ({ page }) => {
        await add(page, '/produkt/flyer-din-a6/');
        await openCheckout(page);
        await page.locator('#place_order').click();

        const list = page.locator('ul.woocommerce-error');

        await expect(list).toBeVisible();
        await expect(list).toBeFocused();
        await expect(list.getByRole('listitem')).toHaveCount(7);
        await expect(list.getByRole('link', { name: 'Rechnung: Vorname ist ein Pflichtfeld.' })).toHaveAttribute('href', '#billing_first_name');
        await expect(list).toContainText('Bitte akzeptieren Sie unsere Allgemeinen Geschäftsbedingungen und Widerrufsbestimmungen');

        const field = page.locator('#billing_first_name');

        await expect(field).toHaveAttribute('aria-invalid', 'true');
        await expect(field).toHaveAccessibleDescription('Rechnung: Vorname ist ein Pflichtfeld.');
        await expect(page.locator('#billing_first_name_field .checkout-inline-error-message')).toBeVisible();
        await expect(page.locator('.checkout-inline-error-message')).toHaveCount(7);

        // A message with links of its own stays one sentence: its pieces run on as
        // text, they are not set apart like a sentence and its button.
        const last = list.getByRole('listitem').last();

        await expect(last.getByRole('link')).not.toHaveCount(1);
        expect(await last.evaluate((item) => getComputedStyle(item).display)).not.toBe('flex');

        expect(await accessibilityViolations(page)).toEqual([]);
        expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBe(0);
    });
});

test.describe('an order by payment in advance', () => {
    test('as a guest: confirmation with the bank details, the order in the administration, the e-mails', async ({ page, baseURL }, testInfo) => {
        const email = address(testInfo, 'gast');

        await add(page, '/produkt/runde-sticker/?attribute_pa_material=kraftpapier');
        await openCheckout(page);
        await fillIn(page, email);
        await page.locator('#legal').check();
        await page.locator('#place_order').click();

        const id = await placed(page);

        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Bestellung eingegangen');
        await expect(page.locator('ul.woocommerce-order-overview')).toContainText(String(id));
        await expect(page.locator('ul.woocommerce-order-overview')).toContainText('31,80 €');
        await expect(page.locator('ul.woocommerce-order-overview')).toContainText('Vorkasse (Überweisung)');
        await expect(page.locator('.woocommerce-bacs-bank-details')).toContainText('DE00 0000 0000 0000 0000 00');
        await expect(page.locator('.woocommerce-order-details')).toContainText('Runde Sticker');
        await expect(page.locator('.woocommerce-order-details')).toContainText('Kraftpapier');
        await expect(page.locator('.woocommerce-customer-details')).toContainText('Musterstraße 1');

        expect(await accessibilityViolations(page)).toEqual([]);
        expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBe(0);

        // The cart is empty again.
        await expect(cartLink(page)).toHaveAccessibleName('Warenkorb, leer');

        // The shop has the order, waiting for the money.
        expect(stored(id)).toEqual({
            status: 'on-hold',
            total: 31.8,
            tax: 5.07,
            shipping: 4.9,
            payment: 'bacs',
            customer: false,
            country: 'DE',
            items: [['Runde Sticker', 'kraftpapier', '100', 1]],
        });

        // The customer gets one e-mail: the confirmation, with everything needed to pay.
        const sent = sentTo(email);

        expect(sent.map((mail) => mail.subject)).toEqual([`Bestätigung Ihrer Bestellung ${id}`]);

        const [{ message }] = sent;
        // What it says, without its markup.
        const text = message.replace(/<style[\s\S]*?<\/style>|<[^>]+>/g, ' ').replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ');

        expect(text).toContain('Guten Tag Erika Mustermann,');
        expect(text).toContain('DE00 0000 0000 0000 0000 00');
        expect(text).toContain('Runde Sticker');
        expect(text).toContain('Kraftpapier');
        expect(text).toContain('Lieferzeit: 3–5 Werktage (Beispiel)');
        expect(text).toContain('Standardversand (Beispielpreis)');
        expect(text).toMatch(/31,80\s*€/);
        expect(text).toContain('inkl. 19 % MwSt.');
        // Germanized attaches the legal texts; here they are the placeholders.
        expect(text).toContain('Widerrufsbelehrung');
        expect(text).toContain('Platzhalter.');
        // It opens with the shop's logo, and fetches nothing from anywhere but the shop.
        expect(message).toMatch(/<img[^>]+logo-mail-320\.png/);
        expect(fetched(message).filter((url) => !url.startsWith(baseURL))).toEqual([]);

        // The shop is told as well.
        expect(mails().filter((mail) => new RegExp(`neue Bestellung.*\\b${id}\\b`, 'i').test(mail.subject))).toHaveLength(1);
    });

    test('the order shows in the list of orders in the administration', async ({ page, isMobile }, testInfo) => {
        test.skip(isMobile, 'The administration is WordPress\'s own; once is enough.');

        await add(page, '/produkt/flyer-din-a6/');
        await openCheckout(page);
        await fillIn(page, address(testInfo, 'liste'));
        await page.locator('#legal').check();
        await page.locator('#place_order').click();

        const id = await placed(page);

        await logInAsAdministrator(page);
        await page.goto('/wp-admin/admin.php?page=wc-orders');

        const row = page.locator(`tr#order-${id}, tr#post-${id}`);

        await expect(row).toContainText(`#${id} Erika Mustermann`);
        await expect(row).toContainText('Wartestellung');
        await expect(row).toContainText('24,80');
    });

    test('with a new account: the customer finds the order there, and is sent the way to a password', async ({ page }, testInfo) => {
        const email = address(testInfo, 'konto');

        await add(page, '/produkt/flyer-din-a5/?attribute_pa_papier=250-g');
        await openCheckout(page);
        await fillIn(page, email);
        await page.getByLabel('Ein Konto erstellen?').check();
        await page.locator('#legal').check();
        await page.locator('#place_order').click();

        const id = await placed(page);

        expect(stored(id).customer).toBe(true);

        // Logged in from here on.
        await page.goto('/mein-konto/');

        const menu = page.locator('.woocommerce-MyAccount-navigation');

        await expect(menu.getByRole('link')).toHaveText(['Übersicht', 'Bestellungen', 'Adressen', 'Kontodetails', 'Abmelden']);

        await menu.getByRole('link', { name: 'Bestellungen' }).click();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Bestellungen');

        const order = page.locator('.woocommerce-orders-table tbody tr');

        await expect(order).toHaveCount(1);
        await expect(order).toContainText(`#${id}`);
        await expect(order).toContainText('In Wartestellung');
        expect(await accessibilityViolations(page)).toEqual([]);
        expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBe(0);

        await order.locator('a.view').click();
        await expect(page.locator('.woocommerce-order-details')).toContainText('Flyer DIN A5');
        await expect(page.locator('.woocommerce-order-details')).toContainText('250 g Bilderdruck');

        await page.goto('/mein-konto/edit-address/');
        await expect(page.locator('.woocommerce-Address').first()).toContainText('Musterstraße 1');

        // Two e-mails: the welcome with the way to a password, and the confirmation.
        const sent = sentTo(email);

        expect(sent.map((mail) => mail.subject).sort()).toEqual([`Bestätigung Ihrer Bestellung ${id}`, 'Ihr Konto bei Stiqon wurde erstellt!']);

        const welcome = sent.find((mail) => mail.subject.startsWith('Ihr Konto')).message;
        const link = /href="([^"]+lost-password[^"]+)"/.exec(welcome);

        expect(link).not.toBeNull();

        // The link opens the form for a new password – for someone who is not logged in.
        await page.context().clearCookies();
        await page.goto(link[1].replaceAll('&amp;', '&'));
        await expect(page.locator('#password_1')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Speichern' })).toBeVisible();
    });

    test('it works without JavaScript', async ({ browser, baseURL, isMobile }, testInfo) => {
        const context = await browser.newContext({ javaScriptEnabled: false, baseURL, viewport: isMobile ? { width: 390, height: 844 } : { width: 1440, height: 900 } });
        const page = await context.newPage();
        const email = address(testInfo, 'ohne-skript');

        await add(page, '/produkt/ovale-sticker/');
        await page.goto('/warenkorb/');
        await expect(rows(page)).toHaveCount(1);
        await page.getByRole('link', { name: 'Weiter zur Kasse' }).click();

        await fillIn(page, email);
        await page.locator('#legal').check();
        await page.locator('#place_order').click();

        const id = await placed(page);

        expect(stored(id).items).toEqual([['Ovale Sticker', 'vinyl', '100', 1]]);
        expect(sentTo(email)).toHaveLength(1);

        await context.close();
    });
});

test.describe('the account', () => {
    test('whoever is not logged in can log in or open an account', async ({ page }) => {
        await page.goto('/mein-konto/');

        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Ihr Konto');
        await expect(page.getByRole('heading', { name: 'Anmelden' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Neues Kundenkonto anlegen' })).toBeVisible();

        // The one button of each form looks the part.
        for (const name of ['Anmelden', 'Neues Kundenkonto anlegen']) {
            const colour = await page.getByRole('button', { name, exact: true }).evaluate((button) => getComputedStyle(button).backgroundColor);

            expect(colour, name).toBe('rgb(20, 20, 20)');
        }

        expect(await accessibilityViolations(page)).toEqual([]);
        expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBe(0);

        // A wrong password is answered in the look of the shop, without saying which part was wrong.
        await page.locator('#username').fill('niemand@example.com');
        await page.locator('#password').fill('falsch');
        await page.getByRole('button', { name: 'Anmelden', exact: true }).click();
        await expect(page.locator('ul.woocommerce-error')).toBeVisible();
    });
});

test.describe('what the law asks of every page', () => {
    test('the footer leads to the legal pages and to the form for withdrawing from a contract', async ({ page }) => {
        await page.goto('/');

        const legal = page.locator('footer nav[aria-labelledby="footer-legal"]');

        await expect(legal.locator('ul a')).toHaveText(['AGB', 'Widerrufsbelehrung', 'Versand und Zahlung', 'Datenschutzerklärung', 'Impressum']);

        // One button for withdrawing, the theme's – Germanized's own is not printed besides.
        await expect(page.getByRole('link', { name: 'Vertrag widerrufen' })).toHaveCount(1);
        await legal.getByRole('link', { name: 'Vertrag widerrufen' }).click();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Vertrag widerrufen');
        await expect(page.locator('main form')).toBeVisible();
        expect(await accessibilityViolations(page)).toEqual([]);
    });

    test('a price comes with what belongs to it: VAT, shipping costs, how long delivery takes', async ({ page }) => {
        await page.goto('/produkt/runde-sticker/');

        await expect(page.locator('.summary')).toContainText('inkl. MwSt.');
        await expect(page.locator('.summary .delivery-time-info')).toHaveText('Lieferzeit: 3–5 Werktage (Beispiel)');

        const link = page.locator('.summary').getByRole('link', { name: 'Versandkosten' });

        await expect(link).toHaveAttribute('href', /\/versand-und-zahlung\/$/);

        // Germanized opens it in a new tab, so the product stays where it is.
        await page.goto(await link.getAttribute('href'));
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('Versand und Zahlung');
        await expect(page.getByRole('main')).toContainText('Platzhalter');
    });

    for (const path of ['/agb/', '/widerruf/', '/impressum/', '/datenschutz/']) {
        test(`${path} exists, marked as a text still to come`, async ({ page }) => {
            const response = await page.goto(path);

            expect(response.status()).toBe(200);
            await expect(page.getByRole('main')).toContainText('Platzhalter');
            expect(await accessibilityViolations(page)).toEqual([]);
        });
    }
});
