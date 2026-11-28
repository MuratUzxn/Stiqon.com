<?php
/**
 * An area of the shop (/sticker/, /visitenkarten/ …): every page whose address
 * is one of the areas in stiqon_area_definitions() is drawn with this template
 * (see the filter "page_template_hierarchy" in inc/site.php).
 */

defined('ABSPATH') || exit;

$area = stiqon_current_area();

if ($area === null) {
    // Reached directly, without being an area: an ordinary page.
    get_template_part('page');

    return;
}

$related = stiqon_area_definitions()[$area['related']] ?? null;

stiqon_declare_lean_page();
get_header();

get_template_part('template-parts/area', null, [
    'name' => $area['label'],
    'lead' => $area['lead'],
    'other' => $related === null ? null : ['label' => $related['label'].' ansehen', 'url' => stiqon_page_url($area['related'])],
    'category' => $area['slug'],
]);

get_footer();
