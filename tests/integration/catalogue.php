<?php
/**
 * The example assortment is in the shop, priced the German way, and every product
 * can be drawn with every one of its materials or papers.
 *
 *   tools/dev/wp.sh eval-file tests/integration/catalogue.php
 */

use Stiqon\Shop\Catalog\ProductView;

$problems = [];

$expect = static function (bool $holds, string $problem) use (&$problems): void {
    if (! $holds) {
        $problems[] = $problem;
    }
};

// Prices the German way, including VAT.
$expect(html_entity_decode(wp_strip_all_tags(wc_price(1234.5))) === "1.234,50\u{a0}€", 'A price is not written "1.234,50 €" but "'.html_entity_decode(wp_strip_all_tags(wc_price(1234.5))).'".');
$expect(get_option('woocommerce_tax_display_shop') === 'incl', 'The shop does not show prices including VAT.');
$expect((float) (array_values(WC_Tax::get_rates_for_tax_class(''))[0]->tax_rate ?? 0) === 19.0, 'The standard VAT rate is not 19 %.');

$expected = [
    'sticker' => ['runde-sticker' => 'circle', 'quadratische-sticker' => 'square', 'rechteckige-sticker' => 'rect', 'ovale-sticker' => 'oval', 'sticker-mit-konturschnitt' => 'contour'],
    'flyer' => ['flyer-din-a6' => 'a6', 'flyer-din-a5' => 'a5', 'flyer-din-a4' => 'a4', 'flyer-din-lang' => 'lang', 'flyer-quadrat' => 'quadrat'],
];

$materials = ['vinyl' => 'vinyl', 'transparent' => 'clear', 'holografisch' => 'holo', 'kraftpapier' => 'kraft'];

foreach ($expected as $category => $shapes) {
    $products = wc_get_products(['category' => [$category], 'status' => 'publish', 'limit' => -1, 'orderby' => 'menu_order', 'order' => 'ASC']);
    $slugs = array_map(static fn (WC_Product $product): string => $product->get_slug(), $products);

    $expect($slugs === array_keys($shapes), "Category {$category} holds: ".implode(', ', $slugs));

    foreach ($products as $product) {
        $slug = $product->get_slug();
        $view = ProductView::resolve($product);

        $expect($product->is_type('variable'), "{$slug} is not a variable product.");
        $expect(count($product->get_children()) === ($category === 'sticker' ? 24 : 8), "{$slug} has ".count($product->get_children()).' variations.');
        $expect($view !== null && $view['type'] === $category, "{$slug} is not recognised as a {$category}.");
        $expect(($view['spec'][$category === 'sticker' ? 'shape' : 'format'] ?? null) === ($shapes[$slug] ?? null), "{$slug} is drawn in the wrong shape or format.");
        $expect(str_contains(stiqon_product_view($product), 'data-mockup="'.$category.'"'), "{$slug} cannot be drawn.");

        // A variation is called what its product is called; the choice is listed beside the name.
        $named = array_unique(array_map(static fn (int $id): string => wc_get_product($id)->get_name(), $product->get_children()));
        $expect($named === [$product->get_name()], "{$slug}: its variations are called ".implode(' / ', array_slice($named, 0, 3)).'.');

        // The cheapest variation is what the gallery will name as "ab …".
        $from = (float) $product->get_variation_price('min', true);
        $expect($from >= 19.9 && fmod(round($from * 100), 100) === 90.0, "{$slug} starts at {$from}.");

        // Net and gross differ by the VAT.
        $variation = wc_get_product($product->get_children()[0]);
        $gross = (float) wc_get_price_including_tax($variation);
        $net = (float) wc_get_price_excluding_tax($variation);
        $expect(abs($gross / 1.19 - $net) < 0.01, "{$slug}: {$gross} gross is not {$net} net plus 19 %.");

        if ($category === 'sticker') {
            // The materials carry nothing but their names; the drawing follows them.
            foreach ($materials as $term => $material) {
                $chosen = ProductView::resolve($product, ['attribute_pa_material' => $term]);

                $expect(($chosen['spec']['material'] ?? null) === $material, "{$slug} with {$term} is drawn as ".($chosen['spec']['material'] ?? 'nothing').'.');
            }

            $expect((ProductView::resolve($product, ['pa_material' => 'kraftpapier'])['spec']['finish'] ?? null) === 'matte', "{$slug} on kraft paper shines.");
            $expect(ProductView::resolve($variation)['spec']['material'] === 'vinyl', "{$slug}: a variation is not drawn in its own material.");
        } else {
            $expect((ProductView::resolve($product, ['attribute_pa_papier' => '250-g'])['spec']['heavy'] ?? null) === true, "{$slug} in 250 g is not drawn as the stiffer sheet.");
            $expect((ProductView::resolve($product)['spec']['heavy'] ?? null) === false, "{$slug} in 135 g is drawn as the stiffer sheet.");
        }
    }
}

// An option that does not exist falls back to the product's first one instead of failing.
$round = wc_get_product(wc_get_product_id_by_sku('beispiel-runde-sticker'));
$expect((ProductView::resolve($round, ['attribute_pa_material' => 'gibt-es-nicht'])['spec']['material'] ?? null) === 'vinyl', 'An unknown option does not fall back to the first one.');

if ($problems !== []) {
    WP_CLI::error_multi_line($problems);
    WP_CLI::halt(1);
}

WP_CLI::success('The example assortment is complete, priced and drawable.');
