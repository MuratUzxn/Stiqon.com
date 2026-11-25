<?php
/**
 * An example assortment for development, tests and previews: five sticker and
 * five flyer products with options and prices.
 *
 *   tools/dev/wp.sh eval-file tools/dev/example-catalogue.php
 *
 * The real products and prices are to come from the print supplier's plugin.
 * Until then these stand in for them, so that gallery, product page, cart and
 * checkout can be built and tested. Every product is marked as an example
 * (meta "_stiqon_example") and carries the SKU prefix "beispiel-".
 *
 * Running it again brings the examples back to what is written here: products
 * and variations are matched by SKU, attributes and their options by slug.
 * Nothing else in the shop is touched.
 */

use Stiqon\Shop\Catalog\ProductView;

if (! class_exists(WC_Product_Variable::class)) {
    WP_CLI::error('WooCommerce is not active.');
}

// --- Options -----------------------------------------------------------------------
// Only the materials' and papers' names say how a product is drawn; nothing is
// stored on them, so the same guessing runs here that a supplier's products will get.

$attributes = [
    'material' => ['Material', ['vinyl' => 'Weißes Vinyl', 'transparent' => 'Transparente Folie', 'holografisch' => 'Holografische Folie', 'kraftpapier' => 'Kraftpapier']],
    'groesse' => ['Größe', ['5-cm' => '5 cm', '7-5-cm' => '7,5 cm', '7x4-cm' => '7 × 4 cm', '10x6-cm' => '10 × 6 cm']],
    'papier' => ['Papier', ['135-g' => '135 g Bilderdruck', '250-g' => '250 g Bilderdruck']],
    'auflage' => ['Auflage', ['100' => '100 Stück', '250' => '250 Stück', '500' => '500 Stück', '1000' => '1.000 Stück']],
];

foreach ($attributes as $slug => [$label, $terms]) {
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

foreach (['sticker' => 'Sticker', 'flyer' => 'Flyer'] as $slug => $name) {
    $term = get_term_by('slug', $slug, 'product_cat');
    $result = $term ? ['term_id' => $term->term_id] : wp_insert_term($name, 'product_cat', ['slug' => $slug]);

    if (is_wp_error($result)) {
        WP_CLI::error("Category {$name}: ".$result->get_error_message());
    }

    $categories[$slug] = (int) $result['term_id'];
}

// --- Prices --------------------------------------------------------------------------
// Invented, but shaped like real ones: more pieces cost less each, larger sizes and
// special materials cost more. Gross prices, ending in ,90.

$price = static fn (float $amount): string => number_format(floor($amount) + 0.9, 2, '.', '');

$stickerPrice = static function (string $material, bool $large, string $quantity, float $shape) use ($price): string {
    $base = ['100' => 24.0, '250' => 39.0, '500' => 59.0][$quantity];
    $factor = ['vinyl' => 1.0, 'transparent' => 1.15, 'holografisch' => 1.45, 'kraftpapier' => 1.1][$material];

    return $price($base * $factor * ($large ? 1.4 : 1.0) * $shape);
};

$flyerPrice = static function (string $paper, string $quantity, float $format) use ($price): string {
    $base = ['100' => 19.0, '250' => 29.0, '500' => 39.0, '1000' => 54.0][$quantity];

    return $price($base * $format * ($paper === '250-g' ? 1.3 : 1.0));
};

// --- Products ------------------------------------------------------------------------

// Deliberately plain: what a product can really do is the supplier's to say.
$stickerText = 'Auf weißem Vinyl, transparenter oder holografischer Folie oder auf Kraftpapier. Geliefert als einzelne Sticker.';
$flyerText = 'Farbig bedruckt auf Bilderdruckpapier, wahlweise in 135 g oder in 250 g mit festerem Griff.';

$products = [
    ['sticker', 'runde-sticker', 'Runde Sticker', 'Der Klassiker für Logo, Siegel und Verpackung.', ['shape' => 'circle', 'motif' => 'siegel'], ['5-cm', '7-5-cm'], 1.0],
    ['sticker', 'quadratische-sticker', 'Quadratische Sticker', 'Mit weichen Ecken – für Motive, die Fläche brauchen.', ['shape' => 'square', 'motif' => 'sonne'], ['5-cm', '7-5-cm'], 1.0],
    ['sticker', 'rechteckige-sticker', 'Rechteckige Sticker', 'Für Schriftzüge, Etiketten und Hinweise.', ['shape' => 'rect', 'motif' => 'wellen'], ['7x4-cm', '10x6-cm'], 1.0],
    ['sticker', 'ovale-sticker', 'Ovale Sticker', 'Die freundliche Form für Dankeschön und Gruß.', ['shape' => 'oval', 'motif' => 'danke'], ['7x4-cm', '10x6-cm'], 1.0],
    ['sticker', 'sticker-mit-konturschnitt', 'Sticker mit Konturschnitt', 'Geschnitten entlang Ihres Motivs, mit schmalem weißem Rand.', ['shape' => 'contour', 'motif' => 'sprechblase'], ['5-cm', '7-5-cm'], 1.2],
    ['flyer', 'flyer-din-a6', 'Flyer DIN A6', 'Postkartengröße – passt in jede Tasche.', ['format' => 'a6', 'motif' => 'fest'], [], 1.0],
    ['flyer', 'flyer-din-a5', 'Flyer DIN A5', 'Genug Platz für Angebot, Termin und Anfahrt.', ['format' => 'a5', 'motif' => 'eroeffnung'], [], 1.35],
    ['flyer', 'flyer-din-a4', 'Flyer DIN A4', 'Das große Blatt für Aushang und Speisekarte.', ['format' => 'a4', 'motif' => 'karte'], [], 1.9],
    ['flyer', 'flyer-din-lang', 'Flyer DIN lang', 'Schmal und hoch – für Aufsteller und Briefumschlag.', ['format' => 'lang', 'motif' => 'prozent'], [], 1.2],
    ['flyer', 'flyer-quadrat', 'Flyer Quadrat', 'Fällt auf, weil er anders ist.', ['format' => 'quadrat', 'motif' => 'kursplan'], [], 1.3],
];

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

foreach ($products as $order => [$type, $slug, $name, $summary, $drawing, $sizes, $factor]) {
    $sku = 'beispiel-'.$slug;
    $existing = wc_get_product(wc_get_product_id_by_sku($sku));

    if ($existing && ! $existing instanceof WC_Product_Variable) {
        WP_CLI::error("A product with the SKU {$sku} exists and is not a variable product.");
    }

    $product = $existing ?: new WC_Product_Variable();

    // What the product offers, in the order the options are shown.
    $offers = $type === 'sticker'
        ? ['material' => ['vinyl', 'transparent', 'holografisch', 'kraftpapier'], 'groesse' => $sizes, 'auflage' => ['100', '250', '500']]
        : ['papier' => ['135-g', '250-g'], 'auflage' => ['100', '250', '500', '1000']];

    $product->set_name($name);
    $product->set_slug($slug);
    $product->set_sku($sku);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_menu_order($order);
    // How many is one of the options ("Auflage"); a separate quantity field would ask twice.
    $product->set_sold_individually(true);
    $product->set_short_description($summary);
    $product->set_description($type === 'sticker' ? $stickerText : $flyerText);
    $product->set_category_ids([$categories[$type]]);
    $product->set_attributes(array_map($productAttribute, array_keys($offers), array_values($offers), array_keys(array_keys($offers))));
    $product->set_default_attributes(array_combine(
        array_map('wc_attribute_taxonomy_name', array_keys($offers)),
        array_map(static fn (array $termSlugs): string => $termSlugs[0], array_values($offers)),
    ));
    $product->update_meta_data(ProductView::META, ['type' => $type] + $drawing);
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
        $variation->set_regular_price($type === 'sticker'
            ? $stickerPrice($combination['material'], $combination['groesse'] === $sizes[1], $combination['auflage'], $factor)
            : $flyerPrice($combination['papier'], $combination['auflage'], $factor));
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
