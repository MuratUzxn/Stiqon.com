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
