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
 *
 * The block is answered before WordPress renders it: what WooCommerce put
 * inside – a cover, a row of social icons – is then never built, and brings
 * none of its stylesheets.
 */
add_filter('pre_render_block', static function (mixed $rendered, mixed $block): mixed {
    if (! is_array($block) || ($block['blockName'] ?? '') !== 'woocommerce/coming-soon') {
        return $rendered;
    }

    if (! empty($block['attrs']['storeOnly'])) {
        return '<section class="wrap py-24 text-center sm:py-32">'
            .'<h1 class="text-title">Der Shop öffnet bald.</h1>'
            .'<p class="text-lead mx-auto mt-4 max-w-xl text-muted">Wir richten gerade alles ein. In Kürze können Sie hier Sticker und Flyer mit Ihrem Motiv bestellen.</p>'
            .'</section>';
    }

    // The page for the whole site is built before its head is printed, and it is all the
    // theme's own markup: it does without what WooCommerce loads on every page (inc/cleanup.php).
    // On the pages of the shop the head is out already when the announcement is built.
    stiqon_declare_lean_page();

    return '<div class="flex min-h-svh flex-col bg-paper text-ink">'
        .'<header class="wrap py-6">'.stiqon_logo().'</header>'
        .'<main class="wrap flex flex-1 flex-col items-center justify-center py-16 text-center">'
        .'<h1 class="text-title">Stiqon öffnet bald.</h1>'
        .'<p class="text-lead mt-4 max-w-xl text-muted">Sticker und Flyer mit Ihrem Motiv – erst ansehen, dann bestellen. Wir richten gerade alles ein.</p>'
        .'</main>'
        .'<footer class="wrap text-label flex flex-wrap justify-center gap-x-6 gap-y-2 py-8 text-muted">'.stiqon_announcement_footer().'</footer>'
        .'</div>';
}, 10, 2);

/**
 * What stands at the foot of the announcement: the year, and the two pages
 * that stay readable.
 */
function stiqon_announcement_footer(): string
{
    $html = '<span>© '.esc_html(wp_date('Y')).' Stiqon</span>';

    foreach (stiqon_legal_links(['imprint', 'data_security']) as $link) {
        $html .= '<a href="'.esc_url($link['url']).'" class="rounded-sm underline underline-offset-4 transition-colors hover:text-ink">'.esc_html($link['label']).'</a>';
    }

    return $html;
}

/**
 * Whether the whole site is announced to this visitor: WooCommerce's "coming
 * soon" for every page, and the visitor is none of those it lets in – who
 * manages the shop, or holds its private link. (For the pages of the shop
 * only, WooCommerce decides page by page; nothing here needs to know.)
 */
function stiqon_site_is_announced(): bool
{
    if (get_option('woocommerce_coming_soon') !== 'yes' || get_option('woocommerce_store_pages_only') === 'yes' || current_user_can('manage_woocommerce')) {
        return false;
    }

    $key = (string) get_option('woocommerce_share_key');

    if (get_option('woocommerce_private_link') === 'yes' && $key !== '') {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- compared with the key WooCommerce keeps.
        foreach ([$_GET['woo-share'] ?? null, $_COOKIE['woo-share'] ?? null] as $shown) {
            if (is_string($shown) && hash_equals($key, wp_unslash($shown))) {
                return false;
            }
        }
    }

    return true;
}

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

/**
 * While WooCommerce shows the announcement in place of a page, the page is not
 * to be found under its own name: search engines are asked to leave it out,
 * and its title is the announcement's. WooCommerce draws the announcement from
 * within the filter "template_include" (ComingSoonRequestHandler) – that is
 * how it is told apart from the page itself.
 */
function stiqon_announcement_is_shown(): bool
{
    return doing_filter('template_include') && get_option('woocommerce_coming_soon') === 'yes';
}

add_filter('wp_robots', static function (mixed $robots): mixed {
    return is_array($robots) && stiqon_announcement_is_shown() ? wp_robots_no_robots($robots) : $robots;
});

add_filter('pre_get_document_title', static function (mixed $title): mixed {
    return stiqon_announcement_is_shown() ? get_bloginfo('name').' öffnet bald' : $title;
});
