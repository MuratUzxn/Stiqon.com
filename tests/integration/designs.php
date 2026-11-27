<?php
/**
 * The register of designs is in place: its post type and taxonomies, the
 * addresses under /design/, and the example designs of the test site – six
 * shown, one published without release and one released without its picture
 * (tools/dev/example-designs.php).
 *
 *   tools/dev/wp.sh eval-file tests/integration/designs.php
 */

use Stiqon\Shop\Content\Designs;

global $wp_rewrite;

$problems = [];

$expect = static function (bool $holds, string $problem) use (&$problems): void {
    if (! $holds) {
        $problems[] = $problem;
    }
};

$type = get_post_type_object(Designs::TYPE);

$expect($type !== null, 'The post type '.Designs::TYPE.' is not registered.');

if ($type !== null) {
    $expect($type->public && $type->show_in_rest, 'Designs are not public, or not in WordPress\' interface (the editor needs it).');
    $expect($type->has_archive === 'design' && ($type->rewrite['slug'] ?? null) === 'design' && empty($type->rewrite['with_front']), 'Designs do not live under /design/.');
    $expect($type->exclude_from_search, 'WordPress\' search looks through designs.');

    foreach (['title', 'editor', 'thumbnail', 'excerpt'] as $feature) {
        $expect(post_type_supports(Designs::TYPE, $feature), "Designs do not support {$feature}.");
    }
}

foreach ([Designs::MEDIUM => 'Werbemittel', Designs::OCCASION => 'Anlass'] as $taxonomy => $label) {
    $object = get_taxonomy($taxonomy);

    $expect($object !== false, "The taxonomy {$taxonomy} is not registered.");

    if ($object !== false) {
        $expect($object->object_type === [Designs::TYPE], "{$label} belongs to ".implode(', ', $object->object_type).'.');
        $expect(! $object->hierarchical && $object->show_in_rest && $object->show_admin_column, "{$label} is not a flat list in the interface and the admin list.");
        // Filtered views are the list with a query argument, not pages of their own.
        $expect(! $object->public && $object->rewrite === false && $object->query_var === false, "{$label} has pages of its own.");
    }
}

// WordPress answers to the addresses: the list, its pages, and a design.
$rules = $wp_rewrite->wp_rewrite_rules();

$expect(($rules['design/?$'] ?? null) === 'index.php?post_type='.Designs::TYPE, 'WordPress does not answer to /design/.');
$expect(isset($rules['design/page/([0-9]{1,})/?$']), 'WordPress does not answer to /design/page/2/.');
$expect(preg_grep('#^design/\(\[\^/\]\+\)#', array_keys($rules)) !== [], 'WordPress does not answer to /design/<name>/.');
$expect(url_to_postid(home_url('/design/beispiel-konfetti/')) > 0, '/design/beispiel-konfetti/ does not lead to a design.');

// Not in the sitemap for now.
$provider = wp_sitemaps_get_server()->registry->get_provider('posts');
$expect($provider === null || ! array_key_exists(Designs::TYPE, $provider->get_object_subtypes()), 'Designs are in WordPress\' sitemap.');

// The example designs.
$shown = array_map(static fn (int $id): string => get_post_field('post_name', $id), Designs::shownIds());

$expect($shown === ['beispiel-neu-eroeffnet', 'beispiel-grosse-eroeffnung', 'beispiel-konfetti', 'beispiel-gartenfest', 'beispiel-prozente', 'beispiel-wochenangebot'], 'The shown designs are: '.implode(', ', $shown ?: ['none']).'.');
$expect(array_column(Designs::terms(Designs::MEDIUM), 'slug') === ['sticker', 'flyer'], 'The media with shown designs are not sticker and flyer.');
$expect(array_column(Designs::terms(Designs::OCCASION), 'slug') === ['eroeffnung', 'feier', 'rabattaktion'], 'The occasions with shown designs are not the three of the examples.');

foreach (Designs::shownIds() as $id) {
    $expect(get_post_meta($id, '_stiqon_example', true) === '1', get_the_title($id).' is not marked as an example.');
    $expect(wp_attachment_is_image((int) get_post_thumbnail_id($id)), get_the_title($id).' has no picture in the media library.');
}

$kept = static function (string $name): ?WP_Post {
    $posts = get_posts(['post_type' => Designs::TYPE, 'name' => $name, 'post_status' => 'any', 'numberposts' => 1]);

    return $posts[0] ?? null;
};

$unreleased = $kept('beispiel-herbstmarkt');
$imageless = $kept('beispiel-kaffeepause');

$expect($unreleased !== null && $unreleased->post_status === 'publish' && has_post_thumbnail($unreleased) && ! Designs::isShown($unreleased->ID), 'There is no published design with its picture that waits for its release.');
$expect($imageless !== null && $imageless->post_status === 'publish' && get_post_meta($imageless->ID, Designs::RELEASED, true) === '1' && ! has_post_thumbnail($imageless) && ! Designs::isShown($imageless->ID), 'There is no released design that waits for its picture.');

// The header names both registers.
$expect(array_column(stiqon_registers(), 'slug') === ['design', 'produkte'], 'The header does not name the registers Design and Produkte.');

if ($problems !== []) {
    WP_CLI::error_multi_line($problems);
    WP_CLI::halt(1);
}

WP_CLI::success('The designs are in place: 6 shown, 2 kept back.');
