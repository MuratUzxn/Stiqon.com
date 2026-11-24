<?php
/**
 * Things WordPress and WooCommerce add to every page that this theme does not use.
 * Each removal saves a request or keeps the markup to what the pages need.
 */

defined('ABSPATH') || exit;

add_action('init', function (): void {
    // Emoji: a script that swaps emoji for images loaded from a WordPress.org server.
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');

    // Links for services the shop does not offer: feeds, remote editing, embedding.
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'rest_output_link_wp_head');
});

// The WordPress version does not belong in the page source.
add_filter('the_generator', '__return_empty_string');

/**
 * Whether the current page is built entirely from this theme's templates, with
 * no content from the block editor.
 */
function stiqon_is_templated_page(): bool
{
    return is_front_page() || is_404() || is_page(['sticker', 'flyer', 'musterbogen']);
}

/**
 * Such pages need none of the stylesheets that exist for editor content. The
 * global styles are printed in the footer and moved to the head by WordPress,
 * so they are unhooked before either happens.
 */
add_action('wp', function (): void {
    if (stiqon_is_templated_page()) {
        remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
        remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
    }
});

add_action('wp_enqueue_scripts', function (): void {
    if (stiqon_is_templated_page()) {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('classic-theme-styles');
        wp_dequeue_style('global-styles');
    }
}, 100);

/**
 * WooCommerce loads its scripts and styles on every page. Outside the shop pages
 * there is nothing for them to act on.
 */
add_action('wp_enqueue_scripts', function (): void {
    if (! function_exists('is_woocommerce')) {
        return;
    }

    if (is_woocommerce() || is_cart() || is_checkout() || is_account_page()) {
        return;
    }

    foreach (wp_scripts()->queue as $handle) {
        if (str_starts_with($handle, 'wc-') || str_starts_with($handle, 'woocommerce') || $handle === 'sourcebuster-js') {
            wp_dequeue_script($handle);
        }
    }

    foreach (wp_styles()->queue as $handle) {
        if (str_starts_with($handle, 'wc-') || str_starts_with($handle, 'woocommerce')) {
            wp_dequeue_style($handle);
        }
    }
}, 100);
