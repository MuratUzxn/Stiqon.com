<?php
/**
 * Site-wide data and what the theme adds to the document head.
 */

defined('ABSPATH') || exit;

/**
 * The pages this theme has templates for (address => title). They have to exist
 * in WordPress under exactly these addresses.
 *
 * @return array<string, string>
 */
function stiqon_required_pages(): array
{
    return ['sticker' => 'Sticker', 'flyer' => 'Flyer'];
}

/**
 * Address of one of those pages. It is taken from the page itself, so it is
 * right under every permalink setting; the plain path is only the fallback
 * for a page that does not exist yet.
 */
function stiqon_page_url(string $slug): string
{
    $page = get_page_by_path($slug);

    return $page instanceof WP_Post && $page->post_status === 'publish'
        ? (string) get_permalink($page)
        : home_url("/{$slug}/");
}

/**
 * The areas of the shop, for navigation.
 *
 * @return list<array{slug: string, label: string, url: string, current: bool}>
 */
function stiqon_areas(): array
{
    $areas = [];

    foreach (stiqon_required_pages() as $slug => $label) {
        $areas[] = [
            'slug' => $slug,
            'label' => $label,
            'url' => stiqon_page_url($slug),
            'current' => is_page($slug),
        ];
    }

    return $areas;
}

/**
 * What the header and the footer link to: the areas and, once there is one to
 * show, the references.
 *
 * @return list<array{slug: string, label: string, url: string, current: bool}>
 */
function stiqon_navigation(): array
{
    $items = stiqon_areas();
    $references = stiqon_references_url();

    if ($references !== null) {
        $items[] = ['slug' => 'referenzen', 'label' => 'Referenzen', 'url' => $references, 'current' => is_post_type_archive('stiqon_referenz')];
    }

    return $items;
}

/**
 * The cart, for the header: where it is and how much is in it. Null without
 * WooCommerce.
 *
 * "hash" is WooCommerce's fingerprint of the cart, the one it also keeps in a
 * cookie; "source" is where the number can be asked for again. With the two,
 * the page's script notices a page that is older than the cart – taken from the
 * browser's memory by "Back", or kept by a cache in front of the shop – and
 * puts the number right (assets/src/js/app.js).
 *
 * @return array{url: string, count: int, label: string, hash: string, source: string}|null
 */
function stiqon_cart_link(): ?array
{
    if (! function_exists('WC') || ! function_exists('wc_get_cart_url')) {
        return null;
    }

    $count = WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;

    return [
        'url' => wc_get_cart_url(),
        'count' => $count,
        'label' => match ($count) {
            0 => 'Warenkorb, leer',
            1 => 'Warenkorb, 1 Artikel',
            default => "Warenkorb, {$count} Artikel",
        },
        'hash' => WC()->cart ? (string) WC()->cart->get_cart_hash() : '',
        'source' => rest_url('wc/store/v1/cart'),
    ];
}

/**
 * The legal pages the shop has, for the footer: terms, the right to withdraw,
 * shipping and payment, privacy, imprint – whichever of them are set in
 * WooCommerce and Germanized and published. A page that serves two purposes
 * is listed once.
 *
 * @param  list<string>  $purposes  which of them; see stiqon_legal_pages()
 * @return list<array{label: string, url: string}>
 */
function stiqon_legal_links(array $purposes = ['terms', 'revocation', 'shipping_costs', 'payment_methods', 'data_security', 'imprint']): array
{
    $links = [];

    foreach (stiqon_legal_pages($purposes) as $id) {
        $links[] = ['label' => get_the_title($id), 'url' => (string) get_permalink($id)];
    }

    return $links;
}

/**
 * The published pages set in WooCommerce and Germanized for the given
 * purposes, each once, in the order asked for.
 *
 * @param  list<string>  $purposes  "terms", "revocation", "shipping_costs", "payment_methods", "data_security", "imprint"
 * @return list<int>
 */
function stiqon_legal_pages(array $purposes): array
{
    $pages = [];

    foreach ($purposes as $purpose) {
        // Which page serves a purpose is a plain setting. It is read directly while WooCommerce
        // is switched off: a site that is online names who runs it and how it treats data.
        $id = function_exists('wc_get_page_id') ? (int) wc_get_page_id($purpose) : (int) get_option("woocommerce_{$purpose}_page_id");

        if ($id <= 0 && $purpose === 'data_security') {
            $id = (int) get_option('wp_page_for_privacy_policy');
        }

        if ($id > 0 && get_post_status($id) === 'publish') {
            $pages[$id] = $id;
        }
    }

    return array_values($pages);
}

/**
 * The way to withdraw from a contract online, which a shop has to offer on
 * every page. Germanized provides the page and its form; null while there is
 * no such page.
 *
 * @return array{label: string, url: string}|null
 */
function stiqon_withdrawal_link(): ?array
{
    if (! function_exists('eu_owb_has_public_withdrawal_page') || ! eu_owb_has_public_withdrawal_page()) {
        return null;
    }

    $url = (string) eu_owb_get_withdrawal_page_permalink();

    return $url === '' ? null : ['label' => (string) eu_owb_get_withdrawal_button_text(), 'url' => $url];
}

/**
 * A visitor's motif is kept in the browser and appears on the next page a moment
 * after it loads – without this, the sample motifs would flash up first. This
 * runs before anything is painted: if a motif is stored, the samples are held
 * back (see app.css) until the page's script has put the motif in their place.
 * Should that script never run, the samples come back by themselves.
 */
add_action('wp_head', function (): void {
    wp_print_inline_script_tag(
        "try{if(localStorage.getItem('stiqon:motif')){document.documentElement.setAttribute('data-motif','pending');"
        ."setTimeout(function(){document.documentElement.removeAttribute('data-motif')},1500)}}catch(e){}",
    );
}, 0);

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
 * The theme brings its own icons. Once a site icon is chosen under
 * Einstellungen › Allgemein, WordPress prints that one and the theme's stay out.
 */
add_action('wp_head', function (): void {
    if (! has_site_icon()) {
        printf('<link rel="icon" href="%s" sizes="any">'."\n", esc_url(get_theme_file_uri('assets/brand/favicon.ico')));
        printf('<link rel="icon" type="image/png" sizes="32x32" href="%s">'."\n", esc_url(get_theme_file_uri('assets/brand/icon-32.png')));
        printf('<link rel="apple-touch-icon" href="%s">'."\n", esc_url(get_theme_file_uri('assets/brand/icon-180.png')));
    }

    echo '<meta name="theme-color" content="#ffffff">'."\n";

    if (is_front_page()) {
        echo '<meta name="description" content="Sticker und Flyer mit Ihrem Motiv: Bild wählen, Vorschau auf allen Formen und Formaten ansehen.">'."\n";
    }
}, 2);

/**
 * Browsers ask for /favicon.ico wherever a page names no icon: the login page,
 * the administration, files opened directly. Without a site icon WordPress
 * would answer with its own logo.
 */
add_action('do_faviconico', function (): void {
    if (! has_site_icon()) {
        wp_safe_redirect(get_theme_file_uri('assets/brand/favicon.ico'));
        exit;
    }
});

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
 * The sample sheet is a working document for whoever looks after the site's
 * appearance. The page is meant to be private, but a page is quickly published
 * by mistake – so the template does not rely on that: everybody else gets the
 * "not found" page.
 */
add_action('template_redirect', function (): void {
    if (is_page('musterbogen') && ! current_user_can('edit_theme_options')) {
        global $wp_query;

        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }
}, 1);

/**
 * WordPress' toolbar for logged-in users would sit on top of what is being judged there.
 */
add_filter('show_admin_bar', static fn (bool $show): bool => is_page('musterbogen') ? false : $show);
