<?php
/**
 * The area "Sticker" (the page with the address /sticker/).
 */

defined('ABSPATH') || exit;

stiqon_declare_lean_page();
get_header();

get_template_part('template-parts/area', null, [
    'name' => 'Sticker',
    'lead' => 'Rund, quadratisch, rechteckig, oval oder mit Konturschnitt – auf weißem Vinyl, transparenter und holografischer Folie oder Kraftpapier.',
    'other' => ['label' => 'Flyer ansehen', 'url' => stiqon_page_url('flyer')],
    'category' => 'sticker',
]);

get_footer();
