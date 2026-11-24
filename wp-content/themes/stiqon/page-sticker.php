<?php
/**
 * The area "Sticker" (the page with the address /sticker/).
 */

defined('ABSPATH') || exit;

get_header();

get_template_part('template-parts/area', null, [
    'name' => 'Sticker',
    'lead' => 'Rund, quadratisch, rechteckig, oval oder mit Konturschnitt – auf weißem Vinyl, transparenter und holografischer Folie oder Kraftpapier.',
    'cards' => [
        ['tone' => 'mist', 'sticker' => ['shape' => 'circle', 'motif' => 'siegel']],
        ['tone' => 'sand', 'sticker' => ['shape' => 'square', 'motif' => 'sonne', 'peel' => true]],
        ['tone' => 'sky', 'sticker' => ['shape' => 'rect', 'motif' => 'wellen']],
        ['tone' => 'blush', 'sticker' => ['shape' => 'oval', 'motif' => 'danke']],
        ['tone' => 'mist', 'sticker' => ['shape' => 'contour', 'motif' => 'sprechblase']],
        ['tone' => 'sky', 'sticker' => ['shape' => 'circle', 'material' => 'clear', 'motif' => 'monogramm']],
        ['tone' => 'mist', 'sticker' => ['shape' => 'square', 'material' => 'holo', 'motif' => 'raster']],
        ['tone' => 'sand', 'sticker' => ['shape' => 'circle', 'material' => 'kraft', 'motif' => 'blatt', 'finish' => 'matte']],
    ],
]);

get_footer();
