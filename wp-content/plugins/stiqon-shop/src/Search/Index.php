<?php

namespace Stiqon\Shop\Search;

use Stiqon\Shop\Catalog\Gallery;
use Stiqon\Shop\Catalog\ProductView;
use Stiqon\Shop\Plugin;
use WC_Product;
use WP_REST_Request;
use WP_REST_Response;
use WP_Term;

/**
 * What the shop's search looks through: one entry per area (a category of
 * products), per product and per variant a visitor can see – "Runde Sticker"
 * on each material.
 *
 * The results page asks find(). The page's script fetches the same entries
 * from the address route() sets up and ranks them in the browser, so results
 * appear while a visitor types.
 */
final class Index
{
    public const REST_NAMESPACE = 'stiqon/v1';

    public const REST_ROUTE = '/suchindex';

    /** What these kinds of products are called besides their names. */
    private const ALSO_CALLED = [
        'sticker' => 'Aufkleber Klebeetiketten Etiketten Labels',
        'flyer' => 'Flugblatt Flugblätter Handzettel Werbezettel Prospekt',
    ];

    /** The fields of an entry the page's script gets. The others are for the results page. */
    private const PUBLISHED = ['n', 's', 'u', 'p', 'g', 't', 'k', 'w', 'm'];

    private const CACHE = 'stiqon_search_index';

    /** A change that none of the hooks below reports shows after this long at the latest. */
    private const CACHE_SECONDS = 3600;

    private const CHANGES = [
        // A product or one of its variations was saved or deleted.
        'woocommerce_delete_product_transients',
        // Names of categories and options.
        'created_term', 'edited_term', 'delete_term',
        // Addresses: pages a theme sends areas to, and how addresses are built.
        'save_post_page', 'update_option_permalink_structure', 'update_option_woocommerce_permalinks', 'switch_theme',
        // A new version of a theme or plugin may gather the entries differently.
        'upgrader_process_complete',
    ];

    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'route']);

        foreach (self::CHANGES as $hook) {
            add_action($hook, [self::class, 'forget'], 10, 0);
        }
    }

    /**
     * All entries, areas first, then each product followed by its variants.
     *
     * Besides what Entry::searchable() says is compared, an entry holds what a
     * result shows – "n" name, "s" line below it, "u" address, "p" price as
     * text, "g" which small shape stands for it – and, for products and
     * variants, what a card on the results page is drawn from.
     *
     * @return list<array<string, mixed>>
     */
    public static function entries(): array
    {
        if (! function_exists('wc_get_products')) {
            return [];
        }

        // Addresses are part of the entries: a shop that moved must not serve the old ones.
        $signature = md5(home_url('/').'|'.Plugin::VERSION.'|'.get_locale());
        $cached = get_transient(self::CACHE);

        if (is_array($cached) && ($cached['signature'] ?? null) === $signature && is_array($cached['entries'] ?? null)) {
            return $cached['entries'];
        }

        $entries = self::build();

        set_transient(self::CACHE, ['signature' => $signature, 'entries' => $entries], self::CACHE_SECONDS);

        return $entries;
    }

    public static function forget(): void
    {
        delete_transient(self::CACHE);
    }

    /**
     * What a query finds, best first: areas, and cards in the form the gallery
     * hands them to the theme (see Catalog\Gallery).
     *
     * @return array{query: string, total: int, areas: list<array{name: string, note: string, url: string}>, cards: list<array<string, mixed>>}
     */
    public static function find(string $query, int $limit): array
    {
        $entries = self::concealed() ? [] : self::entries();
        $hits = Matcher::rank($entries, $query);
        $found = ['query' => $query, 'total' => count($hits), 'areas' => [], 'cards' => []];

        foreach (array_slice($hits, 0, max(0, $limit)) as $position) {
            $entry = $entries[$position];

            if ($entry['kind'] === 'area') {
                $found['areas'][] = ['name' => $entry['n'], 'note' => $entry['s'], 'url' => $entry['u']];

                continue;
            }

            $product = wc_get_product($entry['id']);

            // Gone since the entries were gathered.
            if (! $product instanceof WC_Product) {
                $found['total']--;

                continue;
            }

            $found['cards'][] = [
                'key' => $entry['slug'].($entry['option'] !== null ? '--'.$entry['option'] : ''),
                'product' => $product,
                'slug' => $entry['slug'],
                'title' => $entry['n'],
                'summary' => $entry['summary'],
                'option' => $entry['option'],
                // A card names the option it is drawn with; a product's area goes without saying.
                'subtitle' => $entry['kind'] === 'variant' ? $entry['s'] : null,
                'chosen' => $entry['chosen'],
                'url' => $entry['u'],
                'price' => $entry['price'],
            ];
        }

        return $found;
    }

    public static function route(): void
    {
        register_rest_route(self::REST_NAMESPACE, self::REST_ROUTE, [
            'methods' => 'GET',
            'callback' => [self::class, 'respond'],
            // Nothing in it that the shop's pages do not show to everyone.
            'permission_callback' => '__return_true',
        ]);
    }

    public static function respond(WP_REST_Request $request): WP_REST_Response
    {
        // Before the shop opens the answer depends on who asks, so nobody may keep it for the next one.
        if (get_option('woocommerce_coming_soon') === 'yes') {
            $response = new WP_REST_Response(['entries' => self::concealed() ? [] : self::published()]);
            $response->header('Cache-Control', 'no-store');

            return $response;
        }

        $body = ['entries' => self::published()];
        $tag = '"'.md5((string) wp_json_encode($body)).'"';

        // A browser that has the entries asks whether they changed. A server that
        // compresses responses may have handed the tag on marked as weak ("W/").
        $known = array_map(
            static fn (string $sent): string => (string) preg_replace('~^W/~', '', trim($sent)),
            explode(',', (string) $request->get_header('if_none_match')),
        );
        $unchanged = in_array($tag, $known, true);

        $response = new WP_REST_Response($unchanged ? null : $body, $unchanged ? 304 : 200);
        $response->header('ETag', $tag);
        $response->header('Cache-Control', 'public, max-age=300');

        return $response;
    }

    /**
     * The entries as the page's script gets them.
     *
     * @return list<array<string, mixed>>
     */
    private static function published(): array
    {
        $published = array_flip(self::PUBLISHED);

        return array_map(
            static fn (array $entry): array => array_filter(array_intersect_key($entry, $published), static fn (mixed $value): bool => $value !== ''),
            self::entries(),
        );
    }

    /**
     * Before a shop opens, WooCommerce shows visitors a "coming soon" page in
     * place of the shop's pages. Search must not tell them more than those
     * pages do. Whoever WooCommerce lets see the shop – its managers, and
     * visitors who were given the private link – can search it too.
     */
    private static function concealed(): bool
    {
        if (get_option('woocommerce_coming_soon') !== 'yes' || current_user_can('manage_woocommerce')) {
            return false;
        }

        /** This filter is documented in WooCommerce: src/Internal/ComingSoon/ComingSoonRequestHandler.php */
        if (apply_filters('woocommerce_coming_soon_exclude', false)) {
            return false;
        }

        $key = (string) get_option('woocommerce_share_key');
        $shown = isset($_COOKIE['woo-share']) && is_string($_COOKIE['woo-share']) ? wp_unslash($_COOKIE['woo-share']) : '';

        return ! (get_option('woocommerce_private_link') === 'yes' && $key !== '' && hash_equals($key, $shown));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function build(): array
    {
        $products = wc_get_products([
            'status' => 'publish',
            'visibility' => 'search',
            'limit' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ]);

        $areas = [];
        $entries = [];

        foreach ($products as $product) {
            $view = ProductView::resolve($product);
            $type = $view['type'] ?? '';
            $form = $view['spec'][$type === 'sticker' ? 'shape' : 'format'] ?? null;
            $visible = ProductView::visible($product);
            $cards = Gallery::cards($product, $visible);
            $area = self::area($product);
            $prices = array_filter(array_column($cards, 'price'), 'is_float');
            $keywords = implode(' ', array_filter([self::ALSO_CALLED[$type] ?? '', $area?->name, $cards[0]['summary']]));

            if ($area !== null) {
                $areas[$area->term_id] ??= ['term' => $area, 'types' => [], 'products' => 0];
                $areas[$area->term_id]['types'][$type] = true;
                $areas[$area->term_id]['products']++;
            }

            $shared = [
                'g' => $type.(is_string($form) ? '-'.$form : ''),
                'id' => $product->get_id(),
                'slug' => $product->get_slug(),
                'summary' => $cards[0]['summary'],
            ];

            $entries[] = self::entry('product', $product->get_name(), $area?->name ?? '', $keywords, $shared + [
                'u' => $product->get_permalink(),
                'price' => $prices === [] ? null : min($prices),
                'option' => null,
                'chosen' => [],
            ]);

            if ($visible === null) {
                continue;
            }

            foreach ($cards as $card) {
                $entries[] = self::entry('variant', $card['title'], (string) $card['subtitle'], $visible['label'].' '.$keywords, $shared + [
                    'u' => $card['url'],
                    'price' => $card['price'],
                    'option' => $card['option'],
                    'chosen' => $card['chosen'],
                ]);
            }
        }

        $first = [];

        foreach ($areas as $area) {
            /**
             * Address of an area of the shop. A theme that shows the products of a
             * category on a page of its own names that page here.
             *
             * @param  string|\WP_Error  $url  address of the category's archive
             * @param  WP_Term  $term  the product category
             */
            $url = apply_filters('stiqon_shop_area_url', get_term_link($area['term']), $area['term']);

            if (! is_string($url) || $url === '') {
                continue;
            }

            $types = array_values(array_filter(array_keys($area['types']), 'strlen'));

            $first[] = self::entry(
                'area',
                $area['term']->name,
                $area['products'] === 1 ? '1 Produkt' : $area['products'].' Produkte',
                implode(' ', array_map(static fn (string $type): string => self::ALSO_CALLED[$type] ?? '', $types)),
                ['u' => $url, 'price' => null, 'g' => count($types) === 1 ? $types[0] : ''],
            );
        }

        return array_merge($first, $entries);
    }

    /**
     * @param  array<string, mixed>  $more  address, price and whatever else the entry carries
     * @return array<string, mixed>
     */
    private static function entry(string $kind, string $name, string $subtitle, string $keywords, array $more): array
    {
        return [
            'kind' => $kind,
            'n' => $name,
            's' => $subtitle,
            // wc_price() returns markup; a result shows the price as text.
            'p' => isset($more['price']) ? 'ab '.html_entity_decode(wp_strip_all_tags(wc_price($more['price'])), ENT_QUOTES, 'UTF-8') : '',
        ] + $more + Entry::searchable($kind, $name, $subtitle, $keywords);
    }

    /**
     * The area a product belongs to: the top-level category above the first of
     * its categories. The category WooCommerce files products under that have
     * none is not an area.
     */
    private static function area(WC_Product $product): ?WP_Term
    {
        $terms = get_the_terms($product->get_id(), 'product_cat');
        $term = is_array($terms) ? ($terms[0] ?? null) : null;

        while ($term instanceof WP_Term && $term->parent > 0) {
            $term = get_term($term->parent, 'product_cat');
        }

        if (! $term instanceof WP_Term || $term->term_id === (int) get_option('default_product_cat')) {
            return null;
        }

        return $term;
    }
}
