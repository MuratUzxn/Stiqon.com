<?php
/**
 * A sticker as inline SVG. See Stiqon\Shop\Mockup\Sticker for the geometry.
 *
 * @var \Stiqon\Shop\Mockup\Sticker $mockup
 * @var string                        $class  extra classes from the caller
 */

use Stiqon\Shop\Html;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$material = $mockup->material;
$finish = $mockup->finish;
$motif = $mockup->motif;
$outline = $mockup->outline;
$printArea = $mockup->printArea;
$contour = $mockup->contour;
$box = $mockup->printBox;
$corner = $mockup->peelCorner();
$blend = in_array($material, ['holo', 'kraft'], true) ? 'mix-blend-mode:multiply' : null;
$backing = match ($material) {
    'vinyl' => '#ffffff',
    'holo' => "url(#{$uid}-holo)",
    'kraft' => '#c9a57c',
    'clear' => 'none',
};

// A width class from the caller (w-…, md:w-…) replaces the full-width default.
$sized = (bool) preg_match('/(^|[\s:])w-/', $class);
$classes = Html::classes([
    'block h-auto overflow-visible',
    'w-full' => ! $sized,
    $material === 'clear' ? 'lift-faint' : 'lift',
    $class,
]);
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="sticker"
    class="<?php echo esc_attr($classes); ?>"
>
    <defs>
        <clipPath id="<?php echo esc_attr($uid); ?>-print">
            <?php if ($contour) : ?>
                <path d="<?php echo esc_attr($contour); ?>" transform="translate(10 10)" />
            <?php else : ?>
                <path d="<?php echo esc_attr($printArea); ?>" />
            <?php endif; ?>
        </clipPath>

        <?php // Diagonal band of light: the laminate. Fainter on matte stock. ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-gloss" x1="0.08" y1="0" x2="0.92" y2="1">
            <stop offset="0.22" stop-color="#fff" stop-opacity="0" />
            <stop offset="0.44" stop-color="#fff" stop-opacity="<?php echo esc_attr($finish === 'gloss' ? '0.24' : '0.07'); ?>" />
            <stop offset="0.56" stop-color="#fff" stop-opacity="<?php echo esc_attr($finish === 'gloss' ? '0.08' : '0.02'); ?>" />
            <stop offset="0.78" stop-color="#fff" stop-opacity="0" />
        </linearGradient>

        <?php if ($material === 'holo') : ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-holo" x1="0" y1="0.1" x2="1" y2="0.9">
                <stop offset="0" stop-color="#c7f0ff" />
                <stop offset="0.2" stop-color="#e2d2ff" />
                <stop offset="0.4" stop-color="#ffd4ea" />
                <stop offset="0.6" stop-color="#fff0b8" />
                <stop offset="0.8" stop-color="#c8ffe1" />
                <stop offset="1" stop-color="#c7f0ff" />
            </linearGradient>
        <?php endif; ?>

        <?php if ($material === 'kraft') : ?>
            <pattern id="<?php echo esc_attr($uid); ?>-fibre" width="14" height="14" patternUnits="userSpaceOnUse" patternTransform="rotate(18)">
                <path d="M1 3h3M9 9h4M4 12h2" stroke="#8a6a45" stroke-width="0.3" stroke-opacity="0.2" stroke-linecap="round" />
                <path d="M9 2h2M0 7h3" stroke="#f0d9ba" stroke-width="0.3" stroke-opacity="0.3" stroke-linecap="round" />
            </pattern>
        <?php endif; ?>

        <?php if ($corner) : ?>
            <mask id="<?php echo esc_attr($uid); ?>-peel">
                <rect width="120" height="120" fill="#fff" />
                <path d="M<?php echo esc_attr($corner[0][0]); ?> <?php echo esc_attr($corner[0][1]); ?>L<?php echo esc_attr($corner[1][0]); ?> <?php echo esc_attr($corner[1][1]); ?>L<?php echo esc_attr($corner[1][0]); ?> <?php echo esc_attr($corner[0][1]); ?>z" fill="#000" />
            </mask>
            <linearGradient id="<?php echo esc_attr($uid); ?>-flap" x1="1" y1="1" x2="0" y2="0">
                <stop offset="0" stop-color="#cfcfca" />
                <stop offset="0.55" stop-color="#f1f1ee" />
                <stop offset="1" stop-color="#ffffff" />
            </linearGradient>
            <linearGradient id="<?php echo esc_attr($uid); ?>-flap-shade" x1="1" y1="1" x2="0" y2="0">
                <stop offset="0" stop-color="#000" stop-opacity="0.2" />
                <stop offset="1" stop-color="#000" stop-opacity="0" />
            </linearGradient>
        <?php endif; ?>
    </defs>

    <g <?php if ($corner) : ?> mask="url(#<?php echo esc_attr($uid); ?>-peel)" <?php endif; ?>>
        <?php // Backing material ?>
        <?php if ($contour) : ?>
            <g transform="translate(10 10)">
                <path d="<?php echo esc_attr($contour); ?>" fill="none" stroke="#0a0a0a" stroke-opacity="0.08" stroke-width="9.6" stroke-linejoin="round" />
                <path d="<?php echo esc_attr($contour); ?>" fill="<?php echo esc_attr($backing === 'none' ? 'none' : $backing); ?>" stroke="<?php echo esc_attr($backing === 'none' ? 'none' : $backing); ?>" stroke-width="9" stroke-linejoin="round" />
            </g>
        <?php elseif ($material !== 'clear') : ?>
            <path d="<?php echo esc_attr($outline); ?>" fill="<?php echo esc_attr($backing); ?>" stroke="#0a0a0a" stroke-opacity="0.08" stroke-width="0.5" />
            <?php if ($material === 'kraft') : ?>
                <path d="<?php echo esc_attr($outline); ?>" fill="url(#<?php echo esc_attr($uid); ?>-fibre)" />
            <?php endif; ?>
        <?php endif; ?>

        <?php // Printed area: the sample motif, replaced by the visitor's own motif once chosen ?>
        <g clip-path="url(#<?php echo esc_attr($uid); ?>-print)" <?php if ($blend) : ?> style="<?php echo esc_attr($blend); ?>" <?php endif; ?> x-data="motifLayer">
            <svg
                x="<?php echo esc_attr($box['x']); ?>" y="<?php echo esc_attr($box['y']); ?>" width="<?php echo esc_attr($box['width']); ?>" height="<?php echo esc_attr($box['height']); ?>"
                viewBox="0 0 100 100" preserveAspectRatio="xMidYMid slice" x-show="idle" data-motif-sample
            >
                <?php Template::output('motif/'.$motif, ['uid' => $uid, 'bare' => $mockup->bareMotif()]); ?>
            </svg>
            <?php // Hidden by its own markup, not by a stylesheet: the drawing also has to be right where no script and no theme is, as in the administration. ?>
            <g x-show="active" style="display:none">
                <?php if ($material === 'vinyl') : ?>
                    <rect x="<?php echo esc_attr($box['x']); ?>" y="<?php echo esc_attr($box['y']); ?>" width="<?php echo esc_attr($box['width']); ?>" height="<?php echo esc_attr($box['height']); ?>" fill="#fff" />
                <?php endif; ?>
                <image
                    x="<?php echo esc_attr($box['x']); ?>" y="<?php echo esc_attr($box['y']); ?>" width="<?php echo esc_attr($box['width']); ?>" height="<?php echo esc_attr($box['height']); ?>"
                    preserveAspectRatio="xMidYMid slice" x-bind:href="url" data-motif-image
                />
            </g>
        </g>

        <?php // Finish ?>
        <?php if ($material === 'clear' && ! $contour) : ?>
            <path d="<?php echo esc_attr($outline); ?>" fill="none" stroke="#fff" stroke-opacity="0.7" stroke-width="0.9" />
            <path d="<?php echo esc_attr($outline); ?>" fill="none" stroke="#0a0a0a" stroke-opacity="0.08" stroke-width="0.35" />
        <?php endif; ?>
        <rect width="120" height="120" fill="url(#<?php echo esc_attr($uid); ?>-gloss)" clip-path="url(#<?php echo esc_attr($uid); ?>-print)" pointer-events="none" />
    </g>

    <?php if ($corner) : ?>
        <?php // The corner lifted off the backing paper ?>
        <path
            d="M<?php echo esc_attr($corner[0][0]); ?> <?php echo esc_attr($corner[0][1]); ?>L<?php echo esc_attr($corner[1][0]); ?> <?php echo esc_attr($corner[1][1]); ?>L<?php echo esc_attr($corner[2][0] - 9); ?> <?php echo esc_attr($corner[2][1] - 9); ?>z"
            fill="url(#<?php echo esc_attr($uid); ?>-flap-shade)"
        />
        <path
            d="M<?php echo esc_attr($corner[0][0]); ?> <?php echo esc_attr($corner[0][1]); ?>L<?php echo esc_attr($corner[1][0]); ?> <?php echo esc_attr($corner[1][1]); ?>L<?php echo esc_attr($corner[2][0] + 5); ?> <?php echo esc_attr($corner[2][1]); ?>Q<?php echo esc_attr($corner[2][0]); ?> <?php echo esc_attr($corner[2][1]); ?> <?php echo esc_attr($corner[2][0]); ?> <?php echo esc_attr($corner[2][1] + 5); ?>z"
            fill="url(#<?php echo esc_attr($uid); ?>-flap)" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" stroke-linejoin="round"
        />
    <?php endif; ?>
</svg>
