<?php
/**
 * Products the example assortment does not have, for tests/e2e/variations.spec.js:
 * the kinds a real assortment brings along.
 *
 *   tools/dev/wp.sh eval-file tests/e2e/tools/odd-products.php create    prints JSON: slug => id
 *   tools/dev/wp.sh eval-file tests/e2e/tools/odd-products.php remove
 *
 * They use the options of the example assortment (tools/dev/example-catalogue.php)
 * and carry the SKU prefix "test-".
 */

if (! class_exists(WC_Product_Variable::class)) {
    WP_CLI::error('WooCommerce is not active.');
}

$attribute = static function (string $slug, array $termSlugs, int $position): WC_Product_Attribute {
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

/**
 * @param  array<string, list<string>>  $offers  option => ticked values
 * @param  list<array{0: array<string, string>, 1: string, 2?: string}>  $variations  choice, price ('' for none), status
 */
$make = static function (string $slug, string $name, array $offers, array $variations, array $more = []) use ($attribute): int {
    $product = new WC_Product_Variable();

    $product->set_name($name);
    $product->set_slug($slug);
    $product->set_sku('test-'.$slug);
    $product->set_status('publish');
    $product->set_catalog_visibility($more['visibility'] ?? 'visible');
    $product->set_menu_order(90);
    $product->set_sold_individually(true);
    $product->set_short_description($more['summary'] ?? 'Nur für die Tests.');
    $product->set_category_ids(isset($more['category']) ? [(int) get_term_by('slug', $more['category'], 'product_cat')->term_id] : []);
    $product->set_attributes(array_map($attribute, array_keys($offers), array_values($offers), array_keys(array_keys($offers))));
    $product->set_default_attributes(array_combine(
        array_map('wc_attribute_taxonomy_name', array_keys($offers)),
        array_map(static fn (array $termSlugs): string => $termSlugs[0], array_values($offers)),
    ));
    $product->update_meta_data('_stiqon_mockup', ['type' => 'sticker', 'shape' => 'circle', 'motif' => 'siegel']);
    $product->set_post_password($more['password'] ?? '');
    $product->save();

    foreach ($variations as $position => [$choice, $price, $status]) {
        $variation = new WC_Product_Variation();

        $variation->set_parent_id($product->get_id());
        $variation->set_sku('test-'.$slug.'-'.implode('-', $choice));
        $variation->set_status($status);
        $variation->set_menu_order($position);
        $variation->set_attributes(array_combine(array_map('wc_attribute_taxonomy_name', array_keys($choice)), array_values($choice)));
        $variation->set_regular_price($price);
        $variation->save();
    }

    // The prices of the variations become the product's range.
    WC_Product_Variable::sync($product->get_id());

    return $product->get_id();
};

$remove = static function (): void {
    $ids = get_posts(['post_type' => ['product', 'product_variation'], 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_query' => [['key' => '_sku', 'value' => 'test-', 'compare' => 'LIKE']]]);

    foreach ($ids as $id) {
        if (str_starts_with((string) get_post_meta($id, '_sku', true), 'test-')) {
            wp_delete_post($id, true);
        }
    }
};

if (($args[0] ?? '') === 'remove') {
    $remove();

    return;
}

if (($args[0] ?? '') !== 'create') {
    WP_CLI::error('Say "create" or "remove".');
}

$remove();

$ids = [];

// Not every combination exists: three for sale, one without a price, one switched
// off. And a material is ticked – holographic – that nothing can be bought in.
$ids['teststicker-mit-luecken'] = $make(
    'teststicker-mit-luecken',
    'Teststicker mit Lücken',
    ['material' => ['vinyl', 'transparent', 'holografisch'], 'groesse' => ['5-cm', '7-5-cm'], 'auflage' => ['100', '250']],
    [
        [['material' => 'vinyl', 'groesse' => '5-cm', 'auflage' => '100'], '20.90', 'publish'],
        [['material' => 'vinyl', 'groesse' => '7-5-cm', 'auflage' => '250'], '30.90', 'publish'],
        [['material' => 'transparent', 'groesse' => '7-5-cm', 'auflage' => '100'], '25.90', 'publish'],
        [['material' => 'holografisch', 'groesse' => '5-cm', 'auflage' => '100'], '', 'publish'],
        [['material' => 'transparent', 'groesse' => '5-cm', 'auflage' => '250'], '27.90', 'private'],
    ],
    ['category' => 'sticker'],
);

// More variations than WooCommerce writes into the page (30): it asks the server
// for the one that fits a choice.
$many = [];

foreach (['vinyl' => 0, 'transparent' => 3, 'holografisch' => 9, 'kraftpapier' => 2] as $material => $extra) {
    foreach (['5-cm' => 0, '7-5-cm' => 6, '7x4-cm' => 4] as $size => $larger) {
        foreach (['100' => 20, '250' => 34, '500' => 52] as $quantity => $base) {
            $many[] = [['material' => $material, 'groesse' => $size, 'auflage' => (string) $quantity], number_format($base + $extra + $larger + 0.9, 2, '.', ''), 'publish'];
        }
    }
}

$ids['teststicker-mit-vielen-varianten'] = $make(
    'teststicker-mit-vielen-varianten',
    'Teststicker mit vielen Varianten',
    ['material' => ['vinyl', 'transparent', 'holografisch', 'kraftpapier'], 'groesse' => ['5-cm', '7-5-cm', '7x4-cm'], 'auflage' => ['100', '250', '500']],
    $many,
    ['visibility' => 'hidden'],
);

// For one customer only: whoever has the address needs the password, too.
$ids['teststicker-hinter-passwort'] = $make(
    'teststicker-hinter-passwort',
    'Teststicker hinter Passwort',
    ['material' => ['vinyl'], 'auflage' => ['100']],
    [[['material' => 'vinyl', 'auflage' => '100'], '11.90', 'publish']],
    ['category' => 'sticker', 'password' => 'nur-fuer-einen', 'summary' => 'Geheimsache Sonderpreis.'],
);

echo wp_json_encode($ids);
