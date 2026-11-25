<?php
/**
 * The theme has templates for pages that must exist under fixed addresses. When
 * one is missing, the administration has to say so, and links to it fall back to
 * the plain address instead of pointing nowhere in particular.
 *
 *   tools/dev/wp.sh eval-file tests/integration/setup-problems.php
 */

$page = get_page_by_path('flyer');
$problems = [];

if (! $page instanceof WP_Post) {
    WP_CLI::error('The page "flyer" does not exist; the setup script should have created it.');
}

try {
    wp_update_post(['ID' => $page->ID, 'post_status' => 'draft']);

    $reported = stiqon_setup_problems();

    if (count($reported) !== 1 || ! str_contains($reported[0], 'Flyer')) {
        $problems[] = 'With the page "flyer" withdrawn, the theme reports: '.($reported === [] ? 'nothing' : implode(' | ', $reported));
    }

    if (stiqon_page_url('flyer') !== home_url('/flyer/')) {
        $problems[] = 'The link to a missing page is not the plain address but '.stiqon_page_url('flyer');
    }
} finally {
    wp_update_post(['ID' => $page->ID, 'post_status' => 'publish']);
}

if (stiqon_setup_problems() !== []) {
    $problems[] = 'With the page back, the theme still reports: '.implode(' | ', stiqon_setup_problems());
}

if (stiqon_page_url('flyer') !== get_permalink($page->ID)) {
    $problems[] = 'The link to the page is not its own address.';
}

if ($problems !== []) {
    WP_CLI::error_multi_line($problems);
    WP_CLI::halt(1);
}

WP_CLI::success('A missing page is reported, and reported only while it is missing.');
