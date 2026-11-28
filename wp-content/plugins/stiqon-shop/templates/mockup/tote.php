<?php
/**
 * A cotton tote as inline SVG. See Stiqon\Shop\Mockup\Tote for the handles and
 * how the bag is laid out.
 *
 * @var \Stiqon\Shop\Mockup\Tote $mockup
 * @var string                     $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Mockup\Tote;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$x = $mockup->body['x'];
$y = $mockup->body['y'];
$w = $mockup->body['width'];
$h = $mockup->body['height'];
$right = $x + $w;
$hem = $mockup->hem;
$strap = $mockup->strap;
$natural = $mockup->color === 'natural';

// cloth: the fabric; edge: where it turns under; thread: the seams; fleck: bits
// of seed in unbleached cotton; weave: the threads' texture; fold: shade in the
// folds, with how dark their valleys and how light their ridges are – white
// cloth can only show the shade, black cloth mostly the light.
$tone = [
    'natural' => ['cloth' => '#efe7d6', 'edge' => '#cbbd9f', 'thread' => '#a99878', 'fleck' => '#6f5d3d', 'weave' => '#7d6c4a', 'fold' => '#4f3f22', 'valley' => 0.1, 'ridge' => 0.13],
    'black' => ['cloth' => '#292927', 'edge' => '#101010', 'thread' => '#545450', 'fleck' => '#77776f', 'weave' => '#ffffff', 'fold' => '#000000', 'valley' => 0.14, 'ridge' => 0.06],
    'white' => ['cloth' => '#fbfbf9', 'edge' => '#d9d7d0', 'thread' => '#c4c0b6', 'fleck' => '#c9c5bb', 'weave' => '#8c877b', 'fold' => '#3f3b33', 'valley' => 0.08, 'ridge' => 0.0],
][$mockup->color];

// Soft folds where the cloth was smoothed out by hand: centre, length, breadth
// and angle, as shares of the bag. The straps pull two drag lines down from
// where they are sewn in; lower down the cloth lies in broad, shallow waves.
$folds = [
    [0.33, 0.22, 0.34, 0.06, 64],
    [0.69, 0.23, 0.32, 0.06, -62],
    [0.58, 0.56, 0.8, 0.16, 30],
    [0.45, 0.86, 0.9, 0.12, -5],
];
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="tote"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <clipPath id="<?php echo esc_attr($uid); ?>-cloth">
            <path d="<?php echo esc_attr($mockup->outline); ?>" />
        </clipPath>

        <?php // Plain weave, and the flecks unbleached cotton keeps ?>
        <pattern id="<?php echo esc_attr($uid); ?>-weave" width="1.2" height="1.2" patternUnits="userSpaceOnUse">
            <path d="M0 0.3h1.2M0.9 0v1.2" stroke="<?php echo esc_attr($tone['weave']); ?>" stroke-opacity="0.035" stroke-width="0.3" />
        </pattern>
        <?php if ($natural) : ?>
            <pattern id="<?php echo esc_attr($uid); ?>-flecks" width="11" height="9" patternUnits="userSpaceOnUse" patternTransform="rotate(23)">
                <circle cx="2.1" cy="2.8" r="0.17" fill="<?php echo esc_attr($tone['fleck']); ?>" fill-opacity="0.45" />
                <circle cx="8.3" cy="6.9" r="0.13" fill="<?php echo esc_attr($tone['fleck']); ?>" fill-opacity="0.4" />
                <path d="M5.6 4.8l0.9 0.35M9.4 1.6l0.6-0.3" stroke="<?php echo esc_attr($tone['fleck']); ?>" stroke-opacity="0.3" stroke-width="0.14" stroke-linecap="round" />
            </pattern>
        <?php endif; ?>

        <radialGradient id="<?php echo esc_attr($uid); ?>-valley">
            <stop offset="0" stop-color="<?php echo esc_attr($tone['fold']); ?>" stop-opacity="<?php echo esc_attr($tone['valley']); ?>" />
            <stop offset="1" stop-color="<?php echo esc_attr($tone['fold']); ?>" stop-opacity="0" />
        </radialGradient>
        <?php if ($tone['ridge'] > 0) : ?>
            <radialGradient id="<?php echo esc_attr($uid); ?>-ridge">
                <stop offset="0" stop-color="#fff" stop-opacity="<?php echo esc_attr($tone['ridge']); ?>" />
                <stop offset="1" stop-color="#fff" stop-opacity="0" />
            </radialGradient>
        <?php endif; ?>

        <?php // The cloth curls a little at its seams and lies in shade there ?>
        <radialGradient id="<?php echo esc_attr($uid); ?>-seams" cx="0.5" cy="0.5" r="0.72">
            <stop offset="0.6" stop-color="<?php echo esc_attr($tone['fold']); ?>" stop-opacity="0" />
            <stop offset="1" stop-color="<?php echo esc_attr($tone['fold']); ?>" stop-opacity="0.08" />
        </radialGradient>
    </defs>

    <g transform="rotate(<?php echo esc_attr(Tote::TILT); ?> 60 60)">
        <?php // The straps, the back one first; their ends are sewn in under the hem. Each a band of cloth stitched along both edges. ?>
        <?php foreach ($mockup->straps as $index => $loop) : ?>
            <?php if ($index === 1) : ?>
                <?php // The front strap lies on the back one and casts a little shadow ?>
                <path d="<?php echo esc_attr($loop); ?>" fill="none" stroke="#000" stroke-opacity="0.1" stroke-width="<?php echo esc_attr($strap + 0.6); ?>" transform="translate(0.35 0.55)" />
            <?php endif; ?>
            <path d="<?php echo esc_attr($loop); ?>" fill="none" stroke="<?php echo esc_attr($tone['edge']); ?>" stroke-width="<?php echo esc_attr($strap + 0.45); ?>" />
            <path d="<?php echo esc_attr($loop); ?>" fill="none" stroke="<?php echo esc_attr($tone['cloth']); ?>" stroke-width="<?php echo esc_attr($strap); ?>" />
            <path d="<?php echo esc_attr($loop); ?>" fill="none" stroke="<?php echo esc_attr($tone['thread']); ?>" stroke-width="<?php echo esc_attr($strap - 0.9); ?>" stroke-dasharray="0.9 0.55" />
            <path d="<?php echo esc_attr($loop); ?>" fill="none" stroke="<?php echo esc_attr($tone['cloth']); ?>" stroke-width="<?php echo esc_attr($strap - 1.5); ?>" />
            <path d="<?php echo esc_attr($loop); ?>" fill="none" stroke="url(#<?php echo esc_attr($uid); ?>-weave)" stroke-width="<?php echo esc_attr($strap); ?>" />
        <?php endforeach; ?>

        <?php // The bag ?>
        <path d="<?php echo esc_attr($mockup->outline); ?>" fill="<?php echo esc_attr($tone['cloth']); ?>" />

        <?php
        // The samples keep their own background on every fabric, as a print with
        // an underbase: drawn bare, most are inked dark for kraft and film, which
        // black cotton would swallow, and "stern" is inked white, which natural
        // and white cotton would. Screen-printing ink lets a little of the weave through.
        Template::output('mockup/parts/motif', [
            'uid' => $uid,
            'box' => $mockup->printBox,
            'motif' => 'motif/'.$mockup->motif,
            'viewBox' => '0 0 100 100',
            'fit' => 'meet',
            'vars' => ['bare' => false],
            'backing' => null,
            'attributes' => 'opacity="0.94"',
        ]);
        ?>

        <?php // Over the print as well: the weave, the flecks and the folds of the cloth ?>
        <g clip-path="url(#<?php echo esc_attr($uid); ?>-cloth)" pointer-events="none">
            <rect x="<?php echo esc_attr($x - 2); ?>" y="<?php echo esc_attr($y - 2); ?>" width="<?php echo esc_attr($w + 4); ?>" height="<?php echo esc_attr($h + 4); ?>" fill="url(#<?php echo esc_attr($uid); ?>-weave)" />
            <?php if ($natural) : ?>
                <rect x="<?php echo esc_attr($x - 2); ?>" y="<?php echo esc_attr($y - 2); ?>" width="<?php echo esc_attr($w + 4); ?>" height="<?php echo esc_attr($h + 4); ?>" fill="url(#<?php echo esc_attr($uid); ?>-flecks)" />
            <?php endif; ?>
            <?php foreach ($folds as [$cx, $cy, $length, $breadth, $angle]) : ?>
                <g transform="translate(<?php echo esc_attr(round($x + $cx * $w, 2)); ?> <?php echo esc_attr(round($y + $cy * $h, 2)); ?>) rotate(<?php echo esc_attr($angle); ?>)">
                    <?php if ($tone['ridge'] > 0) : ?>
                        <ellipse cy="<?php echo esc_attr(round(-$breadth * $h * 0.5, 2)); ?>" rx="<?php echo esc_attr(round($length * $w / 2, 2)); ?>" ry="<?php echo esc_attr(round($breadth * $h / 2, 2)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-ridge)" />
                    <?php endif; ?>
                    <ellipse rx="<?php echo esc_attr(round($length * $w / 2, 2)); ?>" ry="<?php echo esc_attr(round($breadth * $h / 2, 2)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-valley)" />
                </g>
            <?php endforeach; ?>
            <rect x="<?php echo esc_attr($x - 2); ?>" y="<?php echo esc_attr($y - 2); ?>" width="<?php echo esc_attr($w + 4); ?>" height="<?php echo esc_attr($h + 4); ?>" fill="url(#<?php echo esc_attr($uid); ?>-seams)" />
        </g>

        <?php // The hem's seam, and where the straps are sewn through it ?>
        <path d="M<?php echo esc_attr($x + 1.2); ?> <?php echo esc_attr($y + $hem); ?>L<?php echo esc_attr($right - 1.2); ?> <?php echo esc_attr($y + $hem + 0.25); ?>" stroke="<?php echo esc_attr($tone['thread']); ?>" stroke-width="0.3" stroke-dasharray="0.9 0.55" />
        <?php foreach ($mockup->feet as $foot) : ?>
            <rect x="<?php echo esc_attr($foot - $strap / 2 + 0.4); ?>" y="<?php echo esc_attr($y + 0.8); ?>" width="<?php echo esc_attr($strap - 0.8); ?>" height="<?php echo esc_attr($hem - 1.4); ?>" fill="none" stroke="<?php echo esc_attr($tone['thread']); ?>" stroke-width="0.28" stroke-dasharray="0.7 0.5" />
        <?php endforeach; ?>

        <path d="<?php echo esc_attr($mockup->outline); ?>" fill="none" stroke="<?php echo esc_attr($tone['edge']); ?>" stroke-width="0.5" />
        <path d="<?php echo esc_attr($mockup->outline); ?>" fill="none" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.35" />
    </g>
</svg>
