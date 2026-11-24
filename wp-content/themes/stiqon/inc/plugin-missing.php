<?php
/**
 * The theme draws its product views with the plugin "Stiqon Shop-Funktionen".
 * Without it, pages still render – without the views – and the administration
 * says what is missing.
 */

defined('ABSPATH') || exit;

/**
 * Stand-ins for the plugin's functions, defined only while a page of the shop is
 * being rendered. In the administration they must not exist: activating the
 * plugin there loads the real functions in the same request.
 */
add_action('template_redirect', function (): void {
    if (function_exists('stiqon_sticker')) {
        return;
    }

    function stiqon_sticker(array $spec = [], string $class = ''): string
    {
        return '';
    }

    function stiqon_flyer(array $spec = [], string $class = ''): string
    {
        return '';
    }

    function stiqon_stage(string $content, string $tone = 'mist', string $class = '', string $ratio = 'square'): string
    {
        return '';
    }
}, 0);

add_action('admin_notices', function (): void {
    if (function_exists('stiqon_sticker')) {
        return;
    }

    echo '<div class="notice notice-error"><p>Das Theme „Stiqon“ braucht das Plugin „Stiqon Shop-Funktionen“. Bitte aktivieren Sie es unter Plugins.</p></div>';
});
