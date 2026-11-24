<?php
/**
 * Site-wide data and what the theme adds to the document head.
 */

defined('ABSPATH') || exit;

/**
 * The areas of the shop, for navigation.
 *
 * @return list<array{slug: string, label: string, url: string, current: bool}>
 */
function stiqon_areas(): array
{
    $areas = [];

    foreach (['sticker' => 'Sticker', 'flyer' => 'Flyer'] as $slug => $label) {
        $areas[] = [
            'slug' => $slug,
            'label' => $label,
            'url' => home_url("/{$slug}/"),
            'current' => is_page($slug),
        ];
    }

    return $areas;
}

/**
 * The interface face is fetched right away instead of after the stylesheet, so
 * headlines do not jump from a fallback face.
 */
add_action('wp_head', function (): void {
    foreach (stiqon_build_manifest() as $source => $entry) {
        if (basename($source) === 'instrument-sans-latin-wght-normal.woff2' && isset($entry['file'])) {
            printf(
                '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>'."\n",
                esc_url(get_theme_file_uri('assets/build/'.$entry['file'])),
            );
        }
    }
}, 1);

/**
 * Icons for browser tabs and home screens, and a description for the home page.
 */
add_action('wp_head', function (): void {
    printf('<link rel="icon" href="%s" sizes="any">'."\n", esc_url(get_theme_file_uri('assets/brand/favicon.ico')));
    printf('<link rel="icon" type="image/png" sizes="32x32" href="%s">'."\n", esc_url(get_theme_file_uri('assets/brand/icon-32.png')));
    printf('<link rel="apple-touch-icon" href="%s">'."\n", esc_url(get_theme_file_uri('assets/brand/icon-180.png')));
    echo '<meta name="theme-color" content="#ffffff">'."\n";

    if (is_front_page()) {
        echo '<meta name="description" content="Sticker und Flyer mit Ihrem Motiv: Bild wählen, Vorschau auf allen Formen und Formaten ansehen.">'."\n";
    }
}, 2);

/**
 * WordPress prints its own icon links when a site icon is set in the customizer;
 * the theme brings the icons itself.
 */
add_filter('get_site_icon_url', '__return_empty_string');

/**
 * The home page's title: name and what the shop is, instead of the tagline setting.
 */
add_filter('document_title_parts', function (array $parts): array {
    if (is_front_page()) {
        return ['title' => 'Stiqon', 'tagline' => 'Sticker und Flyer mit Ihrem Motiv'];
    }

    return $parts;
});

add_filter('document_title_separator', static fn (): string => '–');

/**
 * The sample sheet is a design document; WordPress' toolbar for logged-in users
 * would sit on top of what is being judged.
 */
add_filter('show_admin_bar', static fn (bool $show): bool => is_page('musterbogen') ? false : $show);
