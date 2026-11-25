<?php

namespace Stiqon\Shop\Catalog;

use WC_Product;
use WP_Term;

/**
 * The areas of the shop: the top-level product categories that have a page
 * showing their products – "Sticker", "Flyer".
 *
 * Which page that is, the theme says. A category without one is not an area:
 * search does not offer it, and no product is filed under it.
 */
final class Areas
{
    /**
     * Address of the page that shows a category's products; null if none does.
     */
    public static function url(WP_Term $term): ?string
    {
        $archive = get_term_link($term);

        /**
         * Address of an area of the shop. A theme that shows the products of a
         * category on a page of its own names that page here – and returns an
         * empty string for a category it has no page for.
         *
         * @param  string  $url  address of the category's archive
         * @param  WP_Term  $term  the product category
         */
        $url = apply_filters('stiqon_shop_area_url', is_string($archive) ? $archive : '', $term);

        return is_string($url) && $url !== '' ? $url : null;
    }

    /**
     * The areas a product belongs to: the top-level categories above its
     * categories that have a page. The category WooCommerce files products
     * under that have none is never one.
     *
     * @return list<WP_Term>
     */
    public static function of(WC_Product $product): array
    {
        $terms = get_the_terms($product->get_id(), 'product_cat');
        $areas = [];

        foreach (is_array($terms) ? $terms : [] as $term) {
            while ($term instanceof WP_Term && $term->parent > 0) {
                $term = get_term($term->parent, 'product_cat');
            }

            if (! $term instanceof WP_Term || isset($areas[$term->term_id]) || $term->term_id === (int) get_option('default_product_cat')) {
                continue;
            }

            if (self::url($term) !== null) {
                $areas[$term->term_id] = $term;
            }
        }

        return array_values($areas);
    }
}
