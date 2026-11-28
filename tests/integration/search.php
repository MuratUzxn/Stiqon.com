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

$assortment = require dirname(__DIR__, 2).'/tools/dev/example-assortment.php';
$slugs = array_keys(stiqon_area_definitions());
$areaCount = count($slugs);

Index::forget();

$entries = Index::entries();
$total = count($entries);
$kinds = array_count_values(array_column($entries, 'kind'));

// Search finds what the area pages show: every area, every product, and a variant
// for each card that shows one of a product's options – a sticker per material,
// a shirt per colour.
$shown = array_merge(...array_map(static fn (string $slug): array => stiqon_gallery($slug)['cards'], $slugs));
$variants = count(array_filter($shown, static fn (array $card): bool => $card['option'] !== null));

$expect($kinds === ['area' => $areaCount, 'product' => count($assortment['products']), 'variant' => $variants], 'The index holds '.wp_json_encode($kinds).", the area pages show {$variants} cards of options.");
$expect($variants > count($assortment['products']), "Only {$variants} cards of the area pages show an option.");
$expect(array_column(array_slice($entries, 0, $areaCount), 'u') === array_map(static fn (string $slug): string => home_url("/{$slug}/"), $slugs), 'The areas do not lead to the pages of the theme, in their order: '.implode(', ', array_column(array_slice($entries, 0, $areaCount), 'u')));
$expect($entries[$areaCount]['n'] === 'Runde Sticker' && $entries[$areaCount]['p'] === "ab 24,90\u{a0}€" && $entries[$areaCount]['g'] === 'sticker-circle', 'The first product is not the round sticker from 24,90 €: '.wp_json_encode($entries[$areaCount]));
$expect($entries[$areaCount + 1]['kind'] === 'variant' && $entries[$areaCount + 1]['s'] === 'Weißes Vinyl' && str_ends_with($entries[$areaCount + 1]['u'], '/produkt/runde-sticker/?attribute_pa_material=vinyl'), 'The first variant is not the round sticker on white vinyl.');

// The results page: what find() hands the theme.
$found = Index::find('runder aufkleber kraft', 48);
$expect($found['total'] === 1 && $names($found) === ['Runde Sticker · Kraftpapier'], '"runder aufkleber kraft" finds: '.implode('; ', $names($found)));
$expect(($found['cards'][0]['product'] ?? null) instanceof WC_Product && ($found['cards'][0]['chosen'] ?? null) === ['attribute_pa_material' => 'kraftpapier'], 'A card of a variant does not carry its product and choice.');

// The flyers' area and every product in it, folded leaflets included.
$flyers = count(array_filter($assortment['products'], static fn (array $item): bool => $item['area'] === 'flyer'));
$found = Index::find('flyer', 48);
$expect(array_column($found['areas'], 'name') === ['Flyer'] && count($found['cards']) === $flyers && $found['total'] === $flyers + 1, '"flyer" finds '.$found['total'].' entries: '.implode('; ', $names($found)));

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

// In the bin it is gone at once – WooCommerce reports no change for that – and back when it is restored.
wp_trash_post($product->get_id());
// Gone with it: the product and its four variants, one per material.
$expect(Index::find('runde sticker', 48)['total'] === 0 && count(Index::entries()) === $total - 5, 'With a product in the bin the index holds '.count(Index::entries())." entries of {$total}.");

wp_untrash_post($product->get_id());
$expect(get_post_status($product->get_id()) === 'publish', 'A product restored from the bin is "'.get_post_status($product->get_id()).'".');
$expect(Index::find('runde sticker', 48)['total'] === 1, 'A product restored from the bin is not found.');

// A product behind a password is for whoever has its address: not in search, not in the gallery.
$product = wc_get_product($product->get_id());
$product->set_post_password('nur-fuer-einen');
$product->save();

$expect(Index::find('runde sticker', 48)['total'] === 0, 'A product behind a password is found.');
$expect(! in_array('runde-sticker', array_column(stiqon_gallery('sticker')['products'], 'slug'), true), 'A product behind a password is in the gallery.');
$expect(! in_array('runde-sticker', array_column(stiqon_products_beside(wc_get_product(wc_get_product_id_by_sku('beispiel-ovale-sticker')), 'sticker', 4), 'slug'), true), 'A product behind a password is offered beside another product.');

$product->set_post_password('');
$product->save();
$expect(in_array('runde-sticker', array_column(stiqon_gallery('sticker')['products'], 'slug'), true), 'A product whose password was taken away is not in the gallery.');

// The entries are kept for everyone. Whoever's request gathers them, they hold
// the prices a visitor without an account sees.
$variation = wc_get_product($product->get_children()[0]);
$known = WC()->customer;

WC()->customer = new WC_Customer(0);
WC()->customer->set_is_vat_exempt(true);

$expect(abs((float) wc_get_price_to_display($variation) - 20.92) < 0.005, 'This check takes for granted that a customer who pays no VAT is shown the net price; shown is '.wc_get_price_to_display($variation).'.');

Index::forget();
$expect(Index::entries()[$areaCount]['p'] === "ab 24,90\u{a0}€", 'Gathered in the request of a customer who pays no VAT, the round sticker starts at "'.Index::entries()[$areaCount]['p'].'".');
$expect(WC()->customer instanceof WC_Customer && WC()->customer->get_is_vat_exempt(), 'Gathering the entries changed the customer of the request.');

WC()->customer = $known;
Index::forget();

// An area is a category with a page of the theme. Other categories are none,
// and a product belongs to the area above any of its categories.
$offers = wp_insert_term('Angebote', 'product_cat', ['slug' => 'angebote']);
$cards = wp_insert_term('Neuheiten', 'product_cat', ['slug' => 'neuheiten']);
$sticker = get_term_by('slug', 'sticker', 'product_cat');
$round = wp_insert_term('Rund', 'product_cat', ['slug' => 'rund', 'parent' => $sticker->term_id]);
$flyer = wc_get_product(wc_get_product_id_by_sku('beispiel-flyer-din-a6'));
$filed = [$product->get_category_ids(), $flyer->get_category_ids()];

if (is_wp_error($offers) || is_wp_error($cards) || is_wp_error($round)) {
    WP_CLI::error('The categories for this check could not be made.');
}

// "Angebote" comes before "Sticker" by name; the sticker is filed under a sub-category of "Sticker".
$product->set_category_ids([(int) $offers['term_id'], (int) $round['term_id']]);
$product->save();
$flyer->set_category_ids([(int) $cards['term_id']]);
$flyer->save();

$entries = Index::entries();
$areas = array_values(array_filter($entries, static fn (array $entry): bool => $entry['kind'] === 'area'));
$named = static fn (string $name): array => array_values(array_filter($entries, static fn (array $entry): bool => $entry['kind'] === 'product' && $entry['n'] === $name))[0] ?? [];

$expect(array_column($areas, 'n') === array_values(array_column(stiqon_area_definitions(), 'label')), 'With two more categories the areas are: '.implode(', ', array_column($areas, 'n')));
// The round sticker still counts for stickers, the flyer moved out of its area.
$expect(array_slice(array_column($areas, 's'), 0, 2) === ['5 Produkte', ($flyers - 1).' Produkte'], 'The areas count: '.implode(', ', array_column($areas, 's')));
$expect(($named('Runde Sticker')['s'] ?? null) === 'Sticker', 'A sticker filed under "Angebote" and a sub-category of "Sticker" is filed under "'.($named('Runde Sticker')['s'] ?? '?').'".');
$expect(($named('Flyer DIN A6')['s'] ?? null) === '', 'A product in a category without a page is filed under "'.($named('Flyer DIN A6')['s'] ?? '?').'".');
$expect((stiqon_product_area($product)['slug'] ?? null) === 'sticker' && stiqon_product_area($flyer) === null, 'The product page names another area than search does.');
$expect(in_array('runde-sticker', array_column(stiqon_gallery('sticker')['products'], 'slug'), true), 'The area page does not show a product of a sub-category.');

$product->set_category_ids($filed[0]);
$product->save();
$flyer->set_category_ids($filed[1]);
$flyer->save();

foreach ([$offers, $cards, $round] as $made) {
    wp_delete_term((int) $made['term_id'], 'product_cat');
}

$expect(count(Index::entries()) === $total, 'After all changes were taken back the index holds '.count(Index::entries())." entries, not {$total}.");

if ($problems !== []) {
    WP_CLI::error("Search:\n- ".implode("\n- ", $problems));
}

WP_CLI::success('Search finds the areas, products and variants of the shop, follows their changes, and holds what everyone may see.');
