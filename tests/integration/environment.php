<?php
/**
 * Checks that the local installation is the one the tests expect.
 *
 *   tools/dev/wp.sh eval-file tests/integration/environment.php <woocommerce version> <germanized version>
 */

$problems = [];

$expect = static function (bool $holds, string $problem) use (&$problems): void {
    if (! $holds) {
        $problems[] = $problem;
    }
};

$active = (array) get_option('active_plugins');

$expect(wp_get_theme()->get_stylesheet() === 'stiqon', 'The Stiqon theme is not the active theme.');
$expect(in_array('stiqon-shop/stiqon-shop.php', $active, true), 'The plugin "Stiqon Shop-Funktionen" is not active.');
$expect(class_exists(\Stiqon\Shop\Plugin::class), 'The plugin\'s classes cannot be loaded.');
$expect(defined('WC_VERSION') && WC_VERSION === ($args[0] ?? null), 'WooCommerce is not active in the pinned version '.($args[0] ?? '?').'.');
$expect(in_array('woocommerce-germanized/woocommerce-germanized.php', $active, true), 'Germanized is not active.');
$expect(get_locale() === 'de_DE_formal', 'The site language is not formal German.');
$expect(get_option('woocommerce_default_country') === 'DE', 'The shop is not set up for Germany.');
$expect(get_option('woocommerce_currency') === 'EUR', 'The shop currency is not the euro.');
$expect(get_option('permalink_structure') === '/%postname%/', 'Pretty permalinks are not set.');
$expect(defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL, 'Calls to the outside are not blocked in this installation.');
$expect(stiqon_built_asset('assets/src/css/app.css') !== null, 'The theme\'s stylesheet is not built.');

if ($problems !== []) {
    WP_CLI::error_multi_line($problems);
    WP_CLI::halt(1);
}

WP_CLI::success('The installation is as expected.');
