<?php
/**
 * The unit tests cover the plugin's own logic and run without WordPress.
 * The few WordPress functions the templates use are stood in for here.
 */

define('ABSPATH', dirname(__DIR__, 2).'/');

if (! function_exists('esc_attr')) {
    function esc_attr($text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('esc_html')) {
    function esc_html($text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('wp_get_environment_type')) {
    /**
     * "local" unless a test says otherwise: mistakes stop the page while
     * developing. Tests set the global to see how a live shop behaves.
     */
    function wp_get_environment_type(): string
    {
        return $GLOBALS['stiqon_test_environment'] ?? 'local';
    }
}

require dirname(__DIR__, 2).'/wp-content/plugins/stiqon-shop/src/autoload.php';
require dirname(__DIR__, 2).'/wp-content/plugins/stiqon-shop/src/helpers.php';
