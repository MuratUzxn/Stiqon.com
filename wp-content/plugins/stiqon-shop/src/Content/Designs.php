<?php

namespace Stiqon\Shop\Content;

use WP_Error;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_Term;

/**
 * Designs: ready-made motifs a visitor can try on the shop's products – the
 * register "Design" beside "Produkte", modelled on mobbin.com's "Apps" and
 * "Sites".
 *
 * Each design has a preview image, a medium it is made for ("Werbemittel":
 * sticker or flyer – the terms carry the slugs of the area pages) and an
 * occasion ("Anlass"). They are listed at /design/, filtered by query arguments
 * (?werbemittel=…&anlass=…) so the filters are plain links, and each has a page
 * of its own at /design/<name>/.
 *
 * Designs are made with an image generator after models found elsewhere. That
 * is why one is shown only once it is published, has its preview image and has
 * been released by a person who checked it is not too close to its models or
 * to anybody's brand (see shown()). Until then it is not listed, not counted in
 * the filters, and its address does not exist.
 */
final class Designs
{
    public const TYPE = 'stiqon_design';

    public const ARCHIVE = 'design';

    /** Werbemittel: the slugs are those of the area pages, "sticker" and "flyer". */
    public const MEDIUM = 'stiqon_design_medium';

    public const OCCASION = 'stiqon_design_occasion';

    /** "1" once the checks for similarity and brands are done. */
    public const RELEASED = '_stiqon_design_released';

    /** Number of the job at Higgsfield the image came from. */
    public const JOB = '_stiqon_design_job';

    /** The models: one per line, an address and its class (A or B). */
    public const MODELS = '_stiqon_design_models';

    /** What the person who checked the design noted. */
    public const REVIEW = '_stiqon_design_review';

    /** Query arguments of the list's filters. */
    public const FILTERS = ['werbemittel' => self::MEDIUM, 'anlass' => self::OCCASION];

    public const PER_PAGE = 24;

    /** Preview of a design: fitted into 4 to 5, never cropped – a motif loses nothing at its edges. */
    public const IMAGE = 'stiqon-design';

    /** Says which addresses WordPress has learnt from this class; changes when they do. */
    private const RULES = 'design-1';

    private const RULES_OPTION = 'stiqon_shop_design_rules';

    private const NONCE = 'stiqon_design_provenance';

    /** @var list<int>|null */
    private static ?array $shownIds = null;

    /** @var array{designs: list<int>, images: list<int>}|null */
    private static ?array $hidden = null;

    public static function register(): void
    {
        add_action('init', [self::class, 'types']);
        add_action('init', [self::class, 'learnAddresses'], 99);
        add_action('after_setup_theme', [self::class, 'imageSize']);
        add_filter('the_posts', [self::class, 'onlyShown'], 10, 2);
        add_filter('redirect_canonical', [self::class, 'noWayToHidden']);
        add_action('pre_get_posts', [self::class, 'listed']);
        add_action('save_post_'.self::TYPE, [self::class, 'forgetShown']);
        add_action('deleted_post', [self::class, 'forgetShown']);
        add_filter('wp_sitemaps_post_types', [self::class, 'notInSitemap']);
        add_filter('rest_'.self::TYPE.'_query', [self::class, 'restQuery']);
        add_filter('rest_post_search_query', [self::class, 'restSearch']);
        add_filter('rest_attachment_query', [self::class, 'restMedia']);
        add_filter('rest_request_before_callbacks', [self::class, 'restItem'], 10, 3);

        foreach (self::FILTERS as $taxonomy) {
            add_filter('rest_'.$taxonomy.'_query', [self::class, 'restTerms']);
            add_filter('rest_prepare_'.$taxonomy, [self::class, 'restTermCount'], 10, 2);
        }

        add_filter('oembed_request_post_id', [self::class, 'noEmbed']);
        add_filter('use_block_editor_for_post_type', [self::class, 'classicForm'], 10, 2);
        add_action('edit_form_after_title', [self::class, 'hint']);
        add_action('add_meta_boxes_'.self::TYPE, [self::class, 'metaBox']);
        add_action('save_post_'.self::TYPE, [self::class, 'saveProvenance'], 10, 2);
        add_filter('manage_'.self::TYPE.'_posts_columns', [self::class, 'columns']);
        add_action('manage_'.self::TYPE.'_posts_custom_column', [self::class, 'column'], 10, 2);
    }

    public static function types(): void
    {
        register_post_type(self::TYPE, [
            'labels' => [
                'name' => 'Designs',
                'singular_name' => 'Design',
                'menu_name' => 'Designs',
                'add_new' => 'Hinzufügen',
                'add_new_item' => 'Design hinzufügen',
                'edit_item' => 'Design bearbeiten',
                'new_item' => 'Neues Design',
                'view_item' => 'Design ansehen',
                'view_items' => 'Designs ansehen',
                'search_items' => 'Designs durchsuchen',
                'not_found' => 'Noch keine Designs.',
                'not_found_in_trash' => 'Keine Designs im Papierkorb.',
                'all_items' => 'Alle Designs',
                'archives' => 'Designs',
                'featured_image' => 'Vorschaubild',
                'set_featured_image' => 'Vorschaubild festlegen',
                'remove_featured_image' => 'Vorschaubild entfernen',
                'use_featured_image' => 'Als Vorschaubild verwenden',
                'item_published' => 'Design veröffentlicht.',
                'item_updated' => 'Design aktualisiert.',
            ],
            'description' => 'Vorlagen, die Besucher auf Stickern und Flyern ausprobieren.',
            'public' => true,
            // The shop's search looks through the assortment, not through posts (README "Suche").
            'exclude_from_search' => true,
            'show_in_nav_menus' => false,
            'show_in_rest' => true,
            'rest_controller_class' => DesignsRest::class,
            'has_archive' => self::ARCHIVE,
            'rewrite' => ['slug' => self::ARCHIVE, 'with_front' => false, 'feeds' => false],
            'menu_position' => 27,
            'menu_icon' => 'dashicons-art',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        ]);

        $taxonomy = static fn (string $plural, string $singular, string $hint): array => [
            'labels' => [
                'name' => $plural,
                'singular_name' => $singular,
                'menu_name' => $plural,
                'all_items' => 'Alle: '.$plural,
                'edit_item' => $singular.' bearbeiten',
                'add_new_item' => $singular.' hinzufügen',
                'search_items' => $plural.' durchsuchen',
                'not_found' => 'Nichts gefunden.',
                'separate_items_with_commas' => $hint,
                'choose_from_most_used' => 'Aus den meistgenutzten wählen',
            ],
            // Filtered views are the list with a query argument; a page per term would be a
            // second, unstyled list of the same designs.
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
        ];

        register_taxonomy(self::MEDIUM, self::TYPE, $taxonomy('Werbemittel', 'Werbemittel', 'Titelform der Bereichsseite: „Sticker“ oder „Flyer“.'));
        register_taxonomy(self::OCCASION, self::TYPE, $taxonomy('Anlässe', 'Anlass', 'Zum Beispiel „Eröffnung“ oder „Feier“.'));
    }

    /**
     * WordPress keeps the list of addresses it answers to. It has to rebuild
     * that list once after the designs got their addresses – on a new
     * installation as well as after an update of the plugin.
     */
    public static function learnAddresses(): void
    {
        if (get_option(self::RULES_OPTION) !== self::RULES) {
            flush_rewrite_rules(false);
            update_option(self::RULES_OPTION, self::RULES);
        }
    }

    /**
     * For when the plugin is switched off: while it is off, WordPress may
     * rebuild its list without the addresses, and has to learn them again later.
     */
    public static function forgetAddresses(): void
    {
        delete_option(self::RULES_OPTION);
    }

    public static function imageSize(): void
    {
        add_theme_support('post-thumbnails');
        add_image_size(self::IMAGE, 800, 1000, false);
    }

    /**
     * Whether a design is shown: published, with its preview image, and
     * released after the checks for similarity and brands. Everything else
     * about the designs follows this one rule.
     */
    public static function shown(string $status, bool $hasImage, mixed $released): bool
    {
        return self::reason($status, $hasImage, $released) === null;
    }

    /**
     * Why a design is not shown – for the administration – or null when it is.
     */
    public static function reason(string $status, bool $hasImage, mixed $released): ?string
    {
        return match (true) {
            $status !== 'publish' => 'nicht veröffentlicht',
            ! $hasImage => 'das Vorschaubild fehlt',
            ! is_scalar($released) || (string) $released !== '1' => 'nicht freigegeben',
            default => null,
        };
    }

    /**
     * The filter value a visitor asked for, if it is one the list offers; any
     * other – unknown, misspelt, or a term without a shown design – means no filter.
     *
     * @param  list<string>  $offered
     */
    public static function pick(mixed $asked, array $offered): ?string
    {
        return is_string($asked) && $asked !== '' && in_array($asked, $offered, true) ? $asked : null;
    }

    public static function isShown(int $id): bool
    {
        return get_post_type($id) === self::TYPE
            && self::shown((string) get_post_status($id), has_post_thumbnail($id), get_post_meta($id, self::RELEASED, true));
    }

    /**
     * The conditions of shown() as a query: what is published is asked for separately.
     *
     * @return array<int|string, mixed>
     */
    private static function shownQuery(): array
    {
        return [
            'relation' => 'AND',
            ['key' => '_thumbnail_id', 'value' => '0', 'compare' => '!='],
            ['key' => self::RELEASED, 'value' => '1'],
        ];
    }

    /**
     * Ids of all shown designs. Remembered for the request: the list, its
     * filters and the explore block all ask.
     *
     * @return list<int>
     */
    public static function shownIds(): array
    {
        if (self::$shownIds === null) {
            self::$shownIds = array_map('intval', get_posts([
                'post_type' => self::TYPE,
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids',
                'meta_query' => self::shownQuery(),
            ]));
        }

        return self::$shownIds;
    }

    public static function forgetShown(): void
    {
        self::$shownIds = null;
        self::$hidden = null;
    }

    /**
     * What WordPress would hand out to anybody but the shop does not show:
     * published designs that are not shown, and the pictures that belong to
     * them alone – uploaded for one of them, or its preview image. A picture
     * still waits for the checks as long as its design does.
     *
     * @return array{designs: list<int>, images: list<int>}
     */
    public static function hidden(): array
    {
        if (self::$hidden === null) {
            $published = array_map('intval', get_posts([
                'post_type' => self::TYPE,
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids',
            ]));
            $designs = array_values(array_diff($published, self::shownIds()));
            $images = [];

            if ($designs !== []) {
                update_meta_cache('post', [...$designs, ...self::shownIds()]);

                $uploaded = get_posts([
                    'post_type' => 'attachment',
                    'post_status' => 'inherit',
                    'post_parent__in' => $designs,
                    'numberposts' => -1,
                    'fields' => 'ids',
                ]);
                $previews = array_map(static fn (int $id): int => (int) get_post_meta($id, '_thumbnail_id', true), $designs);
                // A picture a shown design uses as well is out there already.
                $used = array_map(static fn (int $id): int => (int) get_post_meta($id, '_thumbnail_id', true), self::shownIds());

                $images = array_values(array_diff(array_filter(array_unique([...array_map('intval', $uploaded), ...$previews])), $used));
            }

            self::$hidden = ['designs' => $designs, 'images' => $images];
        }

        return self::$hidden;
    }

    /**
     * Whether an object of the front end belongs to what the shop does not
     * show: such a design, or a picture of one. Whoever may edit it may still
     * look at its preview before releasing it.
     */
    private static function isHidden(WP_Post $post, ?WP_Query $query = null): bool
    {
        $hidden = match ($post->post_type) {
            self::TYPE => ! in_array($post->ID, self::shownIds(), true),
            'attachment' => in_array($post->ID, self::hidden()['images'], true),
            default => false,
        };

        return $hidden && ! ($query !== null && $query->is_preview() && current_user_can('edit_post', $post->ID));
    }

    /** Whether the visitor may see what the shop keeps back, in WordPress' interface: those who edit. */
    private static function seesAll(): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * Terms of a taxonomy that at least one shown design carries – what the
     * filters and the explore block offer. Media in the order they were
     * created (sticker before flyer, as the areas), occasions by name.
     *
     * @return list<array{slug: string, name: string}>
     */
    public static function terms(string $taxonomy): array
    {
        $ids = self::shownIds();

        if ($ids === []) {
            return [];
        }

        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'object_ids' => $ids,
            'hide_empty' => false,
            'orderby' => $taxonomy === self::MEDIUM ? 'term_id' : 'name',
        ]);

        $list = [];

        foreach (is_array($terms) ? $terms : [] as $term) {
            if ($term instanceof WP_Term) {
                $list[$term->slug] = ['slug' => $term->slug, 'name' => $term->name];
            }
        }

        return array_values($list);
    }

    /**
     * Which filters the current request asks for, among those the list offers.
     *
     * @return array{werbemittel: ?string, anlass: ?string}
     */
    public static function selection(): array
    {
        $selection = [];

        foreach (self::FILTERS as $argument => $taxonomy) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a filter of a public list; only compared with what it offers.
            $asked = isset($_GET[$argument]) ? wp_unslash($_GET[$argument]) : null;
            $selection[$argument] = self::pick($asked, array_column(self::terms($taxonomy), 'slug'));
        }

        return $selection;
    }

    /**
     * Address of the list, optionally filtered.
     *
     * @param  array{werbemittel?: ?string, anlass?: ?string}  $filters
     */
    public static function url(array $filters = []): string
    {
        $url = get_post_type_archive_link(self::TYPE);
        $url = is_string($url) ? $url : home_url('/'.self::ARCHIVE.'/');
        $query = array_filter(array_intersect_key($filters, self::FILTERS), static fn (?string $value): bool => $value !== null && $value !== '');

        return $query === [] ? $url : add_query_arg(array_map('rawurlencode', $query), $url);
    }

    /**
     * A design as the theme shows it.
     *
     * @return array{id: int, title: string, url: string, image: int, text: string, medium: ?array{slug: string, name: string}, occasion: ?array{slug: string, name: string}, example: bool}
     */
    public static function card(WP_Post $post): array
    {
        $first = static function (string $taxonomy) use ($post): ?array {
            $terms = get_the_terms($post, $taxonomy);
            $term = is_array($terms) ? reset($terms) : null;

            return $term instanceof WP_Term ? ['slug' => $term->slug, 'name' => $term->name] : null;
        };

        return [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'url' => (string) get_permalink($post),
            'image' => (int) get_post_thumbnail_id($post),
            'text' => trim($post->post_excerpt),
            'medium' => $first(self::MEDIUM),
            'occasion' => $first(self::OCCASION),
            'example' => get_post_meta($post->ID, '_stiqon_example', true) === '1',
        ];
    }

    /**
     * Other shown designs for the same medium, newest first.
     *
     * @return list<array>  cards as card() returns them
     */
    public static function beside(int $id, int $limit = 3): array
    {
        $terms = wp_get_object_terms($id, self::MEDIUM, ['fields' => 'ids']);
        $others = array_values(array_diff(self::shownIds(), [$id]));

        if ($others === [] || ! is_array($terms) || $terms === []) {
            return [];
        }

        $posts = get_posts([
            'post_type' => self::TYPE,
            'post_status' => 'publish',
            'post__in' => $others,
            'numberposts' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => [['taxonomy' => self::MEDIUM, 'terms' => $terms]],
        ]);

        return array_map([self::class, 'card'], $posts);
    }

    /**
     * Whether a shown design is one of the examples of a local site.
     */
    public static function examples(): bool
    {
        foreach (self::shownIds() as $id) {
            if (get_post_meta($id, '_stiqon_example', true) === '1') {
                return true;
            }
        }

        return false;
    }

    /**
     * A design that is not shown has no address – in no form WordPress has for
     * one: its page, ?p=<id>, its embed (/embed/), a feed, the page of its
     * picture. They all come from the request's main query, so it is taken out
     * of the results there. With nothing left, WordPress answers "not found"
     * by itself, before anything else – a redirect to the design's address
     * included.
     *
     * @param  array<int, mixed>  $posts
     * @return array<int, mixed>
     */
    public static function onlyShown(array $posts, WP_Query $query): array
    {
        if (is_admin() || ! $query->is_main_query()) {
            return $posts;
        }

        return array_values(array_filter($posts, static fn (mixed $post): bool => ! ($post instanceof WP_Post && self::isHidden($post, $query))));
    }

    /**
     * WordPress guesses where an address that leads nowhere was meant to go –
     * ?p=<id> to a post's address, a misspelt name to a similar one. Such a
     * guess must not name a design that is not shown.
     */
    public static function noWayToHidden(mixed $redirect): mixed
    {
        if (! is_string($redirect) || $redirect === '' || ! is_404()) {
            return $redirect;
        }

        $post = get_post(url_to_postid($redirect));

        return $post instanceof WP_Post && self::isHidden($post) && ! current_user_can('edit_post', $post->ID) ? false : $redirect;
    }

    /**
     * The list: shown designs only, newest first, 24 to a page, narrowed by
     * the filters the request asks for. Any other list of designs alone (a
     * feed of the type, ?post_type[]=…) holds the shown ones as well.
     */
    public static function listed(WP_Query $query): void
    {
        if (is_admin() || ! $query->is_main_query() || $query->is_singular()) {
            return;
        }

        $archive = $query->is_post_type_archive(self::TYPE);

        if (! $archive && array_values((array) $query->get('post_type')) !== [self::TYPE]) {
            return;
        }

        $query->set('post__in', self::shownIds() ?: [0]);

        if (! $archive) {
            return;
        }

        $taxonomies = [];

        foreach (self::selection() as $argument => $slug) {
            if ($slug !== null) {
                $taxonomies[] = ['taxonomy' => self::FILTERS[$argument], 'field' => 'slug', 'terms' => [$slug]];
            }
        }

        $query->set('posts_per_page', self::PER_PAGE);
        $query->set('orderby', ['date' => 'DESC', 'ID' => 'DESC']);

        if ($taxonomies !== []) {
            $query->set('tax_query', ['relation' => 'AND', ...$taxonomies]);
        }
    }

    /**
     * @param  array<string, \WP_Post_Type>  $types
     * @return array<string, \WP_Post_Type>
     */
    public static function notInSitemap(array $types): array
    {
        // Not yet: the first designs are checked one by one, and the list changes while they are.
        unset($types[self::TYPE]);

        return $types;
    }

    /**
     * WordPress' interface lists designs, too (the editor needs it). To visitors
     * it lists what the shop shows and nothing else.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public static function restQuery(array $arguments): array
    {
        if (! self::seesAll()) {
            $asked = array_map('intval', (array) ($arguments['post__in'] ?? []));
            $arguments['post__in'] = ($asked === [] ? self::shownIds() : array_values(array_intersect($asked, self::shownIds()))) ?: [0];
        }

        return $arguments;
    }

    /**
     * The interface's search (/wp/v2/search) looks through every public type
     * itself, past the designs' own endpoint.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public static function restSearch(array $arguments): array
    {
        if (! self::seesAll() && self::hidden()['designs'] !== []) {
            $arguments['post__not_in'] = [...array_map('intval', (array) ($arguments['post__not_in'] ?? [])), ...self::hidden()['designs']];
        }

        return $arguments;
    }

    /**
     * The media library (/wp/v2/media) shows a picture to anybody whose post is
     * published – a design waiting for its release included.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public static function restMedia(array $arguments): array
    {
        if (! self::seesAll() && self::hidden()['images'] !== []) {
            $arguments['post__not_in'] = [...array_map('intval', (array) ($arguments['post__not_in'] ?? [])), ...self::hidden()['images']];
        }

        return $arguments;
    }

    /**
     * Single items of the interface the lists above leave out: a picture of a
     * design that is not shown, and a Werbemittel or Anlass no shown design
     * carries. Unknown, as the design itself – not "forbidden".
     *
     * @param  mixed  $response
     * @param  array<string, mixed>  $handler
     * @param  WP_REST_Request  $request
     * @return mixed
     */
    public static function restItem($response, $handler, $request)
    {
        if ($response !== null || ! in_array($request->get_method(), ['GET', 'HEAD'], true) || self::seesAll()) {
            return $response;
        }

        $taxonomies = implode('|', array_map(static fn (string $taxonomy): string => preg_quote($taxonomy, '#'), self::FILTERS));

        if (! preg_match('#^/wp/v2/(media|'.$taxonomies.')/(\d+)$#', $request->get_route(), $match)) {
            return $response;
        }

        $id = (int) $match[2];
        $hidden = $match[1] === 'media'
            ? in_array($id, self::hidden()['images'], true)
            : ! in_array($id, array_map('intval', get_terms(['taxonomy' => $match[1], 'object_ids' => self::shownIds() ?: [0], 'fields' => 'ids', 'hide_empty' => false]) ?: []), true);

        return $hidden ? new WP_Error('rest_not_found', 'Nicht gefunden.', ['status' => 404]) : $response;
    }

    /**
     * Werbemittel and Anlässe in the interface: to visitors those of shown
     * designs only, as the list's filters offer them.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public static function restTerms(array $arguments): array
    {
        if (! self::seesAll()) {
            $arguments['object_ids'] = self::shownIds() ?: [0];
        }

        return $arguments;
    }

    /**
     * WordPress counts every published design of a term; visitors are told the
     * shown ones.
     *
     * @param  WP_REST_Response  $response
     */
    public static function restTermCount($response, WP_Term $term)
    {
        if (! self::seesAll() && $response instanceof WP_REST_Response) {
            $data = $response->get_data();
            $objects = get_objects_in_term($term->term_id, $term->taxonomy);
            $data['count'] = is_array($objects) ? count(array_intersect(array_map('intval', $objects), self::shownIds())) : 0;
            $response->set_data($data);
        }

        return $response;
    }

    /**
     * An embed would name a design that is not shown.
     */
    public static function noEmbed(mixed $id): mixed
    {
        return is_numeric($id) && get_post_type((int) $id) === self::TYPE && ! self::isShown((int) $id) ? 0 : $id;
    }

    /**
     * Title, image, a description, two terms and the provenance: a plain form,
     * no block editor – and with it the hint and the box below the title.
     */
    public static function classicForm(mixed $use, mixed $type): mixed
    {
        return $type === self::TYPE ? false : $use;
    }

    public static function hint(WP_Post $post): void
    {
        if ($post->post_type !== self::TYPE) {
            return;
        }

        echo '<p class="description" style="margin: 12px 0 0">'
            .'Ein Design erscheint im Shop erst, wenn es veröffentlicht ist, ein Vorschaubild hat und unter „Herkunft und Freigabe“ freigegeben ist. '
            .'Vorschaubild: rechts unter „Vorschaubild“, als PNG, JPEG oder WebP. Werbemittel: „sticker“ oder „flyer“ – wie die Bereichsseiten. Anlass: frei, etwa „Eröffnung“.'
            .'</p>';
    }

    public static function metaBox(): void
    {
        add_meta_box('stiqon-design-provenance', 'Herkunft und Freigabe', [self::class, 'provenanceBox'], self::TYPE, 'normal', 'high');
    }

    public static function provenanceBox(WP_Post $post): void
    {
        wp_nonce_field(self::NONCE, self::NONCE);

        $job = (string) get_post_meta($post->ID, self::JOB, true);
        $models = (string) get_post_meta($post->ID, self::MODELS, true);
        $review = (string) get_post_meta($post->ID, self::REVIEW, true);
        $released = get_post_meta($post->ID, self::RELEASED, true) === '1';

        echo '<p><label for="stiqon-design-job"><strong>Higgsfield-Auftragsnummer</strong></label><br>'
            .'<input type="text" class="regular-text" id="stiqon-design-job" name="stiqon_design[job]" value="'.esc_attr($job).'"></p>';

        echo '<p><label for="stiqon-design-models"><strong>Vorbilder</strong></label><br>'
            .'<textarea class="large-text code" rows="4" id="stiqon-design-models" name="stiqon_design[models]" aria-describedby="stiqon-design-models-hint">'.esc_textarea($models).'</textarea>'
            .'<span class="description" id="stiqon-design-models-hint">Eines je Zeile: Adresse und Klasse, etwa „https://example.com/vorlage A“. A: Anregung für Stil oder Aufbau. B: enges Vorbild – dann ist die Prüfung besonders gründlich.</span></p>';

        echo '<p><label for="stiqon-design-review"><strong>Prüfnotiz</strong></label><br>'
            .'<textarea class="large-text" rows="3" id="stiqon-design-review" name="stiqon_design[review]">'.esc_textarea($review).'</textarea></p>';

        echo '<p><label><input type="checkbox" name="stiqon_design[released]" value="1"'.checked($released, true, false).'> '
            .'<strong>Freigegeben: Ähnlichkeits- und Markenprüfung erledigt</strong></label></p>';
    }

    public static function saveProvenance(int $id, WP_Post $post): void
    {
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($id) || $post->post_type !== self::TYPE) {
            return;
        }

        $nonce = isset($_POST[self::NONCE]) ? sanitize_text_field(wp_unslash($_POST[self::NONCE])) : '';

        if (! wp_verify_nonce($nonce, self::NONCE) || ! current_user_can('edit_post', $id)) {
            return;
        }

        $sent = isset($_POST['stiqon_design']) && is_array($_POST['stiqon_design']) ? wp_unslash($_POST['stiqon_design']) : [];
        $text = static fn (string $key): string => is_string($sent[$key] ?? null) ? $sent[$key] : '';

        update_post_meta($id, self::JOB, sanitize_text_field($text('job')));
        update_post_meta($id, self::MODELS, sanitize_textarea_field($text('models')));
        update_post_meta($id, self::REVIEW, sanitize_textarea_field($text('review')));

        if ($text('released') === '1') {
            update_post_meta($id, self::RELEASED, '1');
        } else {
            delete_post_meta($id, self::RELEASED);
        }

        self::forgetShown();
    }

    /**
     * The list in the administration: preview, the two terms, and whether the
     * shop shows the design – and if not, why.
     *
     * @param  array<string, string>  $columns
     * @return array<string, string>
     */
    public static function columns(array $columns): array
    {
        $ordered = [];

        foreach ($columns as $key => $label) {
            if ($key === 'title') {
                $ordered['stiqon_preview'] = 'Vorschau';
            }

            if ($key === 'date') {
                $ordered['stiqon_release'] = 'Freigabe';
            }

            $ordered[$key] = match ($key) {
                'taxonomy-'.self::MEDIUM => 'Werbemittel',
                'taxonomy-'.self::OCCASION => 'Anlass',
                default => $label,
            };
        }

        return $ordered + (isset($ordered['stiqon_release']) ? [] : ['stiqon_release' => 'Freigabe']);
    }

    public static function column(string $column, int $id): void
    {
        if ($column === 'stiqon_preview') {
            echo get_the_post_thumbnail($id, [48, 60], ['style' => 'width: 48px; height: 60px; object-fit: contain; background: #f3f3f3; border-radius: 6px']);

            return;
        }

        if ($column !== 'stiqon_release') {
            return;
        }

        $reason = self::reason((string) get_post_status($id), has_post_thumbnail($id), get_post_meta($id, self::RELEASED, true));

        echo esc_html($reason === null ? 'Ja – im Shop' : 'Nein – '.$reason);
    }
}
