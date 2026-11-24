<?php
/**
 * The area "Flyer" (the page with the address /flyer/).
 */

defined('ABSPATH') || exit;

get_header();

get_template_part('template-parts/area', null, [
    'name' => 'Flyer',
    'lead' => 'Von DIN A6 bis DIN A4, DIN lang und quadratisch – als einzelnes Blatt oder im Stapel gezeigt.',
    'cards' => [
        ['tone' => 'mist', 'flyer' => ['format' => 'a6', 'motif' => 'fest']],
        ['tone' => 'sand', 'flyer' => ['format' => 'a5', 'motif' => 'eroeffnung', 'stack' => true]],
        ['tone' => 'sage', 'flyer' => ['format' => 'a4', 'motif' => 'karte', 'heavy' => true]],
        ['tone' => 'mist', 'flyer' => ['format' => 'lang', 'motif' => 'prozent', 'gloss' => true]],
        ['tone' => 'sky', 'flyer' => ['format' => 'quadrat', 'motif' => 'kursplan', 'stack' => true]],
    ],
]);

get_footer();
