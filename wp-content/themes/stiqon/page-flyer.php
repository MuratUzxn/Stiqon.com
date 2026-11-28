<?php
/**
 * The area "Flyer" (the page with the address /flyer/).
 */

defined('ABSPATH') || exit;

stiqon_declare_lean_page();
get_header();

get_template_part('template-parts/area', null, [
    'name' => 'Flyer',
    'lead' => 'Von DIN A6 bis DIN A4, DIN lang und quadratisch – auf leichtem oder festem Bilderdruckpapier.',
    'other' => ['label' => 'Sticker ansehen', 'url' => stiqon_page_url('sticker')],
    'category' => 'flyer',
]);

get_footer();
