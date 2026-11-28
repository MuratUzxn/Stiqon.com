<?php
/**
 * A shipping box as inline SVG. See Stiqon\Shop\Mockup\Box for the sizes and
 * how each face is put in place.
 *
 * @var \Stiqon\Shop\Mockup\Box $mockup
 * @var string                    $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Box;
use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$kraft = $mockup->color === 'kraft';
$length = $mockup->length;
$depth = $mockup->depth;
$height = $mockup->height;
$corner = $mockup->corners;

// Light falls from the upper left: the lid is lightest, the side turned away
// darkest. Brown board is closed with brown packing tape, white board with a
// pale paper tape.
$tone = $kraft
    ? ['top' => '#d6b68d', 'front' => '#c9a57c', 'side' => '#ad895f', 'tape' => '#a8804f', 'seam' => '#5e4429']
    : ['top' => '#fcfbf8', 'front' => '#f1efea', 'side' => '#dcd8cf', 'tape' => '#dcc9a6', 'seam' => '#7d786d'];

// The tape covers the seam where the lid's two flaps meet and runs a little
// way down the side, where the dispenser's teeth have cut it.
$tapeTop = round($depth / 2 - Box::TAPE / 2, 2);
$tapeBottom = round($depth / 2 + Box::TAPE / 2, 2);
$drop = round(min(0.3 * $height, 11.0), 2);
$teeth = 10;
$cut = '';

for ($tooth = $teeth; $tooth >= 0; $tooth--) {
    $cut .= sprintf('L%s %s', round($tapeTop + Box::TAPE * $tooth / $teeth, 2), round($drop + ($tooth % 2 === 0 ? 0.0 : 0.7), 2));
}

$point = static fn (string $name): string => $corner[$name][0].' '.$corner[$name][1];
$outline = 'M'.$point('left-bottom').'L'.$point('left-top').'L'.$point('far-top').'L'.$point('back-top').'L'.$point('back-bottom').'L'.$point('near-bottom').'z';
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="box"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <?php // Each face darkens a little towards the floor ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-floor" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0.3" stop-color="#000" stop-opacity="0" />
            <stop offset="1" stop-color="#000" stop-opacity="<?php echo esc_attr($kraft ? '0.1' : '0.06'); ?>" />
        </linearGradient>

        <?php // The lid catches the light from the back left ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-lid" x1="0" y1="1" x2="1" y2="0">
            <stop offset="0" stop-color="#fff" stop-opacity="0.22" />
            <stop offset="0.7" stop-color="#fff" stop-opacity="0" />
        </linearGradient>

        <?php // Packing tape shines along its length ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-sheen" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#fff" stop-opacity="0.05" />
            <stop offset="0.45" stop-color="#fff" stop-opacity="0.32" />
            <stop offset="1" stop-color="#fff" stop-opacity="0.08" />
        </linearGradient>

        <?php if ($kraft) : ?>
            <?php // Short fibres in the brown board, as on a kraft sticker; drawn in each face's own coordinates, they lie in its plane ?>
            <pattern id="<?php echo esc_attr($uid); ?>-fibre" width="14" height="14" patternUnits="userSpaceOnUse" patternTransform="rotate(18)">
                <path d="M1 3h3M9 9h4M4 12h2" stroke="#7a5a37" stroke-width="0.3" stroke-opacity="0.16" stroke-linecap="round" />
                <path d="M9 2h2M0 7h3M6 5h1.5" stroke="#f0d9ba" stroke-width="0.3" stroke-opacity="0.24" stroke-linecap="round" />
            </pattern>
        <?php endif; ?>
    </defs>

    <?php // One piece of cardboard under the faces, so no light shows between them ?>
    <path d="<?php echo esc_attr($outline); ?>" fill="<?php echo esc_attr($tone['side']); ?>" />

    <?php // The lid: two flaps meeting in the middle, taped shut ?>
    <g transform="<?php echo esc_attr($mockup->matrix('top')); ?>">
        <rect width="<?php echo esc_attr($length); ?>" height="<?php echo esc_attr($depth); ?>" fill="<?php echo esc_attr($tone['top']); ?>" />
        <rect width="<?php echo esc_attr($length); ?>" height="<?php echo esc_attr($depth); ?>" fill="url(#<?php echo esc_attr($uid); ?>-lid)" />
        <?php if ($kraft) : ?>
            <rect width="<?php echo esc_attr($length); ?>" height="<?php echo esc_attr($depth); ?>" fill="url(#<?php echo esc_attr($uid); ?>-fibre)" />
        <?php endif; ?>
        <path d="M0 <?php echo esc_attr($depth / 2); ?>H<?php echo esc_attr($length); ?>" stroke="<?php echo esc_attr($tone['seam']); ?>" stroke-opacity="0.55" stroke-width="0.45" />
        <rect y="<?php echo esc_attr($tapeTop); ?>" width="<?php echo esc_attr($length); ?>" height="<?php echo esc_attr(Box::TAPE); ?>" fill="<?php echo esc_attr($tone['tape']); ?>" fill-opacity="0.78" />
        <rect y="<?php echo esc_attr(round($tapeTop + Box::TAPE * 0.18, 2)); ?>" width="<?php echo esc_attr($length); ?>" height="<?php echo esc_attr(round(Box::TAPE * 0.3, 2)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-sheen)" />
        <path d="M0 <?php echo esc_attr($tapeTop); ?>H<?php echo esc_attr($length); ?>M0 <?php echo esc_attr($tapeBottom); ?>H<?php echo esc_attr($length); ?>" stroke="#000" stroke-opacity="0.12" stroke-width="0.35" />
    </g>

    <?php // The end of the box, turned away from the light ?>
    <g transform="<?php echo esc_attr($mockup->matrix('side')); ?>">
        <rect width="<?php echo esc_attr($depth); ?>" height="<?php echo esc_attr($height); ?>" fill="<?php echo esc_attr($tone['side']); ?>" />
        <?php if ($kraft) : ?>
            <rect width="<?php echo esc_attr($depth); ?>" height="<?php echo esc_attr($height); ?>" fill="url(#<?php echo esc_attr($uid); ?>-fibre)" />
        <?php endif; ?>
        <rect width="<?php echo esc_attr($depth); ?>" height="<?php echo esc_attr($height); ?>" fill="url(#<?php echo esc_attr($uid); ?>-floor)" />
        <path d="M<?php echo esc_attr($tapeTop); ?> 0H<?php echo esc_attr($tapeBottom); ?>V<?php echo esc_attr($drop); ?><?php echo esc_attr($cut); ?>z" fill="<?php echo esc_attr($tone['tape']); ?>" fill-opacity="0.72" stroke="#000" stroke-opacity="0.1" stroke-width="0.3" stroke-linejoin="round" />
    </g>

    <?php // The front, with the logo printed on it ?>
    <g transform="<?php echo esc_attr($mockup->matrix('front')); ?>">
        <rect width="<?php echo esc_attr($length); ?>" height="<?php echo esc_attr($height); ?>" fill="<?php echo esc_attr($tone['front']); ?>" />
    </g>

    <?php
    Template::output('mockup/parts/motif', [
        'uid' => $uid,
        'box' => $mockup->printBox,
        'motif' => 'motif/'.$mockup->motif,
        'viewBox' => '0 0 100 100',
        'fit' => 'meet',
        'vars' => $mockup->motifVars(),
        'backing' => null,
        'attributes' => 'transform="'.esc_attr($mockup->matrix('front')).'"',
    ]);
    ?>

    <?php // Over the print as well: the board's fibres and the shade near the floor ?>
    <g transform="<?php echo esc_attr($mockup->matrix('front')); ?>" pointer-events="none">
        <?php if ($kraft) : ?>
            <rect width="<?php echo esc_attr($length); ?>" height="<?php echo esc_attr($height); ?>" fill="url(#<?php echo esc_attr($uid); ?>-fibre)" />
        <?php endif; ?>
        <rect width="<?php echo esc_attr($length); ?>" height="<?php echo esc_attr($height); ?>" fill="url(#<?php echo esc_attr($uid); ?>-floor)" />
    </g>

    <?php // Folded edges catch the light ?>
    <path d="M<?php echo esc_attr($point('left-top')); ?>L<?php echo esc_attr($point('near-top')); ?>L<?php echo esc_attr($point('back-top')); ?>" fill="none" stroke="#fff" stroke-opacity="<?php echo esc_attr($kraft ? '0.4' : '0.9'); ?>" stroke-width="0.5" stroke-linejoin="round" />
    <path d="M<?php echo esc_attr($point('near-top')); ?>L<?php echo esc_attr($point('near-bottom')); ?>" stroke="#fff" stroke-opacity="<?php echo esc_attr($kraft ? '0.22' : '0.6'); ?>" stroke-width="0.45" />
    <path d="<?php echo esc_attr($outline); ?>" fill="none" stroke="#0a0a0a" stroke-opacity="0.12" stroke-width="0.4" stroke-linejoin="round" />
</svg>
