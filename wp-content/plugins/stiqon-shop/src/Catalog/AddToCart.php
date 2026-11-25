<?php

namespace Stiqon\Shop\Catalog;

/**
 * Two things around putting a product with options into the cart.
 *
 * Without script: WooCommerce works out in the browser which variation the
 * chosen options make and sends its number along. Without script only the
 * options arrive, and WooCommerce (as of 11.3) answers "please choose product
 * options". So the variation is looked up here, before WooCommerce handles the
 * request.
 *
 * Afterwards: WooCommerce answers the form with the product page itself.
 * Loading that page again would send the form again. So the visitor is sent
 * back to the product's address with the choice in it – the page looks the
 * same, the message is the same, and reloading is harmless.
 */
final class AddToCart
{
    public static function register(): void
    {
        // WooCommerce's own handler runs on the same hook at priority 20.
        add_action('wp_loaded', [self::class, 'resolveVariation'], 15);
        add_filter('woocommerce_add_to_cart_redirect', [self::class, 'backToProduct'], 10, 2);
    }

    /**
     * @param  string|false  $url  where WooCommerce or another extension wants to go – that wins
     * @param  mixed  $product  the product that was added
     * @return string|false
     */
    public static function backToProduct($url, $product = null)
    {
        if ($url || ! $product instanceof \WC_Product || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || wp_doing_ajax()) {
            return $url;
        }

        // With the setting "go to the cart after adding", WooCommerce redirects there itself.
        if (get_option('woocommerce_cart_redirect_after_add') === 'yes') {
            return $url;
        }

        $parent = $product->is_type('variation') ? wc_get_product($product->get_parent_id()) : $product;

        if (! $parent instanceof \WC_Product) {
            return $url;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the request was handled by WooCommerce; values are only compared with what the product offers.
        $chosen = Choice::of($parent, wp_unslash($_POST));

        return add_query_arg(array_map('rawurlencode', $chosen), $parent->get_permalink());
    }

    public static function resolveVariation(): void
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- WooCommerce's add-to-cart request carries no nonce; nothing is changed here but the request itself.
        if (! isset($_REQUEST['add-to-cart']) || ! is_numeric($_REQUEST['add-to-cart']) || ! empty($_REQUEST['variation_id'])) {
            return;
        }

        $product = wc_get_product(absint($_REQUEST['add-to-cart']));

        if (! $product || ! $product->is_type('variable')) {
            return;
        }

        $chosen = [];

        foreach (array_keys(Choice::offered($product)) as $key) {
            $value = $_REQUEST['attribute_'.$key] ?? null;

            // Only a complete choice names a variation; anything less stays WooCommerce's to refuse.
            if (! is_string($value) || $value === '') {
                return;
            }

            $chosen['attribute_'.$key] = wp_unslash($value);
        }
        // phpcs:enable

        $variation = Choice::variation($product, $chosen);

        if ($variation !== null) {
            $_REQUEST['variation_id'] = $_POST['variation_id'] = (string) $variation->get_id();
        }
    }
}
