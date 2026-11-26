<?php
/**
 * The theme while a plugin it builds on is switched off – by an owner looking
 * for a fault, or after an update that failed. The README promises that pages
 * still render then. This checks what is not a page: the requests WooCommerce
 * answers itself, and what the footer of every page needs.
 *
 *   tools/dev/wp.sh --skip-plugins=stiqon-shop eval-file tests/integration/plugins-off.php shop-plugin
 *   tools/dev/wp.sh --skip-plugins=woocommerce,woocommerce-germanized,stiqon-shop eval-file tests/integration/plugins-off.php woocommerce
 */

$problems = [];

$expect = static function (bool $holds, string $problem) use (&$problems): void {
    if (! $holds) {
        $problems[] = $problem;
    }
};

$state = $args[0] ?? '';

if ($state === 'shop-plugin') {
    if (function_exists('stiqon_product_view') || ! function_exists('WC')) {
        WP_CLI::error('Run this with --skip-plugins=stiqon-shop: the shop plugin has to be off and WooCommerce on.');
    }

    // WooCommerce answers its own requests – the overview the checkout fetches right after
    // loading, the cart's refresh – before a page is drawn, like this command line. The rows
    // then come without drawings; they must not end the request.
    $row = ['data' => wc_get_products(['type' => 'variation', 'limit' => 1])[0] ?? null, 'variation' => [], 'quantity' => 1];

    try {
        $expect(apply_filters('woocommerce_cart_item_thumbnail', 'PHOTO', $row, 'row') === 'PHOTO', 'Without the shop plugin the picture of a row in the cart is not left as WooCommerce made it.');
        // (Germanized adds to this one as well, so it does not come back unchanged.)
        $counted = apply_filters('woocommerce_checkout_cart_item_quantity', '× 1', $row, 'row');
        $expect(is_string($counted) && str_contains($counted, '× 1') && ! str_contains($counted, 'stiqon-order-drawing'), 'Without the shop plugin the row of an order at the checkout gets a place for a drawing that cannot be made.');
    } catch (Throwable $error) {
        $problems[] = 'Without the shop plugin a row of the cart ends the request: '.$error->getMessage();
    }
} elseif ($state === 'woocommerce') {
    if (function_exists('WC')) {
        WP_CLI::error('Run this with --skip-plugins=woocommerce,woocommerce-germanized,stiqon-shop.');
    }

    // Who runs the site and how it treats data has to stay within reach on every page.
    $labels = array_column(stiqon_legal_links(), 'label');
    $expect(in_array('Impressum', $labels, true) && in_array('Datenschutzerklärung', $labels, true), 'Without WooCommerce the footer does not link imprint and privacy policy, but: '.implode(', ', $labels).'.');
    $expect(stiqon_cart_link() === null, 'Without WooCommerce the header still offers a cart.');
} else {
    WP_CLI::error('Say which plugins are off: "shop-plugin" or "woocommerce".');
}

if ($problems !== []) {
    WP_CLI::error_multi_line($problems);
    WP_CLI::halt(1);
}

WP_CLI::success($state === 'shop-plugin' ? 'Without the shop plugin WooCommerce\'s own requests still answer.' : 'Without WooCommerce the footer still names who runs the site.');
