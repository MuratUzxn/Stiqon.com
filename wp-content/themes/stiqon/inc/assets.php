<?php
/**
 * Styles and scripts. Vite builds them from assets/src into assets/build
 * (npm run build) and writes a manifest that maps each source to its built,
 * fingerprinted file.
 */

defined('ABSPATH') || exit;

/**
 * @return array<string, array{file: string}>
 */
function stiqon_build_manifest(): array
{
    static $manifest = null;

    if ($manifest === null) {
        $file = get_theme_file_path('assets/build/manifest.json');
        $decoded = is_readable($file) ? json_decode((string) file_get_contents($file), true) : null;
        $manifest = is_array($decoded) ? $decoded : [];
    }

    return $manifest;
}

/**
 * Address of a built file, given its source path below the theme directory.
 */
function stiqon_built_asset(string $source): ?string
{
    $entry = stiqon_build_manifest()[$source] ?? null;

    return isset($entry['file']) ? get_theme_file_uri('assets/build/'.$entry['file']) : null;
}

add_action('wp_enqueue_scripts', function (): void {
    // File names carry a fingerprint of their content, so no version is appended.
    if ($style = stiqon_built_asset('assets/src/css/app.css')) {
        wp_enqueue_style('stiqon', $style, [], null);
    }

    if ($script = stiqon_built_asset('assets/src/js/app.js')) {
        wp_enqueue_script_module('stiqon', $script, [], null);
    }
});

/**
 * The page's script fetches the behaviour of gallery and product pages on
 * demand (see assets/src/js/app.js). Told about it up front, the browser
 * fetches that file alongside the main script instead of after it.
 */
add_action('wp_head', function (): void {
    $source = match (true) {
        function_exists('is_product') && is_product() => 'assets/src/js/product.js',
        is_page(array_keys(stiqon_required_pages())) => 'assets/src/js/gallery.js',
        default => null,
    };

    $file = $source !== null ? stiqon_built_asset($source) : null;

    if ($file !== null) {
        printf('<link rel="modulepreload" href="%s">'."\n", esc_url($file));
    }
}, 3);

