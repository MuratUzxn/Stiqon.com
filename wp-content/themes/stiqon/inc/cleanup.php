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
    remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');    // Mails, too: WordPress would turn an emoji in a name or a note into a
    // picture loaded from WordPress.org, which opening the mail would contact.
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

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
 * A "lean" page is built entirely from this theme's own markup: no content from
 * the block editor, nothing drawn by WooCommerce. Its template says so before it
 * prints the header.
 *
 * The decision deliberately sits in the template and not in the address: on the
 * very same address another template can end up being used – WooCommerce's
 * "coming soon" page is block content printed between this theme's header and
 * footer – and such a page needs the stylesheets a lean page drops.
 */
function stiqon_declare_lean_page(): void
{
    $GLOBALS['stiqon_lean_page'] = true;

    // The global styles are queued for the footer and moved to the head by
    // WordPress, so they are unhooked before either happens.
    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
    remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
}

function stiqon_is_lean_page(): bool
{
    return ! empty($GLOBALS['stiqon_lean_page']);
}

/**
 * A "shop" page is drawn by WooCommerce inside this theme's frame: cart,
 * checkout, account. It needs WooCommerce's scripts, and none of its
 * stylesheets – the theme styles that markup itself (assets/src/css/shop.css).
 * Like a lean page it has no editor content. Its template says so before it
 * prints the header.
 */
function stiqon_declare_shop_page(): void
{
    $GLOBALS['stiqon_shop_page'] = true;

    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
    remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
}

function stiqon_is_shop_page(): bool
{
    return ! empty($GLOBALS['stiqon_shop_page']);
}

/**
 * A "written" page comes from the editor and holds nothing of WooCommerce's:
 * the legal texts, for instance. It keeps the stylesheets for editor content
 * and, like a lean page, does without what WooCommerce loads on every page of
 * a site – more stylesheet and script than the checkout gets, for a page of
 * text. Its template says so before it prints the header.
 */
function stiqon_declare_written_page(): void
{
    $GLOBALS['stiqon_written_page'] = true;
}

function stiqon_is_written_page(): bool
{
    return ! empty($GLOBALS['stiqon_written_page']);
}

/**
 * What a lean page leaves out: the stylesheets for editor content, and the
 * scripts and styles WooCommerce loads on every page of a site. A written
 * page leaves out the latter only.
 */
add_action('wp_enqueue_scripts', function (): void {
    if (! stiqon_is_lean_page() && ! stiqon_is_written_page()) {
        return;
    }

    if (stiqon_is_lean_page()) {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('classic-theme-styles');
        wp_dequeue_style('global-styles');
    }

    // The store notice is printed hidden on every page; WooCommerce's script
    // reveals it and its stylesheet places it. With the notice switched on,
    // WooCommerce keeps everything it brings.
    if (! function_exists('is_store_notice_showing') || is_store_notice_showing()) {
        return;
    }

    // Order attribution follows its own setting in WooCommerce. It is off by
    // default here; if the owner switches it on, it has to see the pages
    // visitors arrive on – and those are the lean ones.
    $attribution = ['sourcebuster-js', 'wc-order-attribution'];

    foreach (wp_scripts()->queue as $handle) {
        if (in_array($handle, $attribution, true)) {
            continue;
        }

        if (str_starts_with($handle, 'wc-') || str_starts_with($handle, 'woocommerce')) {
            wp_dequeue_script($handle);
        }
    }

    foreach (wp_styles()->queue as $handle) {
        if (str_starts_with($handle, 'wc-') || str_starts_with($handle, 'woocommerce')) {
            wp_dequeue_style($handle);
        }
    }
}, 100);

/**
 * What a shop page leaves out: the stylesheets for editor content, and those of
 * WooCommerce and Germanized. Stylesheets of other plugins stay – a payment
 * method brings what its fields need. So does the one for searchable select
 * boxes, which WooCommerce's scripts build where a country can be chosen.
 */
add_action('wp_enqueue_scripts', function (): void {
    if (! stiqon_is_shop_page()) {
        return;
    }

    foreach (['wp-block-library', 'classic-theme-styles', 'global-styles', 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen', 'woocommerce-inline', 'woocommerce-gzd-layout'] as $handle) {
        wp_dequeue_style($handle);
    }
}, 100);
