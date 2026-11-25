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
 * first. So a product page always shows one concrete variation with its price.
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

        $defaults = $product->get_default_attributes();
        $chosen = [];

        foreach (self::offered($product) as $key => $values) {
            $wanted = [$request['attribute_'.$key] ?? null, $defaults[$key] ?? null];
            $chosen['attribute_'.$key] = $values[0];

            foreach ($wanted as $value) {
                if (is_string($value) && in_array($value, $values, true)) {
                    $chosen['attribute_'.$key] = $value;

                    break;
                }
            }
        }

        return $chosen;
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
     * What the product offers per option, in the order shown on the product page.
     *
     * @return array<string, list<string>>  attribute key ("pa_material") => values (term slugs, or the texts of a custom attribute)
     */
    public static function offered(WC_Product $product): array
    {
        $offered = [];

        foreach ($product->get_attributes() as $key => $attribute) {
            if (! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_variation()) {
                continue;
            }

            $values = $attribute->is_taxonomy()
                ? wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'slugs'])
                : array_map('strval', $attribute->get_options());

            $values = array_values(array_filter(is_array($values) ? $values : [], 'strlen'));

            if ($values !== []) {
                $offered[$key] = $values;
            }
        }

        return $offered;
    }
}
