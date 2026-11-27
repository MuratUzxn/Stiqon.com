<?php
/**
 * Sets a local site's shop up the way a shop in Germany is run: country,
 * currency and the German way of writing prices, prices shown including VAT,
 * the two German VAT rates – and what it takes to buy something: the classic
 * cart and checkout pages, shipping within Germany, payment in advance, and
 * the legal pages Germanized links to.
 *
 *   tools/dev/wp.sh eval-file tools/dev/shop-settings.php
 *
 * This is for development and test installations. On the real shop these are
 * the owner's settings (WooCommerce › Einstellungen); the go-live check will
 * look at them. Amounts, bank details and legal texts here are examples and
 * say so.
 *
 * Running it again puts the same settings in place; pages and tax rates that
 * exist already are used, not made twice. It refuses to run anywhere but on a
 * local site (WP_ENVIRONMENT_TYPE "local", which tools/dev/setup-wordpress.sh sets).
 */

if (! class_exists(WC_Tax::class)) {
    WP_CLI::error('WooCommerce is not active.');
}

if (wp_get_environment_type() !== 'local') {
    WP_CLI::error('This sets up a local test shop and overwrites settings, pages and tax rates. It runs only where WP_ENVIRONMENT_TYPE is "local".');
}

$options = [
    'woocommerce_default_country' => 'DE',
    'woocommerce_currency' => 'EUR',
    // "29,90 €", not "€29.90".
    'woocommerce_currency_pos' => 'right_space',
    'woocommerce_price_thousand_sep' => '.',
    'woocommerce_price_decimal_sep' => ',',
    'woocommerce_price_num_decimals' => '2',
    // Prices are entered and shown including VAT, as consumers must see them.
    'woocommerce_calc_taxes' => 'yes',
    'woocommerce_prices_include_tax' => 'yes',
    'woocommerce_tax_display_shop' => 'incl',
    'woocommerce_tax_display_cart' => 'incl',
    // The shop is open in a local installation; "coming soon" is tested on purpose.
    'woocommerce_coming_soon' => 'no',
    // Sold and shipped within Germany for now.
    'woocommerce_allowed_countries' => 'specific',
    'woocommerce_specific_allowed_countries' => ['DE'],
    'woocommerce_ship_to_countries' => '',
    // Private and business customers: a company may be named, nobody has to.
    'woocommerce_checkout_company_field' => 'optional',
    // Buying without an account stays possible. Who wants one gets it while ordering
    // or on the account page; who has one can log in at the checkout. The user name
    // is made from the e-mail address, and the password is set through a link in
    // the welcome mail – one field less to fill in.
    'woocommerce_enable_guest_checkout' => 'yes',
    'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
    'woocommerce_enable_checkout_login_reminder' => 'yes',
    'woocommerce_enable_myaccount_registration' => 'yes',
    'woocommerce_registration_generate_username' => 'yes',
    'woocommerce_registration_generate_password' => 'yes',
    // One country, one price for shipping: nothing to calculate in the cart.
    'woocommerce_enable_shipping_calc' => 'no',
    // Where the shop is: an example, as the foot of every e-mail shows it.
    'woocommerce_store_address' => 'Beispielstraße 1',
    'woocommerce_store_city' => 'Beispielstadt',
    'woocommerce_store_postcode' => '12345',
    // E-mails greet with the name. Germanized's own greeting needs a form of
    // address ("Sehr geehrte Frau …") that the checkout does not ask for.
    'woocommerce_gzd_email_title_text' => 'Guten Tag {first_name} {last_name},',
    // WooCommerce gives a new shop its current e-mail design – at the first visit
    // to the administration, which a site built by this script has not had. Without
    // this the first mails of a site look and read differently from all later ones.
    'woocommerce_feature_email_improvements_enabled' => 'yes',
    // Not for search engines.
    'blog_public' => '0',
];

foreach ($options as $option => $value) {
    update_option($option, $value);
}

// The standard rate, and the reduced one in WooCommerce's class for it.
$reduced = array_values(array_filter(WC_Tax::get_tax_class_slugs(), static fn (string $slug): bool => str_contains($slug, 'erm') || str_contains($slug, 'reduced')))[0] ?? null;

// Pairs, not keys: without a reduced class $reduced is null, and as a key it
// would turn into '' and overwrite the standard rate.
foreach ([['', '19.0000'], [$reduced, '7.0000']] as [$class, $rate]) {
    if ($class === null || WC_Tax::get_rates_for_tax_class((string) $class) !== []) {
        continue;
    }

    WC_Tax::_insert_tax_rate([
        'tax_rate_country' => 'DE',
        'tax_rate_state' => '',
        'tax_rate' => $rate,
        'tax_rate_name' => 'MwSt.',
        'tax_rate_priority' => 1,
        'tax_rate_compound' => 0,
        'tax_rate_shipping' => 1,
        'tax_rate_order' => 0,
        'tax_rate_class' => (string) $class,
    ]);
}

// --- Cart and checkout: the classic pages ---------------------------------------------
// WooCommerce creates both as block pages. The theme draws the classic ones, which
// weigh a seventh and can be given the shop's look through templates.

foreach (['cart' => 'woocommerce_cart', 'checkout' => 'woocommerce_checkout'] as $page => $shortcode) {
    $id = wc_get_page_id($page);

    if ($id > 0 && ! has_shortcode((string) get_post_field('post_content', $id), $shortcode)) {
        wp_update_post(['ID' => $id, 'post_content' => "<!-- wp:shortcode -->[{$shortcode}]<!-- /wp:shortcode -->"]);
    }
}

// --- Shipping: one zone, one price -----------------------------------------------------
// With Germanized's way of taxing shipping the cost is entered the way prices are: gross.

$zones = array_column(WC_Shipping_Zones::get_zones(), 'zone_name');

if (! in_array('Deutschland', $zones, true)) {
    $zone = new WC_Shipping_Zone();
    $zone->set_zone_name('Deutschland');
    $zone->add_location('DE', 'country');
    $zone->save();

    $instance = $zone->add_shipping_method('flat_rate');

    update_option("woocommerce_flat_rate_{$instance}_settings", ['title' => 'Standardversand (Beispielpreis)', 'tax_status' => 'taxable', 'cost' => '4.90']);
}

// --- How long delivery takes ---------------------------------------------------------------
// A shop has to say it at the product, in the cart and at the checkout. Germanized
// prints the time set on a product, or this one for all that have none.

if (! get_option('woocommerce_gzd_default_delivery_time') && taxonomy_exists('product_delivery_time')) {
    $time = term_exists('3–5 Werktage (Beispiel)', 'product_delivery_time') ?: wp_insert_term('3–5 Werktage (Beispiel)', 'product_delivery_time', ['slug' => '3-5-werktage-beispiel']);

    if (! is_wp_error($time)) {
        update_option('woocommerce_gzd_default_delivery_time', (string) $time['term_id']);
    }
}

// --- Payment in advance ------------------------------------------------------------------

if ((get_option('woocommerce_bacs_settings')['enabled'] ?? 'no') !== 'yes') {
    update_option('woocommerce_bacs_settings', [
        'enabled' => 'yes',
        'title' => 'Vorkasse (Überweisung)',
        'description' => 'Sie überweisen den Betrag nach der Bestellung. Gedruckt wird, sobald das Geld eingegangen ist.',
        'instructions' => 'Bitte überweisen Sie den Betrag unter Angabe der Bestellnummer auf das folgende Konto.',
        'account_details' => '',
    ]);
    update_option('woocommerce_bacs_accounts', [[
        'account_name' => 'Stiqon (Beispielkonto)',
        'account_number' => '',
        'bank_name' => 'Beispielbank',
        'sort_code' => '',
        'iban' => 'DE00 0000 0000 0000 0000 00',
        'bic' => 'XXXXDEXX',
    ]]);
}

// --- Legal pages -------------------------------------------------------------------------
// Germanized links to them from prices, the checkout and e-mails. The texts are the
// owner's to supply; until then each page says that it is a placeholder.

$placeholder = static fn (string $what): string => '<!-- wp:paragraph --><p><strong>Platzhalter.</strong> Hier steht vor dem Start des Shops '.$what.' – geprüft von einer Anwältin, einem Anwalt oder einem Rechtstext-Dienst.</p><!-- /wp:paragraph -->';

$legal = [
    'agb' => ['AGB', 'der Text der Allgemeinen Geschäftsbedingungen', ['woocommerce_terms_page_id']],
    'widerruf' => ['Widerrufsbelehrung', 'die Widerrufsbelehrung mit dem Muster-Widerrufsformular', ['woocommerce_revocation_page_id']],
    'impressum' => ['Impressum', 'das Impressum mit den Angaben zum Unternehmen', ['woocommerce_imprint_page_id']],
    'datenschutz' => ['Datenschutzerklärung', 'die Datenschutzerklärung', ['woocommerce_data_security_page_id', 'wp_page_for_privacy_policy']],
    'versand-und-zahlung' => ['Versand und Zahlung', 'die Übersicht der Versandkosten, Lieferzeiten und Zahlungsarten', ['woocommerce_shipping_costs_page_id', 'woocommerce_payment_methods_page_id']],
];

foreach ($legal as $slug => [$title, $what, $settings]) {
    $page = get_page_by_path($slug);
    $id = $page instanceof WP_Post ? $page->ID : wp_insert_post(['post_type' => 'page', 'post_name' => $slug, 'post_title' => $title, 'post_status' => 'publish', 'post_content' => $placeholder($what)], true);

    if (is_wp_error($id)) {
        WP_CLI::error("Page {$title}: ".$id->get_error_message());
    }

    foreach ($settings as $setting) {
        update_option($setting, (int) $id);
    }
}

// The page with Germanized's form for withdrawing from a contract exists as a draft.
$withdrawal = (int) get_option('woocommerce_withdraw_from_contract_page_id');

if ($withdrawal > 0 && get_post_status($withdrawal) === 'draft') {
    wp_update_post(['ID' => $withdrawal, 'post_status' => 'publish']);
}

WP_CLI::success('Shop settings for Germany are in place.');
