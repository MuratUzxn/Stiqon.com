<?php
/**
 * What the theme adds to WooCommerce's cart, checkout and e-mails, in cases the
 * example shop does not have and a browser test would have to build first: a
 * variation that leaves an option open, a variation with a name of its own, an
 * order in several parcels, shipping that differs inside the country.
 *
 *   tools/dev/wp.sh eval-file tests/integration/buying.php
 */

$problems = [];

$expect = static function (bool $holds, string $problem) use (&$problems): void {
    if (! $holds) {
        $problems[] = $problem;
    }
};

$variation = static fn (string $sku): ?WC_Product => ($product = wc_get_product(wc_get_product_id_by_sku($sku))) instanceof WC_Product ? $product : null;
$vinyl = $variation('beispiel-runde-sticker-vinyl-5-cm-100');
$kraft = $variation('beispiel-runde-sticker-kraftpapier-5-cm-100');

if ($vinyl === null || $kraft === null) {
    WP_CLI::error('The example assortment has no round sticker on vinyl and on kraft paper, 5 cm, 100 pieces.');
}

$picture = static fn (WC_Product $product, array $chosen): string => (string) apply_filters('woocommerce_cart_item_thumbnail', 'PHOTO', ['data' => $product, 'variation' => $chosen, 'quantity' => 1], 'row');
$asChosen = static fn (string $material): array => ['attribute_pa_material' => $material, 'attribute_pa_groesse' => '5-cm', 'attribute_pa_auflage' => '100'];

// A row of the cart is drawn as it was chosen.
$expect(str_contains($picture($kraft, $asChosen('kraftpapier')), 'Runde Sticker, Kraftpapier'), 'A sticker on kraft paper is not drawn on kraft paper in the cart.');

// A variation can leave an option open ("beliebig"): then the choice is in the row, not in the variation.
$open = $variation('beispiel-runde-sticker-vinyl-5-cm-100');
$open->set_attributes(['pa_material' => '', 'pa_groesse' => '5-cm', 'pa_auflage' => '100']);
$expect(str_contains($picture($open, $asChosen('kraftpapier')), 'Runde Sticker, Kraftpapier'), 'A variation with its material left open is not drawn in the material the customer chose.');
$expect(str_contains($picture($open, $asChosen('holografisch')), 'Runde Sticker, Holografische Folie'), 'A variation with its material left open ignores the choice "holografisch".');

// The same at the checkout, where the drawing comes in through another filter.
$atCheckout = (string) apply_filters('woocommerce_checkout_cart_item_quantity', ' × 1', ['data' => $open, 'variation' => $asChosen('kraftpapier'), 'quantity' => 1], 'row');
$expect(str_contains($atCheckout, 'Runde Sticker, Kraftpapier'), 'At the checkout a variation with its material left open is not drawn in the material the customer chose.');

// Another plugin may drop a variation by handing back false or nothing; the theme passes that on.
$expect(apply_filters('woocommerce_available_variation', false, $kraft->get_parent_id() ? wc_get_product($kraft->get_parent_id()) : null, $kraft) === false, 'A variation another plugin dropped with false breaks or comes back.');
$expect(! array_key_exists('stiqon_net_price', (array) apply_filters('woocommerce_available_variation', [], null, $kraft)), 'A variation another plugin emptied comes back with a net price.');

// Mails keep emoji as text instead of pictures from WordPress.org.
$expect(has_filter('wp_mail', 'wp_staticize_emoji_for_email') === false, 'Emoji in mails become pictures loaded from WordPress.org.');

// What a variation fixes stays fixed, whatever a row claims.
$expect(str_contains($picture($vinyl, $asChosen('kraftpapier')), 'Runde Sticker, Weißes Vinyl'), 'A variation on vinyl is drawn in another material because the row says so.');

// The drawing is named after the product, also when the variation carries a name of its own.
$named = $variation('beispiel-runde-sticker-kraftpapier-5-cm-100');
$named->set_name('Runde Sticker - Kraftpapier, 5 cm, 100 Stück');
$drawn = stiqon_product_view($named);
$expect(str_contains($drawn, 'Runde Sticker, Kraftpapier') && ! str_contains($drawn, 'Runde Sticker - Kraftpapier'), 'The drawing of a variation repeats the options its name carries.');

// The cart lists no cards of WooCommerce's own.
$expect(has_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display') === false, 'The cart still shows WooCommerce\'s list of cross-sells.');

// E-mails list their rows without pictures: those about an order, and Germanized's about a withdrawal.
foreach (['woocommerce_email_order_items_args', 'eu_owb_woocommerce_email_withdrawal_items_args', 'woocommerce_email_fulfillment_items_args'] as $filter) {
    $expect((apply_filters($filter, ['show_image' => true])['show_image'] ?? null) === false, "Rows of e-mails keep their pictures ({$filter}).");
}

// One parcel is "Versand"; several keep the names WooCommerce gives them.
$expect(apply_filters('woocommerce_shipping_package_name', 'Sendung', 0, [], 1) === 'Versand', 'A single parcel is not headed "Versand".');
$expect(apply_filters('woocommerce_shipping_package_name', 'Sendung 1', 0, [], 2) === 'Sendung 1' && apply_filters('woocommerce_shipping_package_name', 'Sendung 2', 1, [], 2) === 'Sendung 2', 'Of two parcels one is not headed as WooCommerce names it.');

// One country, one price everywhere in it: the cart has nothing to add about shipping …
$hint = 'Versandoptionen werden während des Bezahlvorgangs aktualisiert.';
$expect(! stiqon_shipping_differs_within_a_country() && str_contains((string) apply_filters('woocommerce_shipping_estimate_html', $hint), 'stiqon-nothing-to-add'), 'The cart keeps WooCommerce\'s hint although shipping costs the same everywhere.');

// … but says that the price can change where a part of the country is charged differently.
$islands = new WC_Shipping_Zone();
$islands->set_zone_name('Inseln (Prüfung)');
$islands->add_location('25980', 'postcode');
$islands->save();

try {
    $expect(stiqon_shipping_differs_within_a_country() && apply_filters('woocommerce_shipping_estimate_html', $hint) === $hint, 'With a zone drawn by postcodes the cart no longer says that shipping can change at the checkout.');
} finally {
    $islands->delete();
}

if ($problems !== []) {
    WP_CLI::error_multi_line($problems);
    WP_CLI::halt(1);
}

WP_CLI::success('Cart, checkout and e-mails hold in the cases the example shop does not have.');
