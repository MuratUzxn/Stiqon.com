<?php
/**
 * What search looks through is gathered from the shop as it is: the areas, the
 * products and their variants – and gathered again when one of them changes.
 *
 *   tools/dev/wp.sh eval-file tests/integration/search.php
 */

use Stiqon\Shop\Search\Index;

$problems = [];

$expect = static function (bool $holds, string $problem) use (&$problems): void {
    if (! $holds) {
        $problems[] = $problem;
    }
};

$names = static fn (array $found): array => array_map(
    static fn (array $card): string => $card['title'].($card['subtitle'] !== null ? ' · '.$card['subtitle'] : ''),
    $found['cards'],
);

Index::forget();

$entries = Index::entries();
$kinds = array_count_values(array_column($entries, 'kind'));

// Two areas, ten products, a variant per material (5 × 4) and per paper (5 × 2).
$expect($kinds === ['area' => 2, 'product' => 10, 'variant' => 30], 'The index holds: '.wp_json_encode($kinds));
$expect(array_column(array_slice($entries, 0, 2), 'u') === [home_url('/sticker/'), home_url('/flyer/')], 'The areas do not lead to the pages of the theme: '.implode(', ', array_column(array_slice($entries, 0, 2), 'u')));
$expect($entries[2]['n'] === 'Runde Sticker' && $entries[2]['p'] === "ab 24,90\u{a0}€" && $entries[2]['g'] === 'sticker-circle', 'The first product is not the round sticker from 24,90 €: '.wp_json_encode($entries[2]));
$expect($entries[3]['kind'] === 'variant' && $entries[3]['s'] === 'Weißes Vinyl' && str_ends_with($entries[3]['u'], '/produkt/runde-sticker/?attribute_pa_material=vinyl'), 'The first variant is not the round sticker on white vinyl.');

// The results page: what find() hands the theme.
$found = Index::find('runder aufkleber kraft', 48);
$expect($found['total'] === 1 && $names($found) === ['Runde Sticker · Kraftpapier'], '"runder aufkleber kraft" finds: '.implode('; ', $names($found)));
$expect(($found['cards'][0]['product'] ?? null) instanceof WC_Product && ($found['cards'][0]['chosen'] ?? null) === ['attribute_pa_material' => 'kraftpapier'], 'A card of a variant does not carry its product and choice.');

$found = Index::find('flyer', 48);
$expect(array_column($found['areas'], 'name') === ['Flyer'] && count($found['cards']) === 5 && $found['total'] === 6, '"flyer" finds '.$found['total'].' entries.');

$found = Index::find('sticker', 3);
$expect($found['total'] === 6 && count($found['areas']) + count($found['cards']) === 3, 'A limit of 3 does not cut the list but keep the total.');

// The entries are kept, and gathered again when a product changes.
$product = wc_get_product(wc_get_product_id_by_sku('beispiel-runde-sticker'));
$name = $product->get_name();

$expect(get_transient('stiqon_search_index') !== false, 'The gathered entries are not kept.');

$product->set_name('Kreisrunde Sticker');
$product->save();

$expect($names(Index::find('kreisrunde', 48)) === ['Kreisrunde Sticker'], 'A renamed product is not found by its new name.');

$product->set_name($name);
$product->save();

$expect($names(Index::find('kreisrunde', 48)) === [] && $names(Index::find('runde sticker', 48)) === ['Runde Sticker'], 'A product that got its old name back is not found by it.');

// So do the names of options.
$term = get_term_by('slug', 'kraftpapier', 'pa_material');
wp_update_term($term->term_id, 'pa_material', ['name' => 'Packpapier']);

$expect(count(Index::find('packpapier', 48)['cards']) === 5, 'A renamed option is not found by its new name.');

wp_update_term($term->term_id, 'pa_material', ['name' => 'Kraftpapier']);

$expect(count(Index::find('kraftpapier', 48)['cards']) === 5 && Index::find('packpapier', 48)['total'] === 0, 'An option that got its old name back is not found by it.');

// A product that is hidden from search is not in it; one hidden from the lists only still is.
$product->set_catalog_visibility('catalog');
$product->save();
$expect(Index::find('runde sticker', 48)['total'] === 0, 'A product hidden from search is found.');

$product->set_catalog_visibility('search');
$product->save();
$expect(Index::find('runde sticker', 48)['total'] === 1, 'A product that is only to be found by search is not found.');

$product->set_catalog_visibility('visible');
$product->save();

// A draft is not found.
$product->set_status('draft');
$product->save();
$expect(Index::find('runde sticker', 48)['total'] === 0, 'A product that is a draft is found.');

$product->set_status('publish');
$product->save();
$expect(count(Index::entries()) === 42, 'After all changes were taken back the index holds '.count(Index::entries()).' entries.');

if ($problems !== []) {
    WP_CLI::error("Search:\n- ".implode("\n- ", $problems));
}

WP_CLI::success('Search finds the areas, products and variants of the shop, and follows their changes.');
