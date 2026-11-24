<?php
/**
 * The area "Sticker" (the page with the address /sticker/).
 */

defined('ABSPATH') || exit;

get_header();

get_template_part('template-parts/area', null, [
    'name' => 'Sticker',
    'lead' => 'Rund, quadratisch, rechteckig, oval oder mit Konturschnitt – auf weißem Vinyl, transparenter und holografischer Folie oder Kraftpapier.',
    'other' => ['label' => 'Flyer ansehen', 'url' => home_url('/flyer/')],
    'cards' => [
        ['title' => 'Runde Sticker', 'meta' => 'Weißes Vinyl', 'sticker' => ['shape' => 'circle', 'motif' => 'siegel']],
        ['title' => 'Quadratische Sticker', 'meta' => 'Weißes Vinyl', 'sticker' => ['shape' => 'square', 'motif' => 'sonne', 'peel' => true]],
        ['title' => 'Rechteckige Sticker', 'meta' => 'Weißes Vinyl', 'sticker' => ['shape' => 'rect', 'motif' => 'wellen']],
        ['title' => 'Ovale Sticker', 'meta' => 'Weißes Vinyl', 'sticker' => ['shape' => 'oval', 'motif' => 'danke']],
        ['title' => 'Sticker mit Konturschnitt', 'meta' => 'Weißes Vinyl', 'sticker' => ['shape' => 'contour', 'motif' => 'sprechblase']],
        ['title' => 'Runde Sticker', 'meta' => 'Transparente Folie', 'sticker' => ['shape' => 'circle', 'material' => 'clear', 'motif' => 'monogramm']],
        ['title' => 'Quadratische Sticker', 'meta' => 'Holografische Folie', 'sticker' => ['shape' => 'square', 'material' => 'holo', 'motif' => 'raster']],
        ['title' => 'Runde Sticker', 'meta' => 'Kraftpapier', 'sticker' => ['shape' => 'circle', 'material' => 'kraft', 'motif' => 'blatt', 'finish' => 'matte']],
    ],
]);

get_footer();
