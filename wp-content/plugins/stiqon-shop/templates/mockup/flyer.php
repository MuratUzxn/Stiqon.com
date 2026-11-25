<?php
/**
 * A flyer as inline SVG. See Stiqon\Shop\Mockup\Flyer for the formats.
 *
 * @var \Stiqon\Shop\Mockup\Flyer $mockup
 * @var string                      $class  extra classes from the caller
 */

use Stiqon\Shop\Html;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$motif = $mockup->motif;
$stack = $mockup->stack;
$heavy = $mockup->heavy;
$gloss = $mockup->gloss;
$tilt = $mockup->tilt;
$x = $mockup->sheet['x'];
$y = $mockup->sheet['y'];
$w = $mockup->sheet['width'];
$h = $mockup->sheet['height'];

// A width class from the caller (w-…, md:w-…) replaces the full-width default.
$sized = (bool) preg_match('/(^|[\s:])w-/', $class);
$classes = Html::classes(['lift block h-auto overflow-visible', 'w-full' => ! $sized, $class]);
?>
<svg
    viewBox="0 0 120 150"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="flyer"
    class="<?php echo esc_attr($classes); ?>"
>
    <defs>
        <linearGradient id="<?php echo esc_attr($uid); ?>-gloss" x1="0.05" y1="0" x2="0.95" y2="1">
            <stop offset="0.32" stop-color="#fff" stop-opacity="0" />
            <stop offset="0.46" stop-color="#fff" stop-opacity="0.2" />
            <stop offset="0.56" stop-color="#fff" stop-opacity="0.06" />
            <stop offset="0.7" stop-color="#fff" stop-opacity="0" />
        </linearGradient>
    </defs>

    <?php if ($stack) : ?>
        <?php // Sheets underneath, fanned out slightly ?>
        <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="#eeede8" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" transform="rotate(8 60 75)" />
        <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="#f6f5f1" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" transform="rotate(2.5 60 75)" />
    <?php endif; ?>

    <g transform="rotate(<?php echo esc_attr($tilt); ?> 60 75)">
        <?php if ($heavy) : ?>
            <?php // The edge of thicker stock ?>
            <rect x="<?php echo esc_attr($x + 0.9); ?>" y="<?php echo esc_attr($y + 1.1); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="#d6d6d0" />
        <?php endif; ?>

        <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="#ffffff" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" />

        <?php // Printed side: the sample motif, replaced by the visitor's own motif once chosen ?>
        <g x-data="motifLayer">
            <svg
                x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>"
                viewBox="0 0 100 141.4" preserveAspectRatio="xMidYMid slice" x-show="idle" data-motif-sample
            >
                <?php Template::output('motif/flyer/'.$motif, ['uid' => $uid]); ?>
            </svg>
            <?php // Hidden by its own markup, not by a stylesheet: the drawing also has to be right where no script and no theme is, as in the administration. ?>
            <g x-show="active" style="display:none">
                <image
                    x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>"
                    preserveAspectRatio="xMidYMid slice" x-bind:href="url" data-motif-image
                />
            </g>
        </g>

        <?php if ($gloss) : ?>
            <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" fill="url(#<?php echo esc_attr($uid); ?>-gloss)" pointer-events="none" />
        <?php endif; ?>
    </g>
</svg>
