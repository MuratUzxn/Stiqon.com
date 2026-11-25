<?php
/**
 * Checks that the local installation is the one the tests expect.
 *
 *   tools/dev/wp.sh eval-file tests/integration/environment.php <wordpress> <woocommerce> <germanized>
 */

require_once ABSPATH.'wp-admin/includes/plugin.php';

[$wordpress, $woocommerce, $germanized] = array_pad($args, 3, '?');

$problems = [];

$expect = static function (bool $holds, string $problem) use (&$problems): void {
    if (! $holds) {
        $problems[] = $problem;
    }
};

$active = (array) get_option('active_plugins');
$germanizedFile = WP_PLUGIN_DIR.'/woocommerce-germanized/woocommerce-germanized.php';

$expect(get_bloginfo('version') === $wordpress, "WordPress is not the pinned version {$wordpress} but ".get_bloginfo('version').'.');
$expect(wp_get_theme()->get_stylesheet() === 'stiqon', 'The Stiqon theme is not the active theme.');
$expect(in_array('stiqon-shop/stiqon-shop.php', $active, true), 'The plugin "Stiqon Shop-Funktionen" is not active.');
$expect(class_exists(\Stiqon\Shop\Plugin::class), 'The plugin\'s classes cannot be loaded.');
$expect(defined('WC_VERSION') && WC_VERSION === $woocommerce, "WooCommerce is not active in the pinned version {$woocommerce}.");
$expect(in_array('woocommerce-germanized/woocommerce-germanized.php', $active, true), 'Germanized is not active.');
$expect(is_file($germanizedFile) && get_plugin_data($germanizedFile, false, false)['Version'] === $germanized, "Germanized is not the pinned version {$germanized}.");
$expect(get_locale() === 'de_DE_formal', 'The site language is not formal German.');
$expect(get_option('woocommerce_default_country') === 'DE', 'The shop is not set up for Germany.');
$expect(get_option('woocommerce_currency') === 'EUR', 'The shop currency is not the euro.');
$expect(get_option('permalink_structure') === '/%postname%/', 'Pretty permalinks are not set.');
$expect(defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL, 'Calls to the outside are not blocked in this installation.');
$expect(wp_get_environment_type() === 'local', 'The installation does not say that it is a local one.');
$expect(parse_url((string) get_post_field('guid', 1), PHP_URL_PATH) === '/', 'The first post\'s address carries a path: the installer did not know the site\'s address.');

// What the theme itself says it needs: its built files and its pages.
$expect(stiqon_setup_problems() === [], 'The theme reports: '.implode(' ', stiqon_setup_problems()));

// Each page the setup script creates exists once – also after the script ran again.
$slugs = get_posts(['post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids']);
$slugs = array_map(static fn (int $id): string => (string) get_post_field('post_name', $id), $slugs);

foreach (['sticker', 'flyer', 'musterbogen'] as $slug) {
    $copies = preg_grep('/^'.$slug.'(-\d+)?$/', $slugs);

    $expect(count($copies) === 1, "There is not exactly one page \"{$slug}\": ".implode(', ', $copies ?: ['none']).'.');
}

$expect(get_post_status(get_page_by_path('musterbogen')) === 'private', 'The sample sheet is not private.');

// What the plugin switches off when it is first activated.
foreach (\Stiqon\Shop\Setup\Defaults::PRIVACY as $option => $value) {
    $expect((string) get_option($option) === $value, "The privacy default {$option} = {$value} is not in place.");
}

if ($problems !== []) {
    WP_CLI::error_multi_line($problems);
    WP_CLI::halt(1);
}

WP_CLI::success('The installation is as expected.');
