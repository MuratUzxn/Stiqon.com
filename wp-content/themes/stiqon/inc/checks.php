<?php
/**
 * What has to be in place for the theme to work – said in the administration
 * when it is not. Without these checks a missing piece fails silently: pages
 * come up unstyled, or the navigation points to addresses that do not exist.
 */

defined('ABSPATH') || exit;

/**
 * @return list<string>  one sentence per problem, in German
 */
function stiqon_setup_problems(): array
{
    $problems = [];

    if (stiqon_build_manifest() === []) {
        $problems[] = 'Dem Theme „Stiqon“ fehlen seine gebauten Dateien (Ordner assets/build). Die Seiten erscheinen deshalb ohne Gestaltung. Bitte das vollständige Theme-Paket installieren.';
    }

    foreach (stiqon_required_pages() as $slug => $title) {
        $page = get_page_by_path($slug);

        if (! $page instanceof WP_Post || $page->post_status !== 'publish') {
            $problems[] = sprintf(
                'Die Seite „%1$s“ fehlt. Bitte unter „Seiten“ eine veröffentlichte Seite mit dem Titel „%1$s“ und der Adresse (Permalink) „%2$s“ anlegen – sonst führen die Links im Kopf der Website ins Leere.',
                $title,
                $slug,
            );
        }
    }

    // WooCommerce creates cart and checkout as pages of blocks. Those bring their
    // own look and about seven times the weight; the theme draws the classic ones.
    if (function_exists('wc_get_page_id')) {
        foreach (['cart' => ['Warenkorb', 'woocommerce_cart', 'woocommerce/cart'], 'checkout' => ['Kasse', 'woocommerce_checkout', 'woocommerce/checkout']] as $page => [$title, $shortcode, $block]) {
            $content = (string) get_post_field('post_content', wc_get_page_id($page));

            if (has_block($block, $content) || ($content !== '' && ! has_shortcode($content, $shortcode))) {
                $problems[] = sprintf(
                    'Die Seite „%1$s“ nutzt nicht den Kurzbefehl [%2$s], den das Theme „Stiqon“ gestaltet. Bitte unter „Seiten“ den Inhalt der Seite „%1$s“ durch einen Block „Kurzbefehl“ mit [%2$s] ersetzen.',
                    $title,
                    $shortcode,
                );
            }
        }
    }

    return $problems;
}

add_action('admin_notices', function (): void {
    if (! current_user_can('manage_options')) {
        return;
    }

    foreach (stiqon_setup_problems() as $problem) {
        printf('<div class="notice notice-warning"><p>%s</p></div>', esc_html($problem));
    }
});
