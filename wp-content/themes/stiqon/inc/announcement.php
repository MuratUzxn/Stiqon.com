<?php
/**
 * Before the shop opens.
 *
 * WooCommerce can announce a shop instead of showing it ("Demnächst
 * verfügbar", under WooCommerce › Einstellungen › Website-Sichtbarkeit): on
 * every page of the site, or on the pages of the shop only. Who sees the
 * announcement and who the shop – its managers, visitors with the private
 * link – stays WooCommerce's decision. What the announcement looks like and
 * which pages it covers is settled here.
 */

defined('ABSPATH') || exit;

/**
 * WooCommerce's announcement is a page of blocks made for block themes: the
 * site's name as text, a link to log in, icons of social networks the shop is
 * not on. This theme says the same in its own look. On the pages of the shop
 * it stands between the theme's header and footer; for the whole site it is
 * the page, and names what has to stay reachable on any site that is online.
 */
add_filter('render_block_woocommerce/coming-soon', static function (mixed $html, mixed $block): string {
    if (! empty($block['attrs']['storeOnly'])) {
        return '<section class="wrap py-24 text-center sm:py-32">'
            .'<h1 class="text-title">Der Shop öffnet bald.</h1>'
            .'<p class="text-lead mx-auto mt-4 max-w-xl text-muted">Wir richten gerade alles ein. In Kürze können Sie hier Sticker und Flyer mit Ihrem Motiv bestellen.</p>'
            .'</section>';
    }

    $links = '';

    foreach (stiqon_legal_links(['imprint', 'data_security']) as $link) {
        $links .= '<a href="'.esc_url($link['url']).'" class="rounded-sm underline underline-offset-4 transition-colors hover:text-ink">'.esc_html($link['label']).'</a>';
    }

    return '<div class="flex min-h-svh flex-col bg-paper text-ink">'
        .'<header class="wrap py-6">'.stiqon_logo().'</header>'
        .'<main class="wrap flex flex-1 flex-col items-center justify-center py-16 text-center">'
        .'<h1 class="text-title">Stiqon öffnet bald.</h1>'
        .'<p class="text-lead mt-4 max-w-xl text-muted">Sticker und Flyer mit Ihrem Motiv – erst ansehen, dann bestellen. Wir richten gerade alles ein.</p>'
        .'</main>'
        .'<footer class="wrap text-label flex flex-wrap justify-center gap-x-6 gap-y-2 py-8 text-muted">'
        .'<span>© '.esc_html(wp_date('Y')).' Stiqon</span>'.$links
        .'</footer>'
        .'</div>';
}, 10, 2);

/**
 * Imprint and privacy policy are never replaced by the announcement: a site
 * that is online has to show who runs it, also before its shop opens.
 */
add_filter('woocommerce_coming_soon_exclude', static function (mixed $excluded): mixed {
    $pages = stiqon_legal_pages(['imprint', 'data_security']);

    return $excluded || ($pages !== [] && is_page($pages));
});

/**
 * Announced "on the pages of the shop only", WooCommerce works out which pages
 * those are from its own: shop, product, cart, checkout. In this shop the
 * products are shown on the area pages – so they announce the shop as well,
 * instead of showing the assortment with its prices.
 */
add_filter('woocommerce_store_pages', static function (mixed $pages): mixed {
    if (! is_array($pages)) {
        return $pages;
    }

    foreach (array_keys(stiqon_required_pages()) as $slug) {
        $page = get_page_by_path($slug);

        if ($page instanceof WP_Post) {
            $pages['stiqon_'.$slug] = $page->ID;
        }
    }

    return $pages;
});
