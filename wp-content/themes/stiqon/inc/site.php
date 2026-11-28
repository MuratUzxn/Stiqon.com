<?php
/**
 * Site-wide data and what the theme adds to the document head.
 */

defined('ABSPATH') || exit;

/**
 * The areas of the shop, in the order the navigation shows them. Each is a
 * page under its own address and the product category of the same slug
 * (see Catalog\Areas in the plugin); every area page is drawn by
 * page-bereich.php.
 *
 *   label    name of the area, in the navigation and as the page's heading
 *   lead     the sentence under the heading
 *   related  the area its page points to next
 *   photo    picture of the range Stiqon supplied (assets/brand/sortiment/<photo>.webp) and its text
 *   drawing  what stands for the area where there is no photo: a kind of drawing and its spec
 *
 * The assortment follows printworld.com (November 2026): what a small business
 * orders from an online printer, from business card to roll-up.
 *
 * @return array<string, array{label: string, lead: string, related: string, photo?: array{file: string, alt: string}, drawing: array{type: string, spec: array<string, mixed>}}>
 */
function stiqon_area_definitions(): array
{
    return [
        'sticker' => [
            'label' => 'Sticker',
            'lead' => 'Rund, quadratisch, rechteckig, oval oder mit Konturschnitt – auf weißem Vinyl, transparenter und holografischer Folie oder Kraftpapier.',
            'related' => 'flyer',
            'photo' => ['file' => 'aufkleber', 'alt' => 'Aufkleber in vier Formen und auf der Rolle, mit Stiqon-Logo'],
            'drawing' => ['type' => 'sticker', 'spec' => ['shape' => 'circle', 'motif' => 'siegel']],
        ],
        'flyer' => [
            'label' => 'Flyer',
            'lead' => 'Von DIN A6 bis DIN A4, DIN lang und quadratisch – als Blatt oder gefalzt, auf leichtem oder festem Bilderdruckpapier.',
            'related' => 'broschueren',
            'drawing' => ['type' => 'flyer', 'spec' => ['format' => 'a5', 'motif' => 'eroeffnung', 'stack' => true]],
        ],
        'visitenkarten' => [
            'label' => 'Visitenkarten',
            'lead' => 'Klassisch auf 300 g Bilderdruck, mit Soft-Touch-Folie oder Goldprägung – auch als Klappvisitenkarte.',
            'related' => 'briefpapier',
            'photo' => ['file' => 'visitenkarten', 'alt' => 'Zwei Stapel Visitenkarten mit Stiqon-Logo'],
            'drawing' => ['type' => 'card', 'spec' => ['format' => 'visitenkarte', 'stack' => true]],
        ],
        'karten' => [
            'label' => 'Karten',
            'lead' => 'Postkarten, quadratische Karten und Klappkarten – für Grüße, Einladungen und ein Dankeschön.',
            'related' => 'visitenkarten',
            'photo' => ['file' => 'karten', 'alt' => 'Klappkarte und Karte mit Stiqon-Logo'],
            'drawing' => ['type' => 'card', 'spec' => ['format' => 'postkarte', 'fold' => true]],
        ],
        'briefpapier' => [
            'label' => 'Briefpapier',
            'lead' => 'Briefbogen, Umschläge, Notizblöcke und Präsentationsmappen – die Geschäftsausstattung in einem Look.',
            'related' => 'visitenkarten',
            'photo' => ['file' => 'briefpapier', 'alt' => 'Briefbogen, Umschlag und Karte mit Stiqon-Logo'],
            'drawing' => ['type' => 'letter', 'spec' => ['format' => 'a4', 'stack' => true]],
        ],
        'broschueren' => [
            'label' => 'Broschüren',
            'lead' => 'Geheftet oder klebegebunden, von 8 bis 96 Seiten – für Katalog, Magazin und Preisliste.',
            'related' => 'flyer',
            'photo' => ['file' => 'broschueren', 'alt' => 'Aufgeschlagene und geschlossene Broschüre mit Stiqon-Logo'],
            'drawing' => ['type' => 'brochure', 'spec' => ['format' => 'a5']],
        ],
        'plakate' => [
            'label' => 'Plakate',
            'lead' => 'Von DIN A3 bis DIN A0, auf Bilderdruck- oder Fotopapier – schon ab einem Stück.',
            'related' => 'werbetechnik',
            'photo' => ['file' => 'plakate', 'alt' => 'Plakat mit Stiqon-Logo'],
            'drawing' => ['type' => 'poster', 'spec' => ['format' => 'a1']],
        ],
        'kalender' => [
            'label' => 'Kalender',
            'lead' => 'Wandkalender, Tischkalender und Jahresplaner – zwölf Monate mit Ihrem Motiv.',
            'related' => 'karten',
            'photo' => ['file' => 'kalender', 'alt' => 'Tischkalender mit Stiqon-Logo'],
            'drawing' => ['type' => 'calendar', 'spec' => ['variant' => 'wall']],
        ],
        'werbetechnik' => [
            'label' => 'Werbetechnik',
            'lead' => 'Roll-ups, Banner und Kundenstopper – für Messe, Ladenlokal und Straße.',
            'related' => 'plakate',
            'photo' => ['file' => 'werbetechnik', 'alt' => 'Roll-up und Kundenstopper mit Stiqon-Logo'],
            // The narrow roll-up crops lettering hard: a motif drawn around its centre suits it.
            'drawing' => ['type' => 'rollup', 'spec' => ['format' => '85x200', 'motif' => 'prozent']],
        ],
        'verpackungen' => [
            'label' => 'Verpackungen',
            'lead' => 'Versandkartons und Papiertragetaschen mit Ihrem Logo – damit schon das Paket nach Ihnen aussieht.',
            'related' => 'sticker',
            'photo' => ['file' => 'verpackungen', 'alt' => 'Versandkarton und Papiertüte mit Stiqon-Logo'],
            'drawing' => ['type' => 'box', 'spec' => ['size' => 'm']],
        ],
        'textilien' => [
            'label' => 'Textilien',
            'lead' => 'T-Shirts, Hoodies und Baumwolltaschen, bedruckt mit Ihrem Motiv.',
            'related' => 'werbeartikel',
            'photo' => ['file' => 'textilien', 'alt' => 'Zwei gefaltete T-Shirts mit Stiqon-Logo'],
            'drawing' => ['type' => 'shirt', 'spec' => ['cut' => 'tshirt']],
        ],
        'werbeartikel' => [
            'label' => 'Werbeartikel',
            'lead' => 'Tassen, Trinkflaschen und Bierdeckel – kleine Dinge, die lange bleiben.',
            'related' => 'textilien',
            'drawing' => ['type' => 'mug', 'spec' => ['style' => 'classic']],
        ],
    ];
}

/**
 * The pages this theme has templates for (address => title): the areas. They
 * have to exist in WordPress under exactly these addresses.
 *
 * @return array<string, string>
 */
function stiqon_required_pages(): array
{
    return array_map(static fn (array $area): string => $area['label'], stiqon_area_definitions());
}

/**
 * The area page that is being shown, or null on any other page.
 *
 * @return array{slug: string, label: string, lead: string, related: string, photo?: array{file: string, alt: string}, drawing: array{type: string, spec: array<string, mixed>}}|null
 */
function stiqon_current_area(): ?array
{
    $page = get_queried_object();

    if (! $page instanceof WP_Post || $page->post_type !== 'page') {
        return null;
    }

    $area = stiqon_area_definitions()[$page->post_name] ?? null;

    return $area === null ? null : ['slug' => $page->post_name] + $area;
}

/**
 * Every area page is drawn by page-bereich.php – unless the theme brings a
 * template of its own for one of them (page-<slug>.php comes first).
 */
add_filter('page_template_hierarchy', static function (mixed $templates): mixed {
    $page = get_queried_object();

    if (! is_array($templates) || ! $page instanceof WP_Post || ! isset(stiqon_area_definitions()[$page->post_name])) {
        return $templates;
    }

    $specific = array_search("page-{$page->post_name}.php", $templates, true);
    $at = $specific === false ? 0 : $specific + 1;

    array_splice($templates, $at, 0, ['page-bereich.php']);

    return $templates;
});

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
        echo '<meta name="description" content="Werbemittel mit Ihrem Motiv – Sticker, Flyer, Visitenkarten, Plakate, Kalender, Textilien und mehr: Bild wählen, Vorschau auf jedem Produkt ansehen.">'."\n";
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
        return ['title' => 'Stiqon', 'tagline' => 'Werbemittel mit Ihrem Motiv'];
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

/**
 * Where a customer finds the account – or null without WooCommerce or without
 * an account page.
 */
function stiqon_account_url(): ?string
{
    if (! function_exists('wc_get_page_id') || wc_get_page_id('myaccount') <= 0) {
        return null;
    }

    return (string) get_permalink(wc_get_page_id('myaccount'));
}
