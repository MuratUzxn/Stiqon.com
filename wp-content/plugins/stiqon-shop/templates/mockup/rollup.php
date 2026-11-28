<?php
/**
 * A roll-up as inline SVG. See Stiqon\Shop\Mockup\Rollup for the formats.
 *
 * Drawn from the front, a little from above: the cassette shows its lid, the
 * feet lie on the floor in front of it.
 *
 * @var \Stiqon\Shop\Mockup\Rollup $mockup
 * @var string                     $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$premium = $mockup->model === 'premium';
['x' => $x, 'y' => $y, 'width' => $w, 'height' => $h] = $mockup->banner;
$rail = $mockup->rail;
$box = $mockup->cassette;
$cx = $box['x'];
$cy = $box['y'];
$cw = $box['width'];
$ch = $box['height'];

// The pole stands in the middle, behind the banner.
$pole = round($x + $w / 2, 2);
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="rollup"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <?php // Brushed aluminium, or chrome with harder reflections ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-metal" x1="0" y1="0" x2="0" y2="1">
            <?php if ($premium) : ?>
                <stop offset="0" stop-color="#fbfcfd" />
                <stop offset="0.2" stop-color="#e2e5e9" />
                <stop offset="0.45" stop-color="#8e949c" />
                <stop offset="0.58" stop-color="#d3d7dc" />
                <stop offset="0.72" stop-color="#f3f4f6" />
                <stop offset="0.9" stop-color="#a2a8b0" />
                <stop offset="1" stop-color="#737a83" />
            <?php else : ?>
                <stop offset="0" stop-color="#f1f2f4" />
                <stop offset="0.45" stop-color="#d3d6da" />
                <stop offset="1" stop-color="#a7acb2" />
            <?php endif; ?>
        </linearGradient>

        <?php // Plastic end caps ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-cap" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#62666c" />
            <stop offset="1" stop-color="#2f3236" />
        </linearGradient>

        <?php // The film curls back a little at its long edges and catches the light in a soft band. ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-curl" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#000" stop-opacity="0.12" />
            <stop offset="0.07" stop-color="#000" stop-opacity="0" />
            <stop offset="0.93" stop-color="#000" stop-opacity="0" />
            <stop offset="1" stop-color="#000" stop-opacity="0.1" />
        </linearGradient>
        <linearGradient id="<?php echo esc_attr($uid); ?>-sheen" x1="0" y1="0" x2="1" y2="0.6">
            <stop offset="0.25" stop-color="#fff" stop-opacity="0" />
            <stop offset="0.42" stop-color="#fff" stop-opacity="0.16" />
            <stop offset="0.52" stop-color="#fff" stop-opacity="0.05" />
            <stop offset="0.68" stop-color="#fff" stop-opacity="0" />
        </linearGradient>
    </defs>

    <?php // The pole behind the banner: its hook stands a little above the rail ?>
    <rect x="<?php echo esc_attr($pole - 0.8); ?>" y="<?php echo esc_attr($rail['y'] - 2.2); ?>" width="1.6" height="3.6" rx="0.8" fill="url(#<?php echo esc_attr($uid); ?>-cap)" />

    <?php // The banner ?>
    <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" fill="#ffffff" />
    <?php
    Template::output('mockup/parts/motif', [
        'uid' => $uid,
        'box' => $mockup->banner,
        'motif' => 'motif/flyer/'.$mockup->motif,
        'viewBox' => '0 0 100 141.4',
        'fit' => 'slice',
        'backing' => '#fff',
    ]);
    ?>
    <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" fill="url(#<?php echo esc_attr($uid); ?>-curl)" pointer-events="none" />
    <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" fill="url(#<?php echo esc_attr($uid); ?>-sheen)" pointer-events="none" />

    <?php // The rail clamping the banner's top edge, with its shadow on the film ?>
    <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($rail['y'] + $rail['height'] - 0.2); ?>" width="<?php echo esc_attr($w); ?>" height="0.7" fill="#000" fill-opacity="0.14" />
    <rect x="<?php echo esc_attr($rail['x']); ?>" y="<?php echo esc_attr($rail['y']); ?>" width="<?php echo esc_attr($rail['width']); ?>" height="<?php echo esc_attr($rail['height']); ?>" rx="0.5" fill="url(#<?php echo esc_attr($uid); ?>-metal)" />
    <rect x="<?php echo esc_attr($rail['x']); ?>" y="<?php echo esc_attr($rail['y']); ?>" width="1" height="<?php echo esc_attr($rail['height']); ?>" rx="0.4" fill="url(#<?php echo esc_attr($uid); ?>-cap)" />
    <rect x="<?php echo esc_attr($rail['x'] + $rail['width'] - 1); ?>" y="<?php echo esc_attr($rail['y']); ?>" width="1" height="<?php echo esc_attr($rail['height']); ?>" rx="0.4" fill="url(#<?php echo esc_attr($uid); ?>-cap)" />

    <?php if ($premium) : ?>
        <?php // A wide cassette with round ends, chrome all over, on a rubber strip ?>
        <rect x="<?php echo esc_attr($cx + 1.5); ?>" y="<?php echo esc_attr($cy + $ch - 1.2); ?>" width="<?php echo esc_attr($cw - 3); ?>" height="1.6" rx="0.8" fill="#2c2f33" />
        <rect x="<?php echo esc_attr($cx); ?>" y="<?php echo esc_attr($cy); ?>" width="<?php echo esc_attr($cw); ?>" height="<?php echo esc_attr($ch - 0.6); ?>" rx="<?php echo esc_attr(($ch - 0.6) / 2); ?>" fill="url(#<?php echo esc_attr($uid); ?>-metal)" />
        <path d="M<?php echo esc_attr($x - 0.6); ?> <?php echo esc_attr($cy + 1); ?>H<?php echo esc_attr($x + $w + 0.6); ?>" stroke="#1d1d1b" stroke-opacity="0.55" stroke-width="0.5" stroke-linecap="round" />
        <path d="M<?php echo esc_attr($cx + 3); ?> <?php echo esc_attr($cy + 0.5); ?>H<?php echo esc_attr($cx + $cw - 3); ?>" stroke="#fff" stroke-opacity="0.9" stroke-width="0.3" stroke-linecap="round" />
    <?php else : ?>
        <?php // Two flat feet swung out under the ends, lying on the floor in front of the box ?>
        <?php foreach ([$cx - 2.4, $cx + $cw - 7.6] as $foot) : ?>
            <rect x="<?php echo esc_attr($foot); ?>" y="<?php echo esc_attr($cy + $ch - 1); ?>" width="10" height="2.8" rx="1.4" fill="url(#<?php echo esc_attr($uid); ?>-cap)" />
            <rect x="<?php echo esc_attr($foot + 0.8); ?>" y="<?php echo esc_attr($cy + $ch - 0.7); ?>" width="8.4" height="0.7" rx="0.35" fill="#9aa0a7" />
        <?php endforeach; ?>

        <?php // A plain silver box: its lid catches the light, the slot takes the banner ?>
        <rect x="<?php echo esc_attr($cx); ?>" y="<?php echo esc_attr($cy); ?>" width="<?php echo esc_attr($cw); ?>" height="<?php echo esc_attr($ch); ?>" rx="0.6" fill="url(#<?php echo esc_attr($uid); ?>-metal)" />
        <rect x="<?php echo esc_attr($cx); ?>" y="<?php echo esc_attr($cy); ?>" width="<?php echo esc_attr($cw); ?>" height="2" rx="0.6" fill="#f7f8f9" />
        <path d="M<?php echo esc_attr($x - 0.4); ?> <?php echo esc_attr($cy + 0.7); ?>H<?php echo esc_attr($x + $w + 0.4); ?>" stroke="#1d1d1b" stroke-opacity="0.55" stroke-width="0.5" stroke-linecap="round" />
        <path d="M<?php echo esc_attr($cx); ?> <?php echo esc_attr($cy + 2); ?>H<?php echo esc_attr($cx + $cw); ?>" stroke="#000" stroke-opacity="0.12" stroke-width="0.3" />
        <path d="M<?php echo esc_attr($cx + 0.4); ?> <?php echo esc_attr($cy + $ch - 0.2); ?>H<?php echo esc_attr($cx + $cw - 0.4); ?>" stroke="#000" stroke-opacity="0.18" stroke-width="0.4" />
        <rect x="<?php echo esc_attr($cx); ?>" y="<?php echo esc_attr($cy); ?>" width="1.9" height="<?php echo esc_attr($ch); ?>" rx="0.7" fill="url(#<?php echo esc_attr($uid); ?>-cap)" />
        <rect x="<?php echo esc_attr($cx + $cw - 1.9); ?>" y="<?php echo esc_attr($cy); ?>" width="1.9" height="<?php echo esc_attr($ch); ?>" rx="0.7" fill="url(#<?php echo esc_attr($uid); ?>-cap)" />
    <?php endif; ?>
</svg>
