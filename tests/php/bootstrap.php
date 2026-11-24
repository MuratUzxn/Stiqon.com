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

require dirname(__DIR__, 2).'/wp-content/plugins/stiqon-shop/src/autoload.php';
require dirname(__DIR__, 2).'/wp-content/plugins/stiqon-shop/src/helpers.php';
