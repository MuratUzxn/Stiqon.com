<?php

namespace Stiqon\Shop\Content;

use WP_Post;
use WP_Query;

/**
 * References: work the shop has delivered, each with a title (the customer or
 * the project), a photo and a sentence or two.
 *
 * They are shown as one list at /referenzen/. That address only exists once
 * there is something to show: a shop that opens with an empty "Referenzen"
 * page, or with invented ones, loses trust instead of gaining it. A reference
 * is shown once it is published and has its photo – the photo is the proof.
 * A single reference has no page of its own; its address leads to the list.
 */
final class References
{
    public const TYPE = 'stiqon_referenz';

    public const ARCHIVE = 'referenzen';

    /** Photo of a reference in a list: 4 to 3, cropped. */
    public const IMAGE = 'stiqon-referenz';

    /** Says which addresses WordPress has learnt from this class; changes when they do. */
    private const RULES = 'referenzen-1';

    private const RULES_OPTION = 'stiqon_shop_rules';

    /** What makes a published reference one that is shown: it has a photo. */
    private const WITH_PHOTO = [['key' => '_thumbnail_id', 'compare' => 'EXISTS']];

    public static function register(): void
    {
        add_action('init', [self::class, 'type']);
        add_action('init', [self::class, 'learnAddresses'], 99);
        add_action('after_setup_theme', [self::class, 'imageSize']);
        add_action('wp', [self::class, 'onlyWithEntries']);
        add_action('template_redirect', [self::class, 'toTheList']);
        add_action('pre_get_posts', [self::class, 'inOrder']);
        add_filter('wp_sitemaps_post_types', [self::class, 'notInSitemap']);
        add_action('edit_form_after_title', [self::class, 'hint']);
        add_filter('manage_'.self::TYPE.'_posts_columns', [self::class, 'columns']);
        add_action('manage_'.self::TYPE.'_posts_custom_column', [self::class, 'column'], 10, 2);
    }

    public static function type(): void
    {
        register_post_type(self::TYPE, [
            'labels' => [
                'name' => 'Referenzen',
                'singular_name' => 'Referenz',
                'menu_name' => 'Referenzen',
                'add_new' => 'Hinzufügen',
                'add_new_item' => 'Referenz hinzufügen',
                'edit_item' => 'Referenz bearbeiten',
                'new_item' => 'Neue Referenz',
                'view_item' => 'Referenzen ansehen',
                'view_items' => 'Referenzen ansehen',
                'search_items' => 'Referenzen durchsuchen',
                'not_found' => 'Noch keine Referenzen.',
                'not_found_in_trash' => 'Keine Referenzen im Papierkorb.',
                'all_items' => 'Alle Referenzen',
                'archives' => 'Referenzen',
                'featured_image' => 'Foto',
                'set_featured_image' => 'Foto festlegen',
                'remove_featured_image' => 'Foto entfernen',
                'use_featured_image' => 'Als Foto verwenden',
                'item_published' => 'Referenz veröffentlicht.',
                'item_updated' => 'Referenz aktualisiert.',
            ],
            'description' => 'Arbeiten, die der Shop geliefert hat.',
            'public' => true,
            'exclude_from_search' => true,
            'show_in_nav_menus' => false,
            'has_archive' => self::ARCHIVE,
            'rewrite' => ['slug' => self::ARCHIVE, 'with_front' => false, 'feeds' => false],
            'menu_position' => 26,
            'menu_icon' => 'dashicons-format-gallery',
            // Title, photo, a short text and a place in the order: a plain form, no page builder.
            'supports' => ['title', 'thumbnail', 'excerpt', 'page-attributes'],
        ]);
    }

    /**
     * WordPress keeps the list of addresses it answers to. It has to rebuild
     * that list once after the list of references got its address – on a new
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
     * rebuild its list without the address, and has to learn it again later.
     */
    public static function forgetAddresses(): void
    {
        delete_option(self::RULES_OPTION);
    }

    /**
     * A reference's photo is its "featured image", which a theme has to allow.
     * Allowed for everything, as WooCommerce does for products: naming only the
     * references here would – depending on which plugin is loaded first – take
     * the picture away from products, pages and posts.
     */
    public static function imageSize(): void
    {
        add_theme_support('post-thumbnails');
        add_image_size(self::IMAGE, 960, 720, true);
    }

    /**
     * Whether there is a reference to show.
     */
    public static function any(): bool
    {
        // WordPress remembers the answer until a post changes, so asking on every page costs little.
        return get_posts([
            'post_type' => self::TYPE,
            'post_status' => 'publish',
            'numberposts' => 1,
            'fields' => 'ids',
            'meta_query' => self::WITH_PHOTO,
        ]) !== [];
    }

    /**
     * Address of the list; null while there is nothing to show.
     */
    public static function url(): ?string
    {
        $url = self::any() ? get_post_type_archive_link(self::TYPE) : false;

        return is_string($url) ? $url : null;
    }

    /**
     * The references in their order: by the number set for each, then newest first.
     *
     * @return list<array{id: int, title: string, text: string, image: int}>  "image": id of the photo in the media library
     */
    public static function all(int $limit = -1): array
    {
        $posts = get_posts([
            'post_type' => self::TYPE,
            'post_status' => 'publish',
            'numberposts' => $limit,
            'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
            'meta_query' => self::WITH_PHOTO,
        ]);

        return array_map(static fn (WP_Post $post): array => [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'text' => trim($post->post_excerpt),
            'image' => (int) get_post_thumbnail_id($post),
        ], $posts);
    }

    /**
     * Without a reference to show, the address of the list is an unknown address.
     */
    public static function onlyWithEntries(): void
    {
        global $wp_query;

        if (is_post_type_archive(self::TYPE) && ! $wp_query->have_posts()) {
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
        }
    }

    /**
     * A single reference has no page; whoever follows its address sees the list.
     * A reference the list does not show – its photo is still missing – has no
     * address at all, and neither has any while there is no list.
     */
    public static function toTheList(): void
    {
        global $wp_query;

        if (! is_singular(self::TYPE)) {
            return;
        }

        $id = get_queried_object_id();
        $url = get_post_status($id) === 'publish' && has_post_thumbnail($id) ? self::url() : null;

        if ($url !== null) {
            wp_safe_redirect($url.'#referenz-'.$id, 301);
            exit;
        }

        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }

    /**
     * The list shows all references on one page, in the order all() gives them.
     */
    public static function inOrder(WP_Query $query): void
    {
        if (! is_admin() && $query->is_main_query() && $query->is_post_type_archive(self::TYPE)) {
            $query->set('posts_per_page', -1);
            $query->set('orderby', ['menu_order' => 'ASC', 'date' => 'DESC']);
            $query->set('meta_query', self::WITH_PHOTO);
        }
    }

    /**
     * @param  array<string, \WP_Post_Type>  $types
     * @return array<string, \WP_Post_Type>
     */
    public static function notInSitemap(array $types): array
    {
        // The single addresses only lead to the list.
        unset($types[self::TYPE]);

        return $types;
    }

    public static function hint(WP_Post $post): void
    {
        if ($post->post_type !== self::TYPE) {
            return;
        }

        echo '<p class="description" style="margin: 12px 0 0">'
            .'Titel: Kundin, Kunde oder Projekt. Textauszug: ein, zwei Sätze dazu. Foto: rechts unter „Foto“, am besten im Querformat. '
            .'Eine Referenz erscheint im Shop, sobald sie veröffentlicht ist und ein Foto hat; mit der ersten erscheint auch die Seite „Referenzen“. '
            .'Bitte nur echte Arbeiten zeigen, und Namen und Fotos nur mit Einverständnis.'
            .'</p>';
    }

    /**
     * The list in the administration says of each reference whether the shop shows it.
     *
     * @param  array<string, string>  $columns
     * @return array<string, string>
     */
    public static function columns(array $columns): array
    {
        $date = $columns['date'] ?? null;
        unset($columns['date']);

        return $columns + ['stiqon_shown' => 'Im Shop'] + ($date !== null ? ['date' => $date] : []);
    }

    public static function column(string $column, int $id): void
    {
        if ($column !== 'stiqon_shown') {
            return;
        }

        echo esc_html(match (true) {
            get_post_status($id) !== 'publish' => 'Nein – nicht veröffentlicht',
            ! has_post_thumbnail($id) => 'Nein – das Foto fehlt',
            default => 'Ja',
        });
    }
}
