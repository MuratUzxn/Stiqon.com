<?php
/**
 * A brochure as inline SVG. See Stiqon\Shop\Mockup\Brochure for the formats and bindings.
 *
 * @var \Stiqon\Shop\Mockup\Brochure $mockup
 * @var string                         $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$x = $mockup->cover['x'];
$y = $mockup->cover['y'];
$w = $mockup->cover['width'];
$h = $mockup->cover['height'];
$dx = $mockup->edge['x'];
$dy = $mockup->edge['y'];
$glued = $mockup->binding === 'perfect';
$motif = 'motif/flyer/'.$mockup->motif;
$strip = $mockup->spineStrip();

$right = round($x + $w, 2);
$bottom = round($y + $h, 2);
$under = ['x' => round($x - $dx, 2), 'right' => round($x + $w - $dx, 2), 'top' => round($y + $dy, 2), 'bottom' => round($y + $h + $dy, 2)];

// The two edges that show: the spine on the left, the page block along the bottom.
$spine = "M{$x} {$y}V{$bottom}L{$under['x']} {$under['bottom']}V{$under['top']}z";
$block = "M{$x} {$bottom}H{$right}L{$under['right']} {$under['bottom']}H{$under['x']}z";
// A line through the page block, $at of the way from the cover down to the table.
$page = static fn (float $at): string => 'M'.round($x - $dx * $at, 2).' '.round($y + $h + $dy * $at, 2).'h'.$w;
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="brochure"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <?php // The cover's outermost strip, laid over the spine (see Brochure::spineTransform()) ?>
        <clipPath id="<?php echo esc_attr($uid); ?>-spine">
            <rect x="<?php echo esc_attr($strip['x']); ?>" y="<?php echo esc_attr($strip['y']); ?>" width="<?php echo esc_attr($strip['width']); ?>" height="<?php echo esc_attr($strip['height']); ?>" />
        </clipPath>

        <?php // The spine faces away from the light: a glued spine is flat, a folded one turns from the cover into the shade. ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-turn" x1="1" y1="0" x2="0" y2="0">
            <?php if ($glued) : ?>
                <stop offset="0" stop-color="#000" stop-opacity="0.14" />
                <stop offset="1" stop-color="#000" stop-opacity="0.24" />
            <?php else : ?>
                <stop offset="0" stop-color="#000" stop-opacity="0.04" />
                <stop offset="1" stop-color="#000" stop-opacity="0.3" />
            <?php endif; ?>
        </linearGradient>

        <linearGradient id="<?php echo esc_attr($uid); ?>-block" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#f6f5f1" />
            <stop offset="1" stop-color="#e4e2dc" />
        </linearGradient>

        <?php if (! $glued) : ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-wire" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0" stop-color="#9ea2a8" />
                <stop offset="0.45" stop-color="#f4f5f7" />
                <stop offset="1" stop-color="#b9bdc2" />
            </linearGradient>
        <?php endif; ?>

        <?php if ($mockup->gloss) : ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-gloss" x1="0.05" y1="0" x2="0.95" y2="1">
                <stop offset="0.32" stop-color="#fff" stop-opacity="0" />
                <stop offset="0.46" stop-color="#fff" stop-opacity="0.22" />
                <stop offset="0.56" stop-color="#fff" stop-opacity="0.07" />
                <stop offset="0.7" stop-color="#fff" stop-opacity="0" />
            </linearGradient>
        <?php endif; ?>
    </defs>

    <g transform="rotate(<?php echo esc_attr($mockup->tilt); ?> 60 60)">
        <?php // Page block: the edges of the pages between cover and back ?>
        <path d="<?php echo esc_attr($block); ?>" fill="url(#<?php echo esc_attr($uid); ?>-block)" />
        <g fill="none" stroke="#0a0a0a" stroke-opacity="0.09" stroke-width="0.2">
            <?php foreach ($glued ? [0.2, 0.4, 0.6, 0.8] : [0.5] as $at) : ?>
                <path d="<?php echo esc_attr($page($at)); ?>" />
            <?php endforeach; ?>
        </g>

        <?php // Spine: printed like the cover, which runs round it ?>
        <path d="<?php echo esc_attr($spine); ?>" fill="#ffffff" />
        <?php
        Template::output('mockup/parts/motif', [
            'uid' => $uid.'-spine',
            'box' => $mockup->cover,
            'motif' => $motif,
            'viewBox' => '0 0 100 141.4',
            'fit' => 'slice',
            'backing' => '#fff',
            'attributes' => 'transform="'.esc_attr($mockup->spineTransform()).'" clip-path="url(#'.esc_attr($uid).'-spine)"',
        ]);
        ?>
        <path d="<?php echo esc_attr($spine); ?>" fill="url(#<?php echo esc_attr($uid); ?>-turn)" />

        <?php foreach ($mockup->staples() as $staple) : ?>
            <?php // A staple's wire on the fold, and the shadow it casts there: drawn a little large, or it would not show at the size of a card ?>
            <rect x="<?php echo esc_attr(round($staple['x'] - 0.55, 2)); ?>" y="<?php echo esc_attr(round($staple['y'] - 3.1, 2)); ?>" width="1.1" height="6.6" rx="0.55" fill="#000" fill-opacity="0.22" />
            <rect
                x="<?php echo esc_attr(round($staple['x'] - 0.48, 2)); ?>" y="<?php echo esc_attr(round($staple['y'] - 3.3, 2)); ?>" width="0.96" height="6.6" rx="0.48"
                fill="url(#<?php echo esc_attr($uid); ?>-wire)" stroke="#0a0a0a" stroke-opacity="0.4" stroke-width="0.16"
            />
        <?php endforeach; ?>

        <?php // Cover ?>
        <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" fill="#ffffff" />
        <?php
        Template::output('mockup/parts/motif', [
            'uid' => $uid,
            'box' => $mockup->cover,
            'motif' => $motif,
            'viewBox' => '0 0 100 141.4',
            'fit' => 'slice',
            'backing' => '#fff',
        ]);
        ?>

        <?php if ($glued) : ?>
            <?php // The groove a glued cover is scored with, so it opens beside the spine instead of breaking it ?>
            <path d="M<?php echo esc_attr(round($x + 2.8, 2)); ?> <?php echo esc_attr($y); ?>v<?php echo esc_attr($h); ?>" stroke="#000" stroke-opacity="0.1" stroke-width="0.35" />
            <path d="M<?php echo esc_attr(round($x + 3.15, 2)); ?> <?php echo esc_attr($y); ?>v<?php echo esc_attr($h); ?>" stroke="#fff" stroke-opacity="0.3" stroke-width="0.3" />
        <?php endif; ?>

        <?php if ($mockup->gloss) : ?>
            <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" fill="url(#<?php echo esc_attr($uid); ?>-gloss)" pointer-events="none" />
        <?php endif; ?>

        <?php // Edges: light where the cover turns into the spine, a fine line round the whole ?>
        <path d="M<?php echo esc_attr($x); ?> <?php echo esc_attr($y); ?>v<?php echo esc_attr($h); ?>h<?php echo esc_attr($w); ?>" fill="none" stroke="#fff" stroke-opacity="<?php echo esc_attr($mockup->gloss ? '0.7' : '0.45'); ?>" stroke-width="0.3" />
        <path
            d="<?php echo esc_attr("M{$x} {$y}H{$right}V{$bottom}L{$under['right']} {$under['bottom']}H{$under['x']}V{$under['top']}z"); ?>"
            fill="none" stroke="#0a0a0a" stroke-opacity="0.12" stroke-width="0.4" stroke-linejoin="round"
        />
    </g>
</svg>
