<?php
/**
 * A mug as inline SVG. See Stiqon\Shop\Mockup\Mug for the styles and how the
 * print is laid onto the body.
 *
 * @var \Stiqon\Shop\Mockup\Mug $mockup
 * @var string                    $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Mug;
use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$enamel = $mockup->style === 'enamel';
$black = $mockup->color === 'black';
$grip = Mug::STYLES[$mockup->style]['grip'];
$rim = $mockup->rim;
$x = $mockup->body['x'];
$w = $mockup->body['width'];
$top = $rim['cy'];
$bottom = $mockup->base['cy'];

// glaze: the outside; lip: the top of the wall, which faces the light; inside:
// the inside wall; edge: the outline; rim: the rolled rim of an enamel mug,
// dark on white enamel and on black; speck: the flecks fired into enamel;
// shine: how bright the reflections of the light are – black glaze shows them
// most, white glaze can only show them against its shade.
$tone = $black
    ? ['glaze' => '#1d1d1c', 'lip' => '#2c2c2a', 'inside' => '#151514', 'edge' => '#0b0b0a', 'rim' => '#0b0b0b', 'speck' => '#d8d7d2', 'shine' => 0.32]
    : ['glaze' => '#f7f6f3', 'lip' => '#fdfdfb', 'inside' => '#ecebe6', 'edge' => '#cfcdc6', 'rim' => '#262d38', 'speck' => '#3d3c38', 'shine' => 0.75];

// Fired enamel keeps a few faint specks: where they lie, as shares of the
// wall's width and height, and how large they are.
$specks = [
    [0.14, 0.3, 0.32], [0.27, 0.77, 0.24], [0.36, 0.14, 0.22], [0.49, 0.88, 0.3], [0.62, 0.22, 0.26],
    [0.71, 0.63, 0.22], [0.83, 0.38, 0.3], [0.9, 0.82, 0.24], [0.08, 0.66, 0.24], [0.56, 0.47, 0.2],
];
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="mug"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <clipPath id="<?php echo esc_attr($uid); ?>-body">
            <path d="<?php echo esc_attr($mockup->wall); ?>" />
        </clipPath>

        <?php // Light from the upper left, round the cylinder: the side turning away darkens, a reflection lies left of the middle ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-round" x1="0" y1="0" x2="1" y2="0">
            <?php if ($black) : ?>
                <stop offset="0" stop-color="#fff" stop-opacity="0.05" />
                <stop offset="0.1" stop-color="#fff" stop-opacity="0.1" />
                <stop offset="0.3" stop-color="#fff" stop-opacity="0.03" />
                <stop offset="0.55" stop-color="#000" stop-opacity="0" />
                <stop offset="0.86" stop-color="#000" stop-opacity="0.3" />
                <stop offset="0.97" stop-color="#000" stop-opacity="0.18" />
                <stop offset="1" stop-color="#fff" stop-opacity="0.06" />
            <?php else : ?>
                <stop offset="0" stop-color="#000" stop-opacity="0.13" />
                <stop offset="0.08" stop-color="#000" stop-opacity="0.04" />
                <stop offset="0.24" stop-color="#fff" stop-opacity="0.5" />
                <stop offset="0.4" stop-color="#fff" stop-opacity="0" />
                <stop offset="0.7" stop-color="#000" stop-opacity="0.04" />
                <stop offset="0.88" stop-color="#000" stop-opacity="0.12" />
                <stop offset="0.97" stop-color="#000" stop-opacity="0.15" />
                <stop offset="1" stop-color="#000" stop-opacity="0.09" />
            <?php endif; ?>
        </linearGradient>

        <?php // The wall darkens a little towards the table ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-floor" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0.55" stop-color="#000" stop-opacity="0" />
            <stop offset="1" stop-color="#000" stop-opacity="<?php echo esc_attr($black ? '0.2' : '0.06'); ?>" />
        </linearGradient>

        <?php // A soft band of reflected light, brightest in its middle ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-shine" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#fff" stop-opacity="0" />
            <stop offset="0.5" stop-color="#fff" stop-opacity="<?php echo esc_attr($tone['shine']); ?>" />
            <stop offset="1" stop-color="#fff" stop-opacity="0" />
        </linearGradient>
        <linearGradient id="<?php echo esc_attr($uid); ?>-shine-fade" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#fff" stop-opacity="1" />
            <stop offset="0.75" stop-color="#fff" stop-opacity="1" />
            <stop offset="1" stop-color="#fff" stop-opacity="0" />
        </linearGradient>
        <mask id="<?php echo esc_attr($uid); ?>-fade" maskContentUnits="objectBoundingBox">
            <rect width="1" height="1" fill="url(#<?php echo esc_attr($uid); ?>-shine-fade)" />
        </mask>

        <?php // Inside, the far wall: in shade on the left, where the light does not reach, and deeper down towards the front ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-inside" x1="0" y1="0" x2="1" y2="0.4">
            <stop offset="0" stop-color="#000" stop-opacity="<?php echo esc_attr($black ? '0.35' : '0.2'); ?>" />
            <stop offset="0.6" stop-color="#000" stop-opacity="0.03" />
            <stop offset="1" stop-color="#fff" stop-opacity="<?php echo esc_attr($black ? '0.06' : '0.3'); ?>" />
        </linearGradient>
        <linearGradient id="<?php echo esc_attr($uid); ?>-deep" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0.35" stop-color="#000" stop-opacity="0" />
            <stop offset="1" stop-color="#000" stop-opacity="<?php echo esc_attr($black ? '0.3' : '0.14'); ?>" />
        </linearGradient>
    </defs>

    <?php // The handle, behind the body where it is joined to it: rounded, lit from above, its underside in shade ?>
    <g fill="none" stroke-linecap="round">
        <path d="<?php echo esc_attr($mockup->handle); ?>" stroke="<?php echo esc_attr($tone['edge']); ?>" stroke-width="<?php echo esc_attr($grip + ($enamel ? 0.4 : 0.5)); ?>" />
        <path d="<?php echo esc_attr($mockup->handle); ?>" stroke="<?php echo esc_attr($tone['glaze']); ?>" stroke-width="<?php echo esc_attr($grip); ?>" />
        <path d="<?php echo esc_attr($mockup->handle); ?>" stroke="#000" stroke-opacity="<?php echo esc_attr($black ? '0.3' : '0.1'); ?>" stroke-width="<?php echo esc_attr(round($grip * 0.45, 2)); ?>" transform="translate(<?php echo esc_attr(round($grip * 0.2, 2)); ?> <?php echo esc_attr(round($grip * 0.2, 2)); ?>)" />
        <path d="<?php echo esc_attr($mockup->handle); ?>" stroke="#fff" stroke-opacity="<?php echo esc_attr($black ? '0.16' : '0.7'); ?>" stroke-width="<?php echo esc_attr(round($grip * 0.22, 2)); ?>" transform="translate(<?php echo esc_attr(round(-$grip * 0.18, 2)); ?> <?php echo esc_attr(round(-$grip * 0.18, 2)); ?>)" />
    </g>

    <?php // The body ?>
    <path d="<?php echo esc_attr($mockup->wall); ?>" fill="<?php echo esc_attr($tone['glaze']); ?>" />

    <?php if ($enamel) : ?>
        <g clip-path="url(#<?php echo esc_attr($uid); ?>-body)" fill="<?php echo esc_attr($tone['speck']); ?>" fill-opacity="0.22">
            <?php foreach ($specks as [$sx, $sy, $size]) : ?>
                <circle cx="<?php echo esc_attr(round($x + $sx * $w, 2)); ?>" cy="<?php echo esc_attr(round($top + $sy * ($bottom - $top), 2)); ?>" r="<?php echo esc_attr($size); ?>" />
            <?php endforeach; ?>
        </g>
    <?php endif; ?>

    <?php
    // The samples are printed straight onto the glaze or the enamel, without a
    // ground of their own: in dark ink on white, in light ink on black
    // (Mug::motifVars()). A visitor's logo gets no backing either, so the mug
    // shows where it is transparent. Clipped to the wall, so nothing reaches
    // past its edge.
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

    <?php // Over the print: the light and shade round the cylinder and the reflections in the glaze, so the print reads as wrapped round the mug ?>
    <g pointer-events="none">
        <path d="<?php echo esc_attr($mockup->wall); ?>" fill="url(#<?php echo esc_attr($uid); ?>-round)" />
        <path d="<?php echo esc_attr($mockup->wall); ?>" fill="url(#<?php echo esc_attr($uid); ?>-floor)" />
        <g clip-path="url(#<?php echo esc_attr($uid); ?>-body)">
            <rect x="<?php echo esc_attr(round($x + 0.15 * $w, 2)); ?>" y="<?php echo esc_attr(round($top + $rim['ry'] + 2.5, 2)); ?>" width="<?php echo esc_attr(round(0.075 * $w, 2)); ?>" height="<?php echo esc_attr(round($bottom - $top - $rim['ry'] - 3.5, 2)); ?>" rx="<?php echo esc_attr(round(0.0375 * $w, 2)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-shine)" mask="url(#<?php echo esc_attr($uid); ?>-fade)" />
            <rect x="<?php echo esc_attr(round($x + 0.87 * $w, 2)); ?>" y="<?php echo esc_attr(round($top + $rim['ry'] + 4.0, 2)); ?>" width="<?php echo esc_attr(round(0.035 * $w, 2)); ?>" height="<?php echo esc_attr(round(0.55 * ($bottom - $top), 2)); ?>" rx="<?php echo esc_attr(round(0.0175 * $w, 2)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-shine)" opacity="0.45" />
        </g>
    </g>

    <?php // The top of the wall, then the inside seen through the opening ?>
    <ellipse cx="<?php echo esc_attr($rim['cx']); ?>" cy="<?php echo esc_attr($top); ?>" rx="<?php echo esc_attr($rim['rx']); ?>" ry="<?php echo esc_attr($rim['ry']); ?>" fill="<?php echo esc_attr($tone['lip']); ?>" />
    <path d="<?php echo esc_attr($mockup->opening); ?>" fill="<?php echo esc_attr($tone['inside']); ?>" />
    <path d="<?php echo esc_attr($mockup->opening); ?>" fill="url(#<?php echo esc_attr($uid); ?>-inside)" />
    <path d="<?php echo esc_attr($mockup->opening); ?>" fill="url(#<?php echo esc_attr($uid); ?>-deep)" />

    <?php if ($enamel) : ?>
        <?php // The rolled rim: a dark bead round the top, catching a line of light ?>
        <ellipse cx="<?php echo esc_attr($rim['cx']); ?>" cy="<?php echo esc_attr($top); ?>" rx="<?php echo esc_attr($rim['rx']); ?>" ry="<?php echo esc_attr($rim['ry']); ?>" fill="none" stroke="<?php echo esc_attr($tone['rim']); ?>" stroke-width="2" />
        <path d="M<?php echo esc_attr(round($rim['cx'] - $rim['rx'] + 0.4, 2)); ?> <?php echo esc_attr(round($top - 0.5, 2)); ?>A<?php echo esc_attr($rim['rx']); ?> <?php echo esc_attr($rim['ry']); ?> 0 0 1 <?php echo esc_attr(round($rim['cx'] - 0.2 * $rim['rx'], 2)); ?> <?php echo esc_attr(round($top - $rim['ry'] - 0.4, 2)); ?>" fill="none" stroke="#fff" stroke-opacity="0.45" stroke-width="0.5" stroke-linecap="round" />
    <?php else : ?>
        <?php // The glaze catches the light along the near edge of the rim ?>
        <path d="M<?php echo esc_attr(round($rim['cx'] - $rim['rx'] + 0.6, 2)); ?> <?php echo esc_attr(round($top + 0.4, 2)); ?>A<?php echo esc_attr($rim['rx']); ?> <?php echo esc_attr($rim['ry']); ?> 0 0 0 <?php echo esc_attr(round($rim['cx'] + 0.1 * $rim['rx'], 2)); ?> <?php echo esc_attr(round($top + $rim['ry'] - 0.1, 2)); ?>" fill="none" stroke="#fff" stroke-opacity="<?php echo esc_attr($black ? '0.22' : '0.9'); ?>" stroke-width="0.45" stroke-linecap="round" />
        <ellipse cx="<?php echo esc_attr($rim['cx']); ?>" cy="<?php echo esc_attr($top); ?>" rx="<?php echo esc_attr($rim['rx']); ?>" ry="<?php echo esc_attr($rim['ry']); ?>" fill="none" stroke="<?php echo esc_attr($tone['edge']); ?>" stroke-opacity="0.6" stroke-width="0.3" />
    <?php endif; ?>

    <path d="<?php echo esc_attr($mockup->wall); ?>" fill="none" stroke="#0a0a0a" stroke-opacity="0.12" stroke-width="0.35" stroke-linejoin="round" />
</svg>
