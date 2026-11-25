<?php

namespace Stiqon\Shop\Catalog;

use WC_Product;
use WC_Product_Attribute;

/**
 * The cards of an area of the shop: one per product and per option that can be
 * seen – a sticker's material, a flyer's paper. "Runde Sticker" on four
 * materials are four cards, each opening the product with that material chosen.
 *
 * This class gathers the data; the theme draws the cards.
 */
final class Gallery
{
    /**
     * @return array{
     *     products: list<array{slug: string, name: string}>,
     *     axis: array{key: string, parameter: string, label: string, options: list<array{slug: string, name: string}>}|null,
     *     cards: list<array{key: string, product: WC_Product, slug: string, title: string, option: ?string, subtitle: ?string, chosen: array<string, string>, url: string, price: ?float}>,
     *     examples: bool
     * }
     */
    public static function of(string $category): array
    {
        $products = wc_get_products([
            'category' => [$category],
            'status' => 'publish',
            'visibility' => 'catalog',
            'limit' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ]);

        $gallery = ['products' => [], 'axis' => null, 'cards' => [], 'examples' => false];
        $axes = [];

        foreach ($products as $product) {
            // Stand-ins from tools/dev/example-catalogue.php; the page says so.
            $gallery['examples'] = $gallery['examples'] || (bool) $product->get_meta('_stiqon_example');

            $axis = self::axis($product);
            $prices = self::lowestPrices($product, $axis?->get_name());
            $slug = $product->get_slug();

            $gallery['products'][] = ['slug' => $slug, 'name' => $product->get_name()];

            if ($axis === null) {
                $gallery['cards'][] = [
                    'key' => $slug,
                    'product' => $product,
                    'slug' => $slug,
                    'title' => $product->get_name(),
                    'option' => null,
                    'subtitle' => null,
                    'chosen' => [],
                    'url' => $product->get_permalink(),
                    'price' => $prices[''] ?? null,
                ];

                continue;
            }

            $key = sanitize_title($axis->get_name());
            $axes[$key] ??= ['key' => $key, 'parameter' => self::parameter($key), 'label' => wc_attribute_label($axis->get_name(), $product), 'options' => []];

            foreach (self::options($product, $axis) as $option) {
                $axes[$key]['options'][$option['slug']] = $option;

                $gallery['cards'][] = [
                    'key' => $slug.'--'.$option['slug'],
                    'product' => $product,
                    'slug' => $slug,
                    'title' => $product->get_name(),
                    'option' => $option['slug'],
                    'subtitle' => $option['name'],
                    'chosen' => ['attribute_'.$key => $option['slug']],
                    'url' => add_query_arg('attribute_'.$key, rawurlencode($option['slug']), $product->get_permalink()),
                    'price' => $prices[$option['slug']] ?? $prices[''] ?? null,
                ];
            }
        }

        // Options can only be offered as one filter when every product varies in the same way.
        if (count($axes) === 1) {
            $axis = array_values($axes)[0];
            $axis['options'] = array_values($axis['options']);

            $gallery['axis'] = $axis;
        }

        return $gallery;
    }

    /**
     * Which of the gallery's filters a request asks for. Anything that is not
     * one of the gallery's own products or options is ignored.
     *
     * @param  array<string, mixed>  $query  the request's parameters
     * @return array{produkt: ?string, option: ?string}
     */
    public static function selection(array $gallery, array $query): array
    {
        $pick = static function (mixed $value, array $known): ?string {
            return is_string($value) && in_array($value, array_column($known, 'slug'), true) ? $value : null;
        };

        return [
            'produkt' => $pick($query['produkt'] ?? null, $gallery['products']),
            'option' => $gallery['axis'] === null ? null : $pick($query[self::parameter($gallery['axis']['key'])] ?? null, $gallery['axis']['options']),
        ];
    }

    /**
     * Name of the request parameter for an attribute: "pa_material" is asked for as "material".
     */
    public static function parameter(string $key): string
    {
        return preg_replace('/^pa_/', '', $key) ?? $key;
    }

    /**
     * The attribute a visitor can see on the product: the first one used for
     * variations whose options change the drawing.
     */
    private static function axis(WC_Product $product): ?WC_Product_Attribute
    {
        $view = ProductView::resolve($product);

        if ($view === null || ! $product->is_type('variable')) {
            return null;
        }

        foreach ($product->get_attributes() as $attribute) {
            if (! $attribute instanceof WC_Product_Attribute || ! $attribute->get_variation() || ! $attribute->is_taxonomy()) {
                continue;
            }

            foreach (self::options($product, $attribute) as $option) {
                if (Guess::option($view['type'], $option['name']) !== [] || Spec::known($view['type'], $option['stored']) !== []) {
                    return $attribute;
                }
            }
        }

        return null;
    }

    /**
     * @return list<array{slug: string, name: string, stored: array<string, mixed>}>
     */
    private static function options(WC_Product $product, WC_Product_Attribute $attribute): array
    {
        $terms = wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'all']);
        $options = [];

        foreach (is_array($terms) ? $terms : [] as $term) {
            $stored = get_term_meta($term->term_id, ProductView::TERM_META, true);

            $options[] = ['slug' => $term->slug, 'name' => $term->name, 'stored' => is_array($stored) ? $stored : []];
        }

        return $options;
    }

    /**
     * The lowest price per option of the axis, as the shop displays prices. The
     * key '' holds the lowest price of variations that are not tied to one option
     * (and the price of a product without variations).
     *
     * @return array<string, float>
     */
    private static function lowestPrices(WC_Product $product, ?string $axis): array
    {
        if (! $product->is_type('variable')) {
            $price = $product->get_price();

            return $price === '' ? [] : ['' => (float) wc_get_price_to_display($product)];
        }

        $prices = $product->get_variation_prices(true)['price'] ?? [];
        $lowest = [];

        // One query for the attributes of all variations instead of one each.
        update_meta_cache('post', array_keys($prices));

        foreach ($prices as $variation => $price) {
            $option = $axis === null ? '' : (string) get_post_meta((int) $variation, 'attribute_'.sanitize_title($axis), true);

            $lowest[$option] = min($lowest[$option] ?? INF, (float) $price);
        }

        return $lowest;
    }
}
