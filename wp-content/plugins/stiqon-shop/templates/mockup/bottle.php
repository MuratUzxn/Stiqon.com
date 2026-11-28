<?php
/**
 * A drink bottle as inline SVG. See Stiqon\Shop\Mockup\Bottle for the sizes and
 * how the bottle is laid out.
 *
 * @var \Stiqon\Shop\Mockup\Bottle $mockup
 * @var string                      $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Bottle;
use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$w = $mockup->width;
$left = round(60 - $w / 2, 2);
$right = round(60 + $w / 2, 2);
$cap = $mockup->cap;
$ring = $mockup->ring();
$capTop = $cap['y'];
$capBottom = round($cap['y'] + $cap['height'], 2);
$capLeft = $cap['x'];
$capRight = round($cap['x'] + $cap['width'], 2);
$capRx = round($cap['width'] / 2, 2);
$capRy = round(Bottle::VIEW * $capRx, 2);
$ringBottom = round($ring['y'] + $ring['height'], 2);
$ringRx = round($ring['width'] / 2, 2);
$ringRy = round(Bottle::VIEW * $ringRx, 2);
$footRy = round($mockup->floor - $mockup->foot, 2);
$silver = $mockup->color === 'silver';

// Light falls from the upper left. Brushed steel mirrors it in hard, upright
// bands, dark and light; powder coat is matte and lets it fall off softly.
// body: the bottle across its width; top: the cap's top face; loop: the carry
// loop and its lit edge; sheen: how strongly the highlight shows over the print.
$tone = [
    'silver' => [
        'body' => [[0, '#6d747b'], [0.05, '#959ca3'], [0.13, '#cfd4d8'], [0.21, '#f6f7f8'], [0.27, '#e3e6e9'], [0.38, '#b9bfc5'], [0.52, '#a5acb3'], [0.64, '#c3c8cd'], [0.73, '#e0e3e6'], [0.82, '#b3b9bf'], [0.92, '#8a9198'], [1, '#6a7178']],
        'top' => '#e7eaed',
        'loop' => '#2b2d30',
        'loopLight' => '#686c72',
        'sheen' => 0.24,
    ],
    'black' => [
        'body' => [[0, '#0d0d0d'], [0.08, '#1b1b1b'], [0.2, '#343434'], [0.3, '#2e2e2e'], [0.55, '#222222'], [0.8, '#1a1a1a'], [1, '#0c0c0c']],
        'top' => '#2f2f2f',
        'loop' => '#1e1e1e',
        'loopLight' => '#4d4d4d',
        'sheen' => 0.1,
    ],
    'white' => [
        'body' => [[0, '#c4c2bc'], [0.08, '#dbd9d4'], [0.2, '#f6f5f2'], [0.3, '#fbfbf9'], [0.5, '#f1f0ec'], [0.78, '#e3e1dc'], [1, '#c3c1bb']],
        'top' => '#fbfbfa',
        'loop' => '#efeeea',
        'loopLight' => '#ffffff',
        'sheen' => 0.12,
    ],
][$mockup->color];

// The steel of the mouth, bare on every bottle.
$steel = [[0, '#80878e'], [0.18, '#dfe3e6'], [0.3, '#f7f8f9'], [0.55, '#b4bac0'], [0.8, '#d2d6da'], [1, '#7c838a']];

// Grooves of the cap's grip, at even steps around it: they crowd together towards its sides.
$ribs = [];

for ($step = -7; $step <= 7; $step++) {
    $along = sin(deg2rad($step * 11.0));
    $bulge = $capRy * sqrt(1 - $along ** 2);
    $ribs[] = [round(60 + $capRx * $along, 2), round($capTop + 0.3 * $cap['height'] + $bulge, 2), round($capBottom + $bulge - 0.6, 2)];
}

// Fine streaks of the brushing, at uneven places across the body: share of the
// width, light (white) or dark (black), and how strongly each shows.
$streaks = [
    [0.06, '#fff', 0.12], [0.11, '#000', 0.05], [0.155, '#fff', 0.18], [0.2, '#fff', 0.1], [0.245, '#000', 0.04],
    [0.29, '#fff', 0.15], [0.36, '#000', 0.05], [0.43, '#fff', 0.09], [0.5, '#000', 0.04], [0.57, '#fff', 0.13],
    [0.64, '#000', 0.05], [0.705, '#fff', 0.16], [0.77, '#000', 0.04], [0.83, '#fff', 0.09], [0.9, '#000', 0.05],
];
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="bottle"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <clipPath id="<?php echo esc_attr($uid); ?>-body">
            <path d="<?php echo esc_attr($mockup->outline); ?>" />
        </clipPath>

        <linearGradient id="<?php echo esc_attr($uid); ?>-coat" x1="0" y1="0" x2="1" y2="0">
            <?php foreach ($tone['body'] as [$offset, $color]) : ?>
                <stop offset="<?php echo esc_attr($offset); ?>" stop-color="<?php echo esc_attr($color); ?>" />
            <?php endforeach; ?>
        </linearGradient>

        <linearGradient id="<?php echo esc_attr($uid); ?>-steel" x1="0" y1="0" x2="1" y2="0">
            <?php foreach ($steel as [$offset, $color]) : ?>
                <stop offset="<?php echo esc_attr($offset); ?>" stop-color="<?php echo esc_attr($color); ?>" />
            <?php endforeach; ?>
        </linearGradient>

        <?php // Over the print as well: the body turns away from the light at both sides, and a band of light runs down its left ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-round" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#000" stop-opacity="0.22" />
            <stop offset="0.1" stop-color="#000" stop-opacity="0.06" />
            <stop offset="0.17" stop-color="#fff" stop-opacity="0" />
            <stop offset="0.22" stop-color="#fff" stop-opacity="<?php echo esc_attr($tone['sheen']); ?>" />
            <stop offset="0.3" stop-color="#fff" stop-opacity="0" />
            <stop offset="0.8" stop-color="#000" stop-opacity="0" />
            <stop offset="0.92" stop-color="#000" stop-opacity="0.1" />
            <stop offset="1" stop-color="#000" stop-opacity="0.24" />
        </linearGradient>

        <?php // The shoulder faces the sky and is lighter; the foot is a shade darker near the table ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-sky" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#fff" stop-opacity="0.32" />
            <stop offset="1" stop-color="#fff" stop-opacity="0" />
        </linearGradient>
        <linearGradient id="<?php echo esc_attr($uid); ?>-table" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#000" stop-opacity="0" />
            <stop offset="1" stop-color="#000" stop-opacity="0.16" />
        </linearGradient>

        <radialGradient id="<?php echo esc_attr($uid); ?>-contact">
            <stop offset="0" stop-color="#000" stop-opacity="0.34" />
            <stop offset="0.55" stop-color="#000" stop-opacity="0.12" />
            <stop offset="1" stop-color="#000" stop-opacity="0" />
        </radialGradient>
    </defs>

    <?php // Where the bottle stands on the table ?>
    <ellipse cx="60" cy="<?php echo esc_attr(round($mockup->floor - 0.5, 2)); ?>" rx="<?php echo esc_attr(round(0.62 * $w, 2)); ?>" ry="2.5" fill="url(#<?php echo esc_attr($uid); ?>-contact)" />

    <?php // The body ?>
    <path d="<?php echo esc_attr($mockup->outline); ?>" fill="url(#<?php echo esc_attr($uid); ?>-coat)" />

    <?php if ($silver) : ?>
        <?php // Brushed steel: fine upright streaks ?>
        <g clip-path="url(#<?php echo esc_attr($uid); ?>-body)">
            <?php foreach ($streaks as [$share, $streak, $opacity]) : ?>
                <path d="M<?php echo esc_attr(round($left + $share * $w, 2)); ?> <?php echo esc_attr($ring['y']); ?>V<?php echo esc_attr($mockup->floor); ?>" stroke="<?php echo esc_attr($streak); ?>" stroke-opacity="<?php echo esc_attr($opacity); ?>" stroke-width="0.14" />
            <?php endforeach; ?>
        </g>
    <?php endif; ?>

    <?php
    // The sample logo is printed straight onto the bottle, without a ground of
    // its own: in dark ink on steel and white, in light ink on black
    // (Bottle::motifVars()). A visitor's logo stays whole and gets no backing.
    Template::output('mockup/parts/motif', [
        'uid' => $uid,
        'box' => $mockup->printBox,
        'motif' => 'motif/'.$mockup->motif,
        'viewBox' => '0 0 100 100',
        'fit' => 'meet',
        'ownFit' => 'meet',
        'vars' => $mockup->motifVars(),
        'backing' => null,
        'attributes' => 'clip-path="url(#'.esc_attr($uid).'-body)"',
    ]);
    ?>

    <?php // Over the print as well: the roundness of the body, the lit shoulder, the shade at the foot ?>
    <g clip-path="url(#<?php echo esc_attr($uid); ?>-body)" pointer-events="none">
        <rect x="<?php echo esc_attr($left); ?>" y="<?php echo esc_attr($ring['y']); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr(round($mockup->floor - $ring['y'], 2)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-round)" />
        <rect x="<?php echo esc_attr($left); ?>" y="<?php echo esc_attr($ring['y']); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr(round($mockup->shoulder - $ring['y'] + 1.5, 2)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-sky)" />
        <rect x="<?php echo esc_attr($left); ?>" y="<?php echo esc_attr(round($mockup->foot - 9, 2)); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr(round($mockup->floor - $mockup->foot + 9, 2)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-table)" />

        <?php // Where the double wall is closed at the bottom: a fine groove round the foot ?>
        <path d="M<?php echo esc_attr($left); ?> <?php echo esc_attr(round($mockup->foot - 3.2, 2)); ?>A<?php echo esc_attr(round($w / 2, 2)); ?> <?php echo esc_attr($footRy); ?> 0 0 0 <?php echo esc_attr($right); ?> <?php echo esc_attr(round($mockup->foot - 3.2, 2)); ?>" fill="none" stroke="#000" stroke-opacity="0.2" stroke-width="0.32" />
        <path d="M<?php echo esc_attr($left); ?> <?php echo esc_attr(round($mockup->foot - 2.8, 2)); ?>A<?php echo esc_attr(round($w / 2, 2)); ?> <?php echo esc_attr($footRy); ?> 0 0 0 <?php echo esc_attr($right); ?> <?php echo esc_attr(round($mockup->foot - 2.8, 2)); ?>" fill="none" stroke="#fff" stroke-opacity="0.3" stroke-width="0.3" />
    </g>
    <path d="<?php echo esc_attr($mockup->outline); ?>" fill="none" stroke="#0a0a0a" stroke-opacity="0.12" stroke-width="0.35" />

    <?php // The bare steel of the mouth under the cap ?>
    <path d="M<?php echo esc_attr($ring['x']); ?> <?php echo esc_attr(round($ring['y'] - 1, 2)); ?>V<?php echo esc_attr($ringBottom); ?>A<?php echo esc_attr($ringRx); ?> <?php echo esc_attr($ringRy); ?> 0 0 0 <?php echo esc_attr(round($ring['x'] + $ring['width'], 2)); ?> <?php echo esc_attr($ringBottom); ?>V<?php echo esc_attr(round($ring['y'] - 1, 2)); ?>z" fill="url(#<?php echo esc_attr($uid); ?>-steel)" stroke="#0a0a0a" stroke-opacity="0.16" stroke-width="0.3" />

    <?php // The screw cap, with grooves for the grip ?>
    <path d="M<?php echo esc_attr($capLeft); ?> <?php echo esc_attr($capTop); ?>V<?php echo esc_attr($capBottom); ?>A<?php echo esc_attr($capRx); ?> <?php echo esc_attr($capRy); ?> 0 0 0 <?php echo esc_attr($capRight); ?> <?php echo esc_attr($capBottom); ?>V<?php echo esc_attr($capTop); ?>z" fill="url(#<?php echo esc_attr($uid); ?>-coat)" />
    <g stroke-width="0.34" stroke-linecap="round">
        <?php foreach ($ribs as [$x, $from, $to]) : ?>
            <path d="M<?php echo esc_attr($x); ?> <?php echo esc_attr($from); ?>V<?php echo esc_attr($to); ?>" stroke="#000" stroke-opacity="0.2" />
            <path d="M<?php echo esc_attr(round($x + 0.36, 2)); ?> <?php echo esc_attr($from); ?>V<?php echo esc_attr($to); ?>" stroke="#fff" stroke-opacity="0.16" />
        <?php endforeach; ?>
    </g>
    <path d="M<?php echo esc_attr($capLeft); ?> <?php echo esc_attr($capTop); ?>V<?php echo esc_attr($capBottom); ?>A<?php echo esc_attr($capRx); ?> <?php echo esc_attr($capRy); ?> 0 0 0 <?php echo esc_attr($capRight); ?> <?php echo esc_attr($capBottom); ?>V<?php echo esc_attr($capTop); ?>z" fill="url(#<?php echo esc_attr($uid); ?>-round)" stroke="#0a0a0a" stroke-opacity="0.16" stroke-width="0.3" pointer-events="none" />

    <?php // Its top face, seen from a little above ?>
    <ellipse cx="60" cy="<?php echo esc_attr($capTop); ?>" rx="<?php echo esc_attr($capRx); ?>" ry="<?php echo esc_attr($capRy); ?>" fill="<?php echo esc_attr($tone['top']); ?>" stroke="#0a0a0a" stroke-opacity="0.14" stroke-width="0.3" />

    <?php // The carry loop, standing on the cap: its feet sit in two small mounts ?>
    <path d="<?php echo esc_attr($mockup->loop); ?>" fill="none" stroke="#0a0a0a" stroke-opacity="0.22" stroke-width="<?php echo esc_attr(Bottle::LOOP['band'] + 0.4); ?>" />
    <path d="<?php echo esc_attr($mockup->loop); ?>" fill="none" stroke="<?php echo esc_attr($tone['loop']); ?>" stroke-width="<?php echo esc_attr(Bottle::LOOP['band']); ?>" />
    <path d="<?php echo esc_attr($mockup->loop); ?>" fill="none" stroke="<?php echo esc_attr($tone['loopLight']); ?>" stroke-opacity="0.7" stroke-width="<?php echo esc_attr(round(Bottle::LOOP['band'] * 0.3, 2)); ?>" transform="translate(-0.35 -0.3)" />
    <?php foreach ([60 - Bottle::LOOP['span'] / 2, 60 + Bottle::LOOP['span'] / 2] as $foot) : ?>
        <rect x="<?php echo esc_attr(round($foot - 1.55, 2)); ?>" y="<?php echo esc_attr(round($capTop - 0.95, 2)); ?>" width="3.1" height="1.6" rx="0.65" fill="<?php echo esc_attr($tone['loop']); ?>" stroke="#0a0a0a" stroke-opacity="0.22" stroke-width="0.3" />
    <?php endforeach; ?>
</svg>
