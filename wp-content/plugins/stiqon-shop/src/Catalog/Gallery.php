<?php

namespace Stiqon\Shop\Catalog;

use WC_Product;

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
     *     cards: list<array{key: string, product: WC_Product, slug: string, title: string, summary: string, option: ?string, subtitle: ?string, chosen: array<string, string>, url: string, price: ?float}>,
     *     examples: bool
     * }
     */
    public static function of(string $category): array
    {
        $products = self::products($category);

        $gallery = ['products' => [], 'axis' => null, 'cards' => [], 'examples' => false];
        $axes = [];

        foreach ($products as $product) {
            // Stand-ins from tools/dev/example-catalogue.php; the page says so.
            $gallery['examples'] = $gallery['examples'] || (bool) $product->get_meta('_stiqon_example');

            $visible = ProductView::visible($product);

            $gallery['products'][] = ['slug' => $product->get_slug(), 'name' => $product->get_name()];

            array_push($gallery['cards'], ...self::cards($product, $visible));

            if ($visible !== null) {
                $key = $visible['key'];
                $axes[$key] ??= ['key' => $key, 'parameter' => self::parameter($key), 'label' => $visible['label'], 'options' => []];

                foreach ($visible['options'] as $option) {
                    $axes[$key]['options'][$option['slug']] = ['slug' => $option['slug'], 'name' => $option['name']];
                }
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
     * The first cards of other products of the same area, for "more of these"
     * beside a product. Only as many products are read as cards are wanted.
     *
     * @return list<array{key: string, product: WC_Product, slug: string, title: string, summary: string, option: ?string, subtitle: ?string, chosen: array<string, string>, url: string, price: ?float}>
     */
    public static function beside(WC_Product $product, string $category, int $limit = 4): array
    {
        $cards = [];

        foreach (self::products($category, ['limit' => max(1, $limit), 'exclude' => [$product->get_id()]]) as $other) {
            $cards[] = self::cards($other, ProductView::visible($other))[0];
        }

        return $cards;
    }

    /**
     * The products of an area, in the shop's order: published, listed in the
     * catalogue, and open to everyone – a product behind a password is for
     * whoever was given its address.
     *
     * @param  array<string, mixed>  $more  further arguments for wc_get_products()
     * @return list<WC_Product>
     */
    private static function products(string $category, array $more = []): array
    {
        $products = wc_get_products($more + [
            'category' => [$category],
            'status' => 'publish',
            'visibility' => 'catalog',
            'has_password' => false,
            'limit' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ]);

        return is_array($products) ? array_values($products) : [];
    }

    /**
     * The cards of one product: one for each value of the option that can be
     * seen, or a single card for a product that always looks the same.
     *
     * @param  array{key: string, label: string, options: list<array{slug: string, name: string}>}|null  $visible  what ProductView::visible() says about the product
     * @return list<array{key: string, product: WC_Product, slug: string, title: string, summary: string, option: ?string, subtitle: ?string, chosen: array<string, string>, url: string, price: ?float}>
     */
    public static function cards(WC_Product $product, ?array $visible): array
    {
        $prices = self::lowestPrices($product, $visible['key'] ?? null);
        $slug = $product->get_slug();

        $card = [
            'key' => $slug,
            'product' => $product,
            'slug' => $slug,
            'title' => $product->get_name(),
            'summary' => trim(wp_strip_all_tags($product->get_short_description())),
            'option' => null,
            'subtitle' => null,
            'chosen' => [],
            'url' => $product->get_permalink(),
            'price' => $prices[''] ?? null,
        ];

        if ($visible === null) {
            return [$card];
        }

        $cards = [];

        foreach ($visible['options'] as $option) {
            $cards[] = [
                'key' => $slug.'--'.$option['slug'],
                'option' => $option['slug'],
                'subtitle' => $option['name'],
                'chosen' => ['attribute_'.$visible['key'] => $option['slug']],
                'url' => add_query_arg('attribute_'.$visible['key'], rawurlencode($option['slug']), $card['url']),
                'price' => $prices[$option['slug']] ?? $card['price'],
            ] + $card;
        }

        return $cards;
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
            $option = $axis === null ? '' : (string) get_post_meta((int) $variation, 'attribute_'.$axis, true);

            $lowest[$option] = min($lowest[$option] ?? INF, (float) $price);
        }

        return $lowest;
    }
}
