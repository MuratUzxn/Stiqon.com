<?php
/**
 * An example assortment for development, tests and previews: every area of the
 * shop with products, options and prices (see tools/dev/example-assortment.php).
 *
 *   tools/dev/wp.sh eval-file tools/dev/example-catalogue.php
 *
 * The real products and prices are to come from the print supplier's plugin.
 * Until then these stand in for them, so that gallery, product page, cart and
 * checkout can be built and tested. Every product is marked as an example
 * (meta "_stiqon_example") and carries the SKU prefix "beispiel-".
 *
 * Running it again brings the examples back to what is written there: products
 * and variations are matched by SKU, attributes and their options by slug.
 * Nothing else in the shop is touched.
 */

use Stiqon\Shop\Catalog\ProductView;

if (! class_exists(WC_Product_Variable::class)) {
    WP_CLI::error('WooCommerce is not active.');
}

$assortment = require __DIR__.'/example-assortment.php';

// --- Options -----------------------------------------------------------------------
// Only the options' names say how a product is drawn; nothing is stored on them,
// so the same guessing runs here that a supplier's products will get.

foreach ($assortment['attributes'] as $slug => [$label, $terms]) {
    $taxonomy = wc_attribute_taxonomy_name($slug);

    if (! wc_attribute_taxonomy_id_by_name($slug)) {
        $created = wc_create_attribute(['name' => $label, 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false]);

        if (is_wp_error($created)) {
            WP_CLI::error("Attribute {$label}: ".$created->get_error_message());
        }
    }

    // WooCommerce registers attribute taxonomies when WordPress starts; one created
    // just now is not known to this request yet.
    if (! taxonomy_exists($taxonomy)) {
        register_taxonomy($taxonomy, ['product'], ['hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false]);
    }

    $position = 0;

    foreach ($terms as $termSlug => $name) {
        $term = get_term_by('slug', (string) $termSlug, $taxonomy);
        $result = $term ? wp_update_term($term->term_id, $taxonomy, ['name' => $name]) : wp_insert_term($name, $taxonomy, ['slug' => (string) $termSlug]);

        if (is_wp_error($result)) {
            WP_CLI::error("Option {$name}: ".$result->get_error_message());
        }

        wc_set_term_order((int) $result['term_id'], $position++, $taxonomy);
    }
}

// --- Categories ----------------------------------------------------------------------

$categories = [];

foreach ($assortment['areas'] as $slug => $name) {
    $term = get_term_by('slug', $slug, 'product_cat');
    $result = $term ? ['term_id' => $term->term_id] : wp_insert_term($name, 'product_cat', ['slug' => $slug]);

    if (is_wp_error($result)) {
        WP_CLI::error("Category {$name}: ".$result->get_error_message());
    }

    $categories[$slug] = (int) $result['term_id'];
}

// --- Prices --------------------------------------------------------------------------
// The base price of a quantity times the factors, in the order the data lists
// them; gross, ending in ,90.

$price = static function (array $model, array $combination): string {
    $amount = $model['base'][$combination['auflage']];

    foreach ($model as $key => $value) {
        if ($key === 'factor') {
            $amount *= $value;
        } elseif ($key === 'by') {
            foreach ($value as $attribute => $factors) {
                $amount *= $factors[$combination[$attribute]];
            }
        }
    }

    return number_format(floor($amount) + 0.9, 2, '.', '');
};

// --- Products ------------------------------------------------------------------------

$productAttribute = static function (string $slug, array $termSlugs, int $position): WC_Product_Attribute {
    $taxonomy = wc_attribute_taxonomy_name($slug);
    $attribute = new WC_Product_Attribute();

    $attribute->set_id(wc_attribute_taxonomy_id_by_name($slug));
    $attribute->set_name($taxonomy);
    $attribute->set_options(array_map(static fn (string $termSlug): int => (int) get_term_by('slug', $termSlug, $taxonomy)->term_id, $termSlugs));
    $attribute->set_position($position);
    $attribute->set_visible(true);
    $attribute->set_variation(true);

    return $attribute;
};

$made = ['products' => 0, 'variations' => 0, 'removed' => 0];

foreach ($assortment['products'] as $order => $item) {
    ['slug' => $slug, 'name' => $name, 'offers' => $offers] = $item;
    $sku = 'beispiel-'.$slug;
    $existing = wc_get_product(wc_get_product_id_by_sku($sku));

    if ($existing && ! $existing instanceof WC_Product_Variable) {
        WP_CLI::error("A product with the SKU {$sku} exists and is not a variable product.");
    }

    $product = $existing ?: new WC_Product_Variable();

    $product->set_name($name);
    $product->set_slug($slug);
    $product->set_sku($sku);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_menu_order($order);
    // How many is one of the options ("Auflage"); a separate quantity field would ask twice.
    $product->set_sold_individually(true);
    $product->set_short_description($item['summary']);
    $product->set_description($item['description']);
    $product->set_category_ids([$categories[$item['area']]]);
    $product->set_attributes(array_map($productAttribute, array_keys($offers), array_values($offers), array_keys(array_keys($offers))));
    $product->set_default_attributes(array_combine(
        array_map('wc_attribute_taxonomy_name', array_keys($offers)),
        array_map(static fn (array $termSlugs): string => $termSlugs[0], array_values($offers)),
    ));
    $product->update_meta_data(ProductView::META, $item['drawing']);
    $product->update_meta_data('_stiqon_example', '1');
    $product->save();

    // Every combination of the options is one variation with its own price.
    $combinations = [[]];

    foreach ($offers as $attributeSlug => $termSlugs) {
        $next = [];

        foreach ($combinations as $combination) {
            foreach ($termSlugs as $termSlug) {
                $next[] = $combination + [$attributeSlug => $termSlug];
            }
        }

        $combinations = $next;
    }

    $kept = [];

    foreach ($combinations as $combination) {
        $variationSku = $sku.'-'.implode('-', $combination);
        $variation = wc_get_product(wc_get_product_id_by_sku($variationSku));

        if (! $variation instanceof WC_Product_Variation) {
            $variation = new WC_Product_Variation();
        }

        $variation->set_parent_id($product->get_id());
        $variation->set_sku($variationSku);
        $variation->set_status('publish');
        $variation->set_attributes(array_combine(array_map('wc_attribute_taxonomy_name', array_keys($combination)), array_values($combination)));
        $variation->set_regular_price($price($item['price'], $combination));
        $variation->save();

        $kept[] = $variation->get_id();
        $made['variations']++;
    }

    // Variations from an earlier version of this list go.
    foreach (array_diff($product->get_children(), $kept) as $stale) {
        wp_delete_post((int) $stale, true);
        $made['removed']++;
    }

    WC_Product_Variable::sync($product->get_id());
    $made['products']++;
}

wc_delete_product_transients();

WP_CLI::success("Example assortment: {$made['products']} products with {$made['variations']} variations".($made['removed'] > 0 ? ", {$made['removed']} old variations removed" : '').'.');
