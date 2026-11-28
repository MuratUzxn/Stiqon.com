<?php
/**
 * The example assortment is in the shop, priced the German way, and every product
 * can be drawn – in every value of the option a visitor sees in the drawing.
 *
 *   tools/dev/wp.sh eval-file tests/integration/catalogue.php
 */

use Stiqon\Shop\Catalog\ProductView;
use Stiqon\Shop\Mockup\Kinds;

$problems = [];

$expect = static function (bool $holds, string $problem) use (&$problems): void {
    if (! $holds) {
        $problems[] = $problem;
    }
};

$assortment = require dirname(__DIR__, 2).'/tools/dev/example-assortment.php';

// Prices the German way, including VAT.
$expect(html_entity_decode(wp_strip_all_tags(wc_price(1234.5))) === "1.234,50\u{a0}€", 'A price is not written "1.234,50 €" but "'.html_entity_decode(wp_strip_all_tags(wc_price(1234.5))).'".');
$expect(get_option('woocommerce_tax_display_shop') === 'incl', 'The shop does not show prices including VAT.');
$expect((float) (array_values(WC_Tax::get_rates_for_tax_class(''))[0]->tax_rate ?? 0) === 19.0, 'The standard VAT rate is not 19 %.');

// Every area of the theme has its page and its category with products, and the data knows it.
foreach (stiqon_area_definitions() as $slug => $area) {
    $page = get_page_by_path($slug);
    $category = get_term_by('slug', $slug, 'product_cat');

    $expect($page instanceof WP_Post && $page->post_status === 'publish', "The area {$slug} has no published page.");
    $expect($category instanceof WP_Term && $category->count > 0, "The area {$slug} has no category with products.");
    $expect(isset($assortment['areas'][$slug]), "The example assortment does not know the area {$slug}.");
    $expect(Kinds::has($area['drawing']['type']), "The area {$slug} stands for itself with a drawing of the unknown kind {$area['drawing']['type']}.");
}

/** What a drawing shows: without the ids that only make it unique on its page, and without its name for screen readers. */
$drawing = static fn (string $svg): string => (string) preg_replace(['/\saria-label="[^"]*"/', '/\b[a-z]{2}\d+(-[a-z0-9-]+)?\b/'], ['', 'ID'], $svg);

foreach ($assortment['areas'] as $area => $name) {
    $products = wc_get_products(['category' => [$area], 'status' => 'publish', 'limit' => -1, 'orderby' => 'menu_order', 'order' => 'ASC']);
    $slugs = array_map(static fn (WC_Product $product): string => $product->get_slug(), $products);
    $listed = array_values(array_map(static fn (array $item): string => $item['slug'], array_filter($assortment['products'], static fn (array $item): bool => $item['area'] === $area)));

    $expect($slugs === $listed, "Category {$area} holds: ".implode(', ', $slugs));
}

foreach ($assortment['products'] as $item) {
    $slug = $item['slug'];
    $product = wc_get_product(wc_get_product_id_by_sku('beispiel-'.$slug));

    if (! $product instanceof WC_Product_Variable) {
        $problems[] = "{$slug} is not in the shop as a variable product.";

        continue;
    }

    $type = $item['drawing']['type'];
    $view = ProductView::resolve($product);
    $variations = (int) array_product(array_map('count', $item['offers']));

    $expect(count($product->get_children()) === $variations, "{$slug} has ".count($product->get_children())." variations instead of {$variations}.");
    $expect($view !== null && $view['type'] === $type, "{$slug} is not recognised as {$type} but as ".($view['type'] ?? 'nothing').'.');

    // What the data says about the form, the drawing does (a sticker's shape, a flyer's format …).
    $form = Kinds::has($type) ? Kinds::of($type)::form()['key'] : null;

    if ($form !== null && isset($item['drawing'][$form])) {
        $expect(($view['spec'][$form] ?? null) === $item['drawing'][$form], "{$slug} is drawn with {$form} ".($view['spec'][$form] ?? 'none')." instead of {$item['drawing'][$form]}.");
    }

    $expect(str_contains(stiqon_product_view($product), 'data-mockup="'.$type.'"'), "{$slug} cannot be drawn.");

    // A variation is called what its product is called; the choice is listed beside the name.
    $named = array_unique(array_map(static fn (int $id): string => wc_get_product($id)->get_name(), $product->get_children()));
    $expect($named === [$product->get_name()], "{$slug}: its variations are called ".implode(' / ', array_slice($named, 0, 3)).'.');

    // The cheapest variation is what the gallery will name as "ab …".
    $from = (float) $product->get_variation_price('min', true);
    $expect($from >= 9.9 && fmod(round($from * 100), 100) === 90.0, "{$slug} starts at {$from}.");

    // Net and gross differ by the VAT.
    $variation = wc_get_product($product->get_children()[0]);
    $gross = (float) wc_get_price_including_tax($variation);
    $net = (float) wc_get_price_excluding_tax($variation);
    $expect(abs($gross / 1.19 - $net) < 0.01, "{$slug}: {$gross} gross is not {$net} net plus 19 %.");

    // The gallery shows one card per value of the option the drawing shows; no two of them may look the same.
    $views = stiqon_product_views($product);

    if (count($views['views']) > 1) {
        $drawn = array_map(static fn (array $view): string => $drawing($view['html']), $views['views']);
        $expect(count(array_unique($drawn)) === count($drawn), "{$slug}: two of its cards (".implode(', ', array_column($views['views'], 'name')).') are drawn alike.');
    }
}

// Stickers: the materials carry nothing but their names; the drawing follows them.
$materials = ['vinyl' => 'vinyl', 'transparent' => 'clear', 'holografisch' => 'holo', 'kraftpapier' => 'kraft'];

foreach (wc_get_products(['category' => ['sticker'], 'status' => 'publish', 'limit' => -1]) as $product) {
    $slug = $product->get_slug();

    foreach ($materials as $term => $material) {
        $chosen = ProductView::resolve($product, ['attribute_pa_material' => $term]);

        $expect(($chosen['spec']['material'] ?? null) === $material, "{$slug} with {$term} is drawn as ".($chosen['spec']['material'] ?? 'nothing').'.');
    }

    $expect((ProductView::resolve($product, ['pa_material' => 'kraftpapier'])['spec']['finish'] ?? null) === 'matte', "{$slug} on kraft paper shines.");
    $expect(ProductView::resolve(wc_get_product($product->get_children()[0]))['spec']['material'] === 'vinyl', "{$slug}: a variation is not drawn in its own material.");
}

// Flyers and folded leaflets: from 250 g the sheet is drawn stiffer.
foreach (wc_get_products(['category' => ['flyer'], 'status' => 'publish', 'limit' => -1]) as $product) {
    $slug = $product->get_slug();

    $expect((ProductView::resolve($product, ['attribute_pa_papier' => '250-g'])['spec']['heavy'] ?? null) === true, "{$slug} in 250 g is not drawn as the stiffer sheet.");
    $expect((ProductView::resolve($product)['spec']['heavy'] ?? null) === false, "{$slug} in 135 g is drawn as the stiffer sheet.");
}

// An option that does not exist falls back to the product's first one instead of failing.
$round = wc_get_product(wc_get_product_id_by_sku('beispiel-runde-sticker'));
$expect((ProductView::resolve($round, ['attribute_pa_material' => 'gibt-es-nicht'])['spec']['material'] ?? null) === 'vinyl', 'An unknown option does not fall back to the first one.');

if ($problems !== []) {
    WP_CLI::error_multi_line($problems);
    WP_CLI::halt(1);
}

WP_CLI::success('The example assortment is complete, priced and drawable in every area.');
