<?php
/**
 * A printed card as inline SVG. See Stiqon\Shop\Mockup\Card for the formats.
 *
 * The printed side is drawn once, in its own coordinates, and placed by
 * Card::transform(): turned on the page, or slanted when the card stands folded.
 *
 * @var \Stiqon\Shop\Mockup\Card $mockup
 * @var string                    $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$w = $mockup->face['width'];
$h = $mockup->face['height'];
$tent = $mockup->tent;
$finish = $mockup->finish;
$foil = $mockup->foil();
$shines = $mockup->shines();

// The hot-foil frame sits a little inside the edge; a diamond marks each of its corners.
$inset = round(min($w, $h) * 0.06, 2);
$corners = [[$inset, $inset], [$w - $inset, $inset], [$w - $inset, $h - $inset], [$inset, $h - $inset]];
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="card"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <clipPath id="<?php echo esc_attr($uid); ?>-print">
            <rect width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" />
        </clipPath>

        <?php if ($shines) : ?>
            <?php // A diagonal band of light: stock that shines. The faint darker reflection beside it lets the shine show on light designs too. ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-gloss" x1="0.05" y1="0" x2="0.95" y2="1">
                <stop offset="0.26" stop-color="#000" stop-opacity="0" />
                <stop offset="0.38" stop-color="#000" stop-opacity="0.045" />
                <stop offset="0.44" stop-color="#fff" stop-opacity="0.34" />
                <stop offset="0.54" stop-color="#fff" stop-opacity="0.1" />
                <stop offset="0.7" stop-color="#fff" stop-opacity="0" />
            </linearGradient>
        <?php endif; ?>

        <?php if ($foil) : ?>
            <?php // Foil reflects: light and dark run along the frame as it turns towards the light and away. ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-foil" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="<?php echo esc_attr($w); ?>" y2="<?php echo esc_attr($h); ?>">
                <?php foreach ($foil as $index => $color) : ?>
                    <stop offset="<?php echo esc_attr(round($index / (count($foil) - 1), 3)); ?>" stop-color="<?php echo esc_attr($color); ?>" />
                <?php endforeach; ?>
            </linearGradient>
        <?php endif; ?>

        <?php if ($finish === 'softtouch') : ?>
            <?php // Velvet: light falls off softly across the whole card, with no band of shine anywhere, over a fine grain. ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-velvet" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#fff" stop-opacity="0.14" />
                <stop offset="0.45" stop-color="#fff" stop-opacity="0.02" />
                <stop offset="1" stop-color="#000" stop-opacity="0.1" />
            </linearGradient>
            <?php // Specks at uneven places, so the grain does not line up into a weave ?>
            <pattern id="<?php echo esc_attr($uid); ?>-grain" width="6" height="6" patternUnits="userSpaceOnUse">
                <g fill="#000" fill-opacity="0.07">
                    <circle cx="0.6" cy="0.9" r="0.2" />
                    <circle cx="4.1" cy="1.2" r="0.22" />
                    <circle cx="3.2" cy="3.1" r="0.2" />
                    <circle cx="0.5" cy="4.2" r="0.18" />
                    <circle cx="4.6" cy="4.4" r="0.2" />
                    <circle cx="1.7" cy="5.7" r="0.16" />
                </g>
                <g fill="#fff" fill-opacity="0.12">
                    <circle cx="2.3" cy="0.4" r="0.18" />
                    <circle cx="5.4" cy="2.6" r="0.18" />
                    <circle cx="1.4" cy="2.4" r="0.2" />
                    <circle cx="2.6" cy="4.9" r="0.22" />
                    <circle cx="5.6" cy="5.5" r="0.16" />
                    <circle cx="3.9" cy="5.6" r="0.18" />
                </g>
            </pattern>
        <?php endif; ?>

        <?php if ($tent) : ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-inside" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#dcdbd6" />
                <stop offset="1" stop-color="#b4b3ad" />
            </linearGradient>
            <?php // The front side leans back: lit from above, a shade darker at its foot. ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-slope" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#fff" stop-opacity="0.06" />
                <stop offset="1" stop-color="#000" stop-opacity="0.08" />
            </linearGradient>
        <?php endif; ?>
    </defs>

    <?php if ($mockup->stack) : ?>
        <?php // Cards underneath, fanned out slightly ?>
        <?php foreach ([[5.5, '#ecebe6'], [1.0, '#f5f4f0']] as [$angle, $paper]) : ?>
            <g transform="rotate(<?php echo esc_attr($angle); ?> 60 60) translate(<?php echo esc_attr(round(60 - $w / 2, 2)); ?> <?php echo esc_attr(round(60 - $h / 2, 2)); ?>)">
                <rect x="0.5" y="0.7" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="#d6d5cf" />
                <rect width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="<?php echo esc_attr($paper); ?>" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" />
            </g>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($tent) : ?>
        <?php // The inside of the back side, seen through the open end of the tent ?>
        <path
            d="M<?php echo esc_attr($tent['ridge'][0][0]); ?> <?php echo esc_attr($tent['ridge'][0][1]); ?>L<?php echo esc_attr($tent['ridge'][1][0]); ?> <?php echo esc_attr($tent['ridge'][1][1]); ?>L<?php echo esc_attr($tent['back'][1][0]); ?> <?php echo esc_attr($tent['back'][1][1]); ?>L<?php echo esc_attr($tent['back'][0][0]); ?> <?php echo esc_attr($tent['back'][0][1]); ?>z"
            fill="url(#<?php echo esc_attr($uid); ?>-inside)" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" stroke-linejoin="round"
        />
    <?php endif; ?>

    <g transform="<?php echo esc_attr($mockup->transform()); ?>">
        <?php if (! $tent) : ?>
            <?php // The edge of the stiff card ?>
            <rect x="0.5" y="0.7" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="#d6d5cf" />
        <?php endif; ?>

        <rect width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="#ffffff" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" />

        <?php // Printed side: the sample motif, replaced by the visitor's own motif once chosen ?>
        <?php
        Template::output('mockup/parts/motif', [
            'uid' => $uid,
            'box' => Svg::box(0, 0, $w, $h),
            'motif' => 'motif/card/'.$mockup->motif,
            'viewBox' => '0 0 100 64.7',
            'fit' => 'slice',
            'backing' => '#fff',
            'attributes' => 'clip-path="url(#'.esc_attr($uid).'-print)"',
        ]);
        ?>

        <?php if ($finish === 'softtouch') : ?>
            <rect width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="url(#<?php echo esc_attr($uid); ?>-grain)" pointer-events="none" />
            <rect width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="url(#<?php echo esc_attr($uid); ?>-velvet)" pointer-events="none" />
        <?php endif; ?>

        <?php if ($foil) : ?>
            <?php // Hot foil: a fine frame with a second, finer line inside it, and a diamond on each corner ?>
            <g fill="none" stroke="url(#<?php echo esc_attr($uid); ?>-foil)" pointer-events="none">
                <rect x="<?php echo esc_attr($inset); ?>" y="<?php echo esc_attr($inset); ?>" width="<?php echo esc_attr(round($w - 2 * $inset, 2)); ?>" height="<?php echo esc_attr(round($h - 2 * $inset, 2)); ?>" stroke-width="0.75" />
                <rect x="<?php echo esc_attr(round($inset + 1.4, 2)); ?>" y="<?php echo esc_attr(round($inset + 1.4, 2)); ?>" width="<?php echo esc_attr(round($w - 2 * $inset - 2.8, 2)); ?>" height="<?php echo esc_attr(round($h - 2 * $inset - 2.8, 2)); ?>" stroke-width="0.3" />
            </g>
            <g fill="url(#<?php echo esc_attr($uid); ?>-foil)" pointer-events="none">
                <?php foreach ($corners as [$cx, $cy]) : ?>
                    <path d="M<?php echo esc_attr(round($cx - 1.6, 2)); ?> <?php echo esc_attr(round($cy, 2)); ?>L<?php echo esc_attr(round($cx, 2)); ?> <?php echo esc_attr(round($cy - 1.6, 2)); ?>L<?php echo esc_attr(round($cx + 1.6, 2)); ?> <?php echo esc_attr(round($cy, 2)); ?>L<?php echo esc_attr(round($cx, 2)); ?> <?php echo esc_attr(round($cy + 1.6, 2)); ?>z" />
                <?php endforeach; ?>
            </g>
        <?php endif; ?>

        <?php if ($tent) : ?>
            <rect width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" fill="url(#<?php echo esc_attr($uid); ?>-slope)" pointer-events="none" />
        <?php endif; ?>

        <?php if ($shines) : ?>
            <rect width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="url(#<?php echo esc_attr($uid); ?>-gloss)" pointer-events="none" />
        <?php endif; ?>
    </g>

    <?php if ($tent) : ?>
        <?php // The fold catches the light; the front side's cut edge shows at the open end. ?>
        <path d="M<?php echo esc_attr($tent['ridge'][0][0]); ?> <?php echo esc_attr($tent['ridge'][0][1]); ?>H<?php echo esc_attr($tent['ridge'][1][0]); ?>" stroke="#ffffff" stroke-opacity="0.85" stroke-width="0.5" stroke-linecap="round" />
        <path d="M<?php echo esc_attr($tent['ridge'][1][0]); ?> <?php echo esc_attr($tent['ridge'][1][1]); ?>L<?php echo esc_attr($tent['front'][1][0]); ?> <?php echo esc_attr($tent['front'][1][1]); ?>" stroke="#0a0a0a" stroke-opacity="0.16" stroke-width="0.4" stroke-linecap="round" />
    <?php endif; ?>
</svg>
