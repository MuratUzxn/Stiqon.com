<?php

namespace Stiqon\Shop\Catalog;

/**
 * What a chosen variation is called in the cart, at the checkout, in orders
 * and in e-mails.
 *
 * WooCommerce writes the choice into the name of a product with one or two
 * options ("Flyer DIN A6 - 250 g Bilderdruck, 100 Stück") and lists it below
 * the name of a product with more ("Runde Sticker", then material, size and
 * quantity). The shop lists it for every product alike: a row is called what
 * its product is called, and what was chosen stands beside it, option by
 * option.
 *
 * WooCommerce stores the name with a variation when it is saved. One that was
 * saved before this rule was in place keeps its longer name until it is saved
 * again.
 */
final class Names
{
    public static function register(): void
    {
        add_filter('woocommerce_product_variation_title_include_attributes', '__return_false');
    }
}
