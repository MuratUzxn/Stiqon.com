<?php
/**
 * Search. It looks through the shop's own index – areas, products and their
 * variants, see the plugin "Stiqon Shop-Funktionen" – and not, as WordPress
 * would, through posts and pages.
 *
 * A visitor reaches it two ways: the page's script opens it over the page
 * (template-parts/search-panel.php) and lists what fits while they type; and
 * search.php is the results page, which works without any script.
 */

defined('ABSPATH') || exit;

/** The longest query that is looked for and shown back. */
const STIQON_SEARCH_MAX_LENGTH = 80;

/**
 * What the visitor searched for: on one line, and of limited length.
 */
function stiqon_search_query(): string
{
    $query = trim((string) preg_replace('/\s+/u', ' ', (string) get_search_query(false)));

    return mb_substr($query, 0, STIQON_SEARCH_MAX_LENGTH);
}

/**
 * Address of the results page for a query; without one, the page that only
 * holds the search field.
 */
function stiqon_search_url(string $query = ''): string
{
    return home_url('/?s='.rawurlencode($query));
}

/**
 * The search field of the results page: a plain form that works without script.
 */
function stiqon_search_form(string $query = '', string $class = ''): string
{
    return '<form role="search" action="'.esc_url(home_url('/')).'" method="get" class="'.esc_attr(stiqon_classes(['relative flex items-center', $class])).'">'
        .'<label for="suchfeld" class="pointer-events-none absolute left-5 text-muted"><span class="sr-only">Suchbegriff</span>'.stiqon_icon('search', 'size-6').'</label>'
        .'<input id="suchfeld" type="search" name="s" value="'.esc_attr($query).'" maxlength="'.STIQON_SEARCH_MAX_LENGTH.'"'
        .' placeholder="Visitenkarten, Plakate, Material …" autocomplete="off" autocapitalize="off" spellcheck="false" enterkeyhint="search"'
        .' class="h-14 w-full min-w-0 rounded-full bg-ink/8 pr-16 pl-14 text-lg font-medium placeholder:font-normal placeholder:text-muted">'
        .'<button type="submit" aria-label="Suchen" class="absolute right-1.5 inline-flex size-11 items-center justify-center rounded-full bg-ink text-paper transition-colors duration-200 ease-soft hover:bg-ink-soft">'
        .stiqon_icon('arrow-right').'</button>'
        .'</form>';
}

/**
 * WordPress would look through posts and pages for the query. The results page
 * shows none of that, so the database is not asked.
 */
add_filter('posts_pre_query', static function (?array $posts, WP_Query $query): ?array {
    return ! is_admin() && $query->is_main_query() && $query->is_search() ? [] : $posts;
}, 10, 2);

add_filter('document_title_parts', static function (array $parts): array {
    if (is_search()) {
        $query = stiqon_search_query();
        $parts['title'] = $query === '' ? 'Suche' : sprintf('Suche nach „%s“', $query);
    }

    return $parts;
});

/**
 * WooCommerce's own search field, an old link or a widget ask for products
 * only ("post_type=product"). There is one search in this shop; such a request
 * is sent on to its results page instead of an empty list of products.
 */
add_action('template_redirect', static function (): void {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only whether the parameter is there.
    if (is_search() && isset($_GET['post_type'])) {
        wp_safe_redirect(stiqon_search_url(stiqon_search_query()), 301);
        exit;
    }
});

/**
 * The areas of the shop are pages of this theme. What search finds leads
 * there directly. A category without such a page is no area: its archive
 * only redirects to the home page (inc/shop.php).
 */
add_filter('stiqon_shop_area_url', static function (mixed $url, WP_Term $term): string {
    return isset(stiqon_required_pages()[$term->slug]) ? stiqon_page_url($term->slug) : '';
}, 10, 2);
