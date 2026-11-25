<?php
/**
 * Supplier families can be added as drafts without touching existing products.
 *
 *   tools/dev/wp.sh eval-file tests/integration/supplier-catalogue.php
 */

use Stiqon\Shop\Catalog\SupplierCatalogue;

$problems = [];

$expect = static function (bool $holds, string $problem) use (&$problems): void {
    if (! $holds) {
        $problems[] = $problem;
    }
};

$names = array_column(SupplierCatalogue::PROVIDERS, 'name');
$expectedNames = ['Print.com', 'HelloPrint Connect', 'Onlineprinters', 'SAXOPRINT', 'FLYERALARM PRO', 'Probo', 'print24', 'Maxilia', 'Printful', 'Shirtigo', 'Gelato'];
sort($names);
sort($expectedNames);
$expect($names === $expectedNames, 'The supplier catalogue does not contain exactly the eleven requested suppliers.');

try {
    SupplierCatalogue::createDrafts('kein-anbieter');
    $expect(false, 'An unknown supplier was accepted.');
} catch (InvalidArgumentException) {
}

$provider = 'print-com';
$families = SupplierCatalogue::PROVIDERS[$provider]['families'];
$expect(isset($families['sticker'], $families['flyer']), 'Print.com is missing its sticker or flyer family.');

$productIds = static fn (): array => get_posts([
    'post_type' => 'product',
    'post_status' => array_values(get_post_stati()),
    'numberposts' => -1,
    'orderby' => 'ID',
    'order' => 'ASC',
    'fields' => 'ids',
    'meta_key' => '_stiqon_supplier',
    'meta_value' => $provider,
]);

$before = $productIds();

try {
    $first = SupplierCatalogue::createDrafts($provider);
    $after = $productIds();
    $newIds = array_values(array_diff($after, $before));
    $expect(count($after) === count($families), 'The import did not leave exactly one product per family.');
    $expect($first['created'] === count($newIds) && $first['existing'] === count($before), 'The first import reported incorrect counts.');

    $actualFamilies = [];
    foreach ($after as $id) {
        $actualFamilies[] = get_post_meta($id, '_stiqon_supplier_family', true);
    }
    $expectedFamilies = array_map(static fn (string $slug): string => $provider.':'.$slug, array_keys($families));
    sort($actualFamilies);
    sort($expectedFamilies);
    $expect($actualFamilies === $expectedFamilies, 'The product family identities do not match the catalogue.');

    $edited = null;
    foreach ($newIds as $id) {
        $product = wc_get_product($id);
        $expect($product instanceof WC_Product_Simple, "Supplier product {$id} is not a simple WooCommerce product.");
        if (! $product instanceof WC_Product_Simple) {
            continue;
        }

        $expect($product->get_status() === 'draft', "Supplier product {$id} was published.");
        $expect($product->get_catalog_visibility() === 'hidden', "Supplier product {$id} is visible in the catalogue.");
        $expect($product->get_price() === '' && ! $product->is_purchasable(), "Supplier product {$id} can be purchased or has an assigned price.");
        $edited ??= $product;
    }

    if ($edited !== null) {
        $edited->set_name('Bearbeiteter Lieferantenentwurf');
        $edited->save();
    }

    $second = SupplierCatalogue::createDrafts($provider);
    $expect($second['created'] === 0 && $second['existing'] === count($families), 'The repeated import reported incorrect counts.');
    $expect($productIds() === $after, 'The repeated import changed the set of products.');
    if ($edited !== null) {
        $expect(wc_get_product($edited->get_id())->get_name() === 'Bearbeiteter Lieferantenentwurf', 'The repeated import overwrote an edit.');
    }
} finally {
    foreach (array_diff($productIds(), $before) as $id) {
        wp_delete_post($id, true);
    }
}

if ($problems !== []) {
    WP_CLI::error_multi_line($problems);
    WP_CLI::halt(1);
}

WP_CLI::success('Supplier catalogue imports create hidden drafts and preserve existing products.');
