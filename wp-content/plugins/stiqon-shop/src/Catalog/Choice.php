<?php

namespace Stiqon\Shop\Catalog;

use WC_Data_Store;
use WC_Product;
use WC_Product_Variation;

/**
 * Which options of a product are chosen, and which variation that makes.
 *
 * A complete choice always exists: for each option the request's value if the
 * product offers it, otherwise the product's preselected one, otherwise its
 * first. Not every combination has to exist as a variation – a flyer may come
 * as A6 on 250 g and as A5 on 135 g only. Then the choice moves to the nearest
 * combination that does (see closest()). So a product page always shows one
 * concrete variation with its price.
 */
final class Choice
{
    /**
     * @param  array<string, mixed>  $request  the request's parameters; values are only compared with what the product offers
     * @return array<string, string>  "attribute_pa_material" => "vinyl", one entry per option of the product
     */
    public static function of(WC_Product $product, array $request = []): array
    {
        if (! $product->is_type('variable')) {
            return [];
        }

        $combinations = self::combinations($product);
        $defaults = $product->get_default_attributes();
        $chosen = [];
        $asked = [];

        foreach (self::offered($product, $combinations) as $key => $values) {
            $name = 'attribute_'.$key;
            $chosen[$name] = $values[0];

            foreach ([$request[$name] ?? null, $defaults[$key] ?? null] as $position => $value) {
                if (is_string($value) && in_array($value, $values, true)) {
                    $chosen[$name] = $value;

                    // What a visitor asked for counts more than what the product preselects.
                    if ($position === 0) {
                        $asked[] = $name;
                    }

                    break;
                }
            }
        }

        return self::closest($combinations, $chosen, $asked);
    }

    /**
     * The choice nearest to the wanted one that exists as a variation: the
     * wanted one itself if a variation fits it; otherwise the first variation
     * that keeps most of it, with the options in $fixed counting more than all
     * others together.
     *
     * The product page's script follows the same rule when a visitor chooses an
     * option (assets/src/js/variation-match.js in the theme). Both are held to
     * the cases in tests/fixtures/choice-cases.json.
     *
     * @param  list<array<string, string>>  $combinations  per variation: option => value, '' for "any value"
     * @param  array<string, string>  $wanted  a complete choice
     * @param  list<string>  $fixed  options whose value should stay if at all possible
     * @return array<string, string>
     */
    public static function closest(array $combinations, array $wanted, array $fixed = []): array
    {
        $heavy = count($wanted) + 1;
        $best = null;
        $most = -1;

        foreach ($combinations as $combination) {
            $kept = 0;

            foreach ($wanted as $name => $value) {
                $offered = $combination[$name] ?? '';

                if ($offered === '' || $offered === $value) {
                    $kept += in_array($name, $fixed, true) ? $heavy : 1;
                }
            }

            if ($kept > $most) {
                $best = $combination;
                $most = $kept;
            }
        }

        if ($best === null) {
            return $wanted;
        }

        foreach ($wanted as $name => $value) {
            if (($best[$name] ?? '') !== '') {
                $wanted[$name] = $best[$name];
            }
        }

        return $wanted;
    }

    /**
     * The variation that matches a complete choice, if there is one for sale.
     *
     * @param  array<string, string>  $chosen
     */
    public static function variation(WC_Product $product, array $chosen): ?WC_Product_Variation
    {
        if (! $product->is_type('variable') || $chosen === []) {
            return null;
        }

        $id = WC_Data_Store::load('product')->find_matching_product_variation($product, $chosen);
        $variation = $id ? wc_get_product($id) : null;

        return $variation instanceof WC_Product_Variation ? $variation : null;
    }

    /**
     * What can be bought of the product per option, in the order shown on the
     * product page: the values ticked on the product that a variation for sale
     * uses. A value that is ticked but has no such variation – none was made
     * for it, or it is switched off or has no price – is not offered.
     *
     * @param  list<array<string, string>>|null  $combinations  what combinations() returned for the product, if already at hand
     * @return array<string, list<string>>  attribute key ("pa_material") => values (term slugs, or the texts of a custom attribute)
     */
    public static function offered(WC_Product $product, ?array $combinations = null): array
    {
        $combinations ??= self::combinations($product);
        $offered = [];

        foreach (self::ticked($product) as $key => $values) {
            $used = array_column($combinations, 'attribute_'.$key);

            // A variation that leaves the option open is for sale with each of its values.
            if (! in_array('', $used, true)) {
                $values = array_values(array_filter($values, static fn (string $value): bool => in_array($value, $used, true)));
            }

            if ($values !== []) {
                $offered[$key] = $values;
            }
        }

        return $offered;
    }

    /**
     * What the product's variations for sale combine, in the product's order.
     *
     * For sale means what WooCommerce offers on the product page: the variation
     * is switched on, has a price and – where the shop hides what is out of
     * stock – is in stock.
     *
     * @return list<array<string, string>>  per variation: "attribute_pa_material" => "vinyl"; '' stands for "any value"
     */
    public static function combinations(WC_Product $product): array
    {
        if (! $product instanceof \WC_Product_Variable) {
            return [];
        }

        $priced = $product->get_variation_prices()['price'] ?? [];
        $ids = array_values(array_filter($product->get_visible_children(), static fn ($id): bool => isset($priced[$id])));
        $keys = array_keys(self::ticked($product));
        $combinations = [];

        // One query for the options of all variations instead of one each.
        update_meta_cache('post', $ids);

        foreach ($ids as $id) {
            $combination = [];

            foreach ($keys as $key) {
                $combination['attribute_'.$key] = (string) get_post_meta((int) $id, 'attribute_'.$key, true);
            }

            $combinations[] = $combination;
        }

        return $combinations;
    }

    /**
     * The values ticked on the product per option used for variations, in the
     * order set for the attribute.
     *
     * @return array<string, list<string>>
     */
    private static function ticked(WC_Product $product): array
    {
        $ticked = [];

        foreach ($product->get_attributes() as $key => $attribute) {
            if (! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_variation()) {
                continue;
            }

            $values = $attribute->is_taxonomy()
                ? wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'slugs'])
                : array_map('strval', $attribute->get_options());

            $values = array_values(array_filter(is_array($values) ? $values : [], 'strlen'));

            if ($values !== []) {
                $ticked[(string) $key] = $values;
            }
        }

        return $ticked;
    }
}
