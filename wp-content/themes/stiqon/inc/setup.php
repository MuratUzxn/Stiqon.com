<?php
/**
 * What the theme supports and what it switches off.
 */

defined('ABSPATH') || exit;

add_action('after_setup_theme', function (): void {
    add_theme_support('title-tag');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);

    // The shop pages are rendered by this theme's own templates.
    add_theme_support('woocommerce');
});
