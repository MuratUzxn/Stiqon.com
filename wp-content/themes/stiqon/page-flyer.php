<?php
/**
 * The area "Flyer" (the page with the address /flyer/).
 */

defined('ABSPATH') || exit;

get_header();

get_template_part('template-parts/area', null, [
    'name' => 'Flyer',
    'lead' => 'Von DIN A6 bis DIN A4, DIN lang und quadratisch – als einzelnes Blatt oder im Stapel gezeigt.',
    'other' => ['label' => 'Sticker ansehen', 'url' => home_url('/sticker/')],
    'cards' => [
        ['title' => 'Flyer DIN A6', 'meta' => 'Einzelblatt', 'flyer' => ['format' => 'a6', 'motif' => 'fest']],
        ['title' => 'Flyer DIN A5', 'meta' => 'Auflage im Stapel', 'flyer' => ['format' => 'a5', 'motif' => 'eroeffnung', 'stack' => true]],
        ['title' => 'Flyer DIN A4', 'meta' => 'Stärkeres Papier', 'flyer' => ['format' => 'a4', 'motif' => 'karte', 'heavy' => true]],
        ['title' => 'Flyer DIN lang', 'meta' => 'Glänzend', 'flyer' => ['format' => 'lang', 'motif' => 'prozent', 'gloss' => true]],
        ['title' => 'Flyer Quadrat', 'meta' => 'Auflage im Stapel', 'flyer' => ['format' => 'quadrat', 'motif' => 'kursplan', 'stack' => true]],
    ],
]);

get_footer();
