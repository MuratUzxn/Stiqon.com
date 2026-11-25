<?php

namespace Stiqon\Shop\Catalog;

use WC_Product;
use WC_Product_Attribute;

/**
 * Reads a WooCommerce product and a choice of its options, and says how to draw it.
 *
 * What is stored for the drawing:
 *
 *   on the product, meta "_stiqon_mockup":  ['type' => 'sticker', 'shape' => 'circle', 'motif' => 'siegel']
 *   on an attribute's term, meta "stiqon_mockup":  ['material' => 'kraft', 'finish' => 'matte']
 *
 * Both are optional – without them the names decide (see Guess).
 */
final class ProductView
{
    public const META = '_stiqon_mockup';

    public const TERM_META = 'stiqon_mockup';

    /**
     * @param  array<string, string>  $chosen  attribute => value, keyed the way WooCommerce keys
     *                                         them in a request ("pa_material" or "attribute_pa_material"),
     *                                         valued by term slug (or by the text of a custom attribute)
     * @return array{type: string, spec: array<string, string|bool>, options: list<string>}|null
     */
    public static function resolve(WC_Product $product, array $chosen = []): ?array
    {
        $parent = $product;

        if ($product->is_type('variation')) {
            $parent = wc_get_product($product->get_parent_id());

            if (! $parent instanceof WC_Product) {
                return null;
            }

            // A variation is a choice already; anything passed in refines it.
            $chosen += array_filter($product->get_attributes(), 'is_string');
        }

        $options = [];

        foreach ($parent->get_attributes() as $key => $attribute) {
            if (! $attribute instanceof WC_Product_Attribute) {
                continue;
            }

            // In this order: what is asked for, what the product preselects, its first option.
            $wanted = array_filter([$chosen[$key] ?? $chosen['attribute_'.$key] ?? null, $parent->get_default_attributes()[$key] ?? null], 'is_string');
            $option = self::option($parent, $attribute, array_values($wanted));

            if ($option !== null) {
                $options[] = $option;
            }
        }

        $categories = wp_get_post_terms($parent->get_id(), 'product_cat', ['fields' => 'names']);
        $stored = $parent->get_meta(self::META);

        $resolved = Spec::resolve(
            [
                'name' => $parent->get_name(),
                'categories' => is_array($categories) ? array_values($categories) : [],
                'stored' => is_array($stored) ? $stored : [],
            ],
            $options,
        );

        return $resolved === null ? null : $resolved + ['options' => array_column($options, 'name')];
    }

    /**
     * The option of a product that a visitor can see in the drawing: the first
     * one used for variations whose values change it – a sticker's material, a
     * flyer's paper. Null for a product that looks the same whatever is chosen.
     *
     * @return array{key: string, name: string, label: string, options: list<array{slug: string, name: string, stored: array<string, mixed>}>}|null
     */
    public static function visible(WC_Product $product): ?array
    {
        $view = self::resolve($product);

        if ($view === null || ! $product->is_type('variable')) {
            return null;
        }

        foreach ($product->get_attributes() as $key => $attribute) {
            if (! $attribute instanceof WC_Product_Attribute || ! $attribute->get_variation() || ! $attribute->is_taxonomy()) {
                continue;
            }

            $options = self::options($product, $attribute);

            foreach ($options as $option) {
                if (Guess::option($view['type'], $option['name']) !== [] || Spec::known($view['type'], $option['stored']) !== []) {
                    return [
                        'key' => (string) $key,
                        'name' => $attribute->get_name(),
                        'label' => wc_attribute_label($attribute->get_name(), $product),
                        'options' => $options,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * The values of one of a product's options, in the order set for the attribute.
     *
     * @return list<array{slug: string, name: string, stored: array<string, mixed>}>
     */
    public static function options(WC_Product $product, WC_Product_Attribute $attribute): array
    {
        // Asked for through WooCommerce, the terms come in the order set for the
        // attribute – the order of the options on the product page. The attribute
        // object itself would hand them over sorted by name.
        $terms = wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'all']);
        $options = [];

        foreach (is_array($terms) ? $terms : [] as $term) {
            $stored = get_term_meta($term->term_id, self::TERM_META, true);

            $options[] = ['slug' => $term->slug, 'name' => $term->name, 'stored' => is_array($stored) ? $stored : []];
        }

        return $options;
    }

    /**
     * The option of one attribute that is drawn: the first of the wanted ones
     * that exists, otherwise the attribute's first option.
     *
     * @param  list<string>  $wanted  term slugs (or the texts of a custom attribute)
     * @return array{name: string, stored: array<string, mixed>}|null
     */
    private static function option(WC_Product $product, WC_Product_Attribute $attribute, array $wanted): ?array
    {
        if (! $attribute->is_taxonomy()) {
            $texts = array_values(array_filter(array_map('strval', $attribute->get_options()), 'strlen'));

            if ($texts === []) {
                return null;
            }

            foreach ($wanted as $value) {
                foreach ($texts as $text) {
                    if ($text === $value || sanitize_title($text) === $value) {
                        return ['name' => $text, 'stored' => []];
                    }
                }
            }

            return ['name' => $texts[0], 'stored' => []];
        }

        $options = self::options($product, $attribute);

        if ($options === []) {
            return null;
        }

        foreach ($wanted as $slug) {
            foreach ($options as $option) {
                if ($option['slug'] === $slug) {
                    return ['name' => $option['name'], 'stored' => $option['stored']];
                }
            }
        }

        return ['name' => $options[0]['name'], 'stored' => $options[0]['stored']];
    }
}
