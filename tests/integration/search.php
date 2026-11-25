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

// In the bin it is gone at once – WooCommerce reports no change for that – and back when it is restored.
wp_trash_post($product->get_id());
$expect(Index::find('runde sticker', 48)['total'] === 0 && count(Index::entries()) === 37, 'With a product in the bin the index holds '.count(Index::entries()).' entries.');

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
$expect(Index::entries()[2]['p'] === "ab 24,90\u{a0}€", 'Gathered in the request of a customer who pays no VAT, the round sticker starts at "'.Index::entries()[2]['p'].'".');
$expect(WC()->customer instanceof WC_Customer && WC()->customer->get_is_vat_exempt(), 'Gathering the entries changed the customer of the request.');

WC()->customer = $known;
Index::forget();

// An area is a category with a page of the theme. Other categories are none,
// and a product belongs to the area above any of its categories.
$offers = wp_insert_term('Angebote', 'product_cat', ['slug' => 'angebote']);
$cards = wp_insert_term('Visitenkarten', 'product_cat', ['slug' => 'visitenkarten']);
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

$expect(array_column($areas, 'n') === ['Sticker', 'Flyer'], 'With two more categories the areas are: '.implode(', ', array_column($areas, 'n')));
$expect(array_column($areas, 's') === ['5 Produkte', '4 Produkte'], 'The areas count: '.implode(', ', array_column($areas, 's')));
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

$expect(count(Index::entries()) === 42, 'After all changes were taken back the index holds '.count(Index::entries()).' entries.');

if ($problems !== []) {
    WP_CLI::error("Search:\n- ".implode("\n- ", $problems));
}

WP_CLI::success('Search finds the areas, products and variants of the shop, follows their changes, and holds what everyone may see.');
