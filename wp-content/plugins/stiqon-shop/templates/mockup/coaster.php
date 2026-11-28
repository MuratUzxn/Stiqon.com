<?php
/**
 * A beer mat as inline SVG. See Stiqon\Shop\Mockup\Coaster for the shapes and
 * how the top and its edge are put in place.
 *
 * @var \Stiqon\Shop\Mockup\Coaster $mockup
 * @var string                       $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$s = $mockup->size;
$r = $mockup->radius;
$left = $mockup->extent['x'];
$right = round($mockup->extent['x'] + $mockup->extent['width'], 2);
$levels = $mockup->levels();
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="coaster"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <clipPath id="<?php echo esc_attr($uid); ?>-face">
            <rect width="<?php echo esc_attr($s); ?>" height="<?php echo esc_attr($s); ?>" rx="<?php echo esc_attr($r); ?>" />
        </clipPath>

        <?php // The cut edge of the board: lit from the left, its lower half a shade darker towards the table ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-edge" gradientUnits="userSpaceOnUse" x1="<?php echo esc_attr($left); ?>" y1="0" x2="<?php echo esc_attr($right); ?>" y2="0">
            <stop offset="0" stop-color="#e0d7c5" />
            <stop offset="0.45" stop-color="#d6ccb8" />
            <stop offset="1" stop-color="#c0b49c" />
        </linearGradient>
        <linearGradient id="<?php echo esc_attr($uid); ?>-lit" gradientUnits="userSpaceOnUse" x1="<?php echo esc_attr($left); ?>" y1="0" x2="<?php echo esc_attr($right); ?>" y2="0">
            <stop offset="0" stop-color="#f5f0e6" />
            <stop offset="0.45" stop-color="#ece5d7" />
            <stop offset="1" stop-color="#d9cfbc" />
        </linearGradient>

        <?php // Short fibres of the pressed board, lying along the edge at uneven places, so they do not line up in rows ?>
        <pattern id="<?php echo esc_attr($uid); ?>-fibres" width="12.6" height="1.9" patternUnits="userSpaceOnUse">
            <path d="M0.3 0.4h1.6M4.2 1.3h2.1M8.1 0.7h1.2M10.6 1.6h1.5" stroke="#8c7d62" stroke-opacity="0.28" stroke-width="0.15" stroke-linecap="round" />
            <path d="M2.2 1h1.3M6.6 0.3h1.7M9.4 1.2h0.8M11.8 0.5h0.6" stroke="#fff" stroke-opacity="0.5" stroke-width="0.15" stroke-linecap="round" />
        </pattern>

        <?php // Uncoated board takes the light softly, from the upper left, with no shine ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-light" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#fff" stop-opacity="0.16" />
            <stop offset="0.5" stop-color="#fff" stop-opacity="0" />
            <stop offset="1" stop-color="#000" stop-opacity="0.07" />
        </linearGradient>
    </defs>

    <?php foreach ($levels as $level) : ?>
        <?php // A mat's cut edge: its top and the same outline one edge further down, joined where the top is widest. ?>
        <?php // The upper half catches the light; a fine dark line where the mat lies on the table, or on the mat below. ?>
        <path d="<?php echo esc_attr($mockup->outline($level, 1.0)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-edge)" stroke="#5f5544" stroke-opacity="<?php echo esc_attr($level === $levels[0] ? '0.3' : '0.18'); ?>" stroke-width="0.3" />
        <polygon points="<?php echo esc_attr($mockup->side($level, 0.0, 1.0)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-edge)" />
        <path d="<?php echo esc_attr($mockup->outline($level, 0.5)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-lit)" />
        <polygon points="<?php echo esc_attr($mockup->side($level, 0.0, 0.5)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-lit)" />
        <path d="<?php echo esc_attr($mockup->outline($level, 1.0)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-fibres)" />
        <polygon points="<?php echo esc_attr($mockup->side($level, 0.0, 1.0)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-fibres)" />

        <?php if ($level > 0) : ?>
            <?php // The top of a mat underneath, in the shade of the one on it: it shows only where the stack is uneven ?>
            <path d="<?php echo esc_attr($mockup->outline($level)); ?>" fill="#cfc6b4" />
        <?php endif; ?>
    <?php endforeach; ?>

    <?php // The top: white board, printed all over ?>
    <g transform="<?php echo esc_attr($mockup->matrix()); ?>">
        <rect width="<?php echo esc_attr($s); ?>" height="<?php echo esc_attr($s); ?>" rx="<?php echo esc_attr($r); ?>" fill="#ffffff" />

        <?php
        Template::output('mockup/parts/motif', [
            'uid' => $uid,
            'box' => Svg::box(0, 0, $s, $s),
            'motif' => 'motif/'.$mockup->motif,
            'viewBox' => '0 0 100 100',
            'fit' => 'slice',
            'ownFit' => 'slice',
            'vars' => $mockup->motifVars(),
            'backing' => '#fff',
            'attributes' => 'clip-path="url(#'.esc_attr($uid).'-face)"',
        ]);
        ?>

        <?php // Over the print as well: the light on the board, and its cut rim catching it ?>
        <rect width="<?php echo esc_attr($s); ?>" height="<?php echo esc_attr($s); ?>" rx="<?php echo esc_attr($r); ?>" fill="url(#<?php echo esc_attr($uid); ?>-light)" pointer-events="none" />
        <rect width="<?php echo esc_attr($s); ?>" height="<?php echo esc_attr($s); ?>" rx="<?php echo esc_attr($r); ?>" fill="none" stroke="#fff" stroke-opacity="0.6" stroke-width="0.7" clip-path="url(#<?php echo esc_attr($uid); ?>-face)" pointer-events="none" />
    </g>
    <path d="<?php echo esc_attr($mockup->outline()); ?>" fill="none" stroke="#0a0a0a" stroke-opacity="0.12" stroke-width="0.35" />
</svg>
