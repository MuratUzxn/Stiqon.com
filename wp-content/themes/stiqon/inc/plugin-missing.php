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

    function stiqon_product_view(WC_Product $product, array $chosen = [], array $options = []): string
    {
        return '';
    }

    function stiqon_product_views(WC_Product $product, bool $ticked = false): array
    {
        return ['key' => null, 'views' => []];
    }

    function stiqon_product_choice(WC_Product $product): array
    {
        return ['chosen' => [], 'variation' => null, 'offered' => null];
    }

    function stiqon_gallery(string $category): array
    {
        return ['products' => [], 'axis' => null, 'cards' => [], 'examples' => false, 'selection' => ['produkt' => null, 'option' => null]];
    }

    function stiqon_products_beside(WC_Product $product, string $category, int $limit = 4): array
    {
        return [];
    }

    function stiqon_search(string $query, int $limit = 48): array
    {
        return ['query' => $query, 'total' => 0, 'areas' => [], 'cards' => []];
    }

    function stiqon_search_index_url(): string
    {
        return '';
    }

    function stiqon_references(int $limit = -1): array
    {
        return [];
    }

    function stiqon_references_url(): ?string
    {
        return null;
    }

    function stiqon_designs(): array
    {
        return ['media' => [], 'occasions' => [], 'selection' => ['werbemittel' => null, 'anlass' => null], 'designs' => [], 'any' => false, 'examples' => false];
    }

    function stiqon_designs_url(array $filters = []): string
    {
        return home_url('/design/');
    }

    function stiqon_design(WP_Post $post, int $beside = 3): array
    {
        return ['design' => ['id' => $post->ID, 'title' => get_the_title($post), 'url' => (string) get_permalink($post), 'image' => 0, 'text' => '', 'medium' => null, 'occasion' => null, 'example' => false], 'beside' => []];
    }
}, 0);

add_action('admin_notices', function (): void {
    if (function_exists('stiqon_sticker')) {
        return;
    }

    echo '<div class="notice notice-error"><p>Das Theme „Stiqon“ braucht das Plugin „Stiqon Shop-Funktionen“. Bitte aktivieren Sie es unter Plugins.</p></div>';
});
