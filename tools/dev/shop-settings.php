<?php
/**
 * Sets a local site's shop up the way a shop in Germany is run: country,
 * currency and the German way of writing prices, prices shown including VAT,
 * and the two German VAT rates.
 *
 *   tools/dev/wp.sh eval-file tools/dev/shop-settings.php
 *
 * This is for development and test installations. On the real shop these are
 * the owner's settings (WooCommerce › Einstellungen); the go-live check will
 * look at them.
 */

if (! class_exists(WC_Tax::class)) {
    WP_CLI::error('WooCommerce is not active.');
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
    // Not for search engines.
    'blog_public' => '0',
];

foreach ($options as $option => $value) {
    update_option($option, $value);
}

// The standard rate, and the reduced one in WooCommerce's class for it.
$reduced = array_values(array_filter(WC_Tax::get_tax_class_slugs(), static fn (string $slug): bool => str_contains($slug, 'erm') || str_contains($slug, 'reduced')))[0] ?? null;

foreach (['' => '19.0000', $reduced => '7.0000'] as $class => $rate) {
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

WP_CLI::success('Shop settings for Germany are in place.');
