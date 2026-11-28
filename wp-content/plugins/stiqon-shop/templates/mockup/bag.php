<?php
/**
 * A paper carrier bag as inline SVG. See Stiqon\Shop\Mockup\Bag for the sizes
 * and how the bag is laid out.
 *
 * @var \Stiqon\Shop\Mockup\Bag $mockup
 * @var string                    $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Bag;
use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$kraft = $mockup->color === 'kraft';
$x = $mockup->panel['x'];
$y = $mockup->panel['y'];
$w = $mockup->panel['width'];
$h = $mockup->panel['height'];
[$dx, $dy] = $mockup->recede;
$cord = Bag::SIZES[$mockup->size]['cord'];
$patch = $mockup->patch;

// Light falls from the upper left: the front is lightest, the gusset's far half darkest.
$tone = $kraft
    ? ['front' => '#c9a57c', 'near' => '#b48f65', 'far' => '#a58056', 'inside' => '#7a5a39', 'band' => '#b28d63', 'cord' => '#b48d60', 'twist' => '#7d5d3a', 'shine' => '#e3caa5']
    : ['front' => '#f8f7f4', 'near' => '#e8e5df', 'far' => '#dcd8d0', 'inside' => '#bdb8ae', 'band' => '#e9e6e0', 'cord' => '#f3f1ec', 'twist' => '#bfb9ad', 'shine' => '#ffffff'];

// The paper is turned in along the top. Inside the back it shows as a band,
// lighter than the depth below; on the front, where it ends, as a faint line.
$back = $y + $dy;
$turn = 3.8;
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="bag"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <?php // The inside darkens away from the opening ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-inside" x1="0" y1="<?php echo esc_attr($back); ?>" x2="0" y2="<?php echo esc_attr($y + 2); ?>" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="<?php echo esc_attr($tone['inside']); ?>" />
            <stop offset="1" stop-color="#000" stop-opacity="<?php echo esc_attr($kraft ? '0.7' : '0.32'); ?>" />
        </linearGradient>

        <?php // Light from the left across the front, a little shade towards the floor ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-light" x1="0" y1="0" x2="1" y2="0.35">
            <stop offset="0" stop-color="#fff" stop-opacity="0.16" />
            <stop offset="0.45" stop-color="#fff" stop-opacity="0" />
            <stop offset="1" stop-color="#000" stop-opacity="0.05" />
        </linearGradient>
        <linearGradient id="<?php echo esc_attr($uid); ?>-floor" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0.55" stop-color="#000" stop-opacity="0" />
            <stop offset="1" stop-color="#000" stop-opacity="<?php echo esc_attr($kraft ? '0.09' : '0.05'); ?>" />
        </linearGradient>

        <?php if ($kraft) : ?>
            <?php // Short fibres in the kraft paper, as on a kraft sticker ?>
            <pattern id="<?php echo esc_attr($uid); ?>-fibre" width="14" height="14" patternUnits="userSpaceOnUse" patternTransform="rotate(18)">
                <path d="M1 3h3M9 9h4M4 12h2" stroke="#7a5a37" stroke-width="0.3" stroke-opacity="0.16" stroke-linecap="round" />
                <path d="M9 2h2M0 7h3M6 5h1.5" stroke="#f0d9ba" stroke-width="0.3" stroke-opacity="0.24" stroke-linecap="round" />
            </pattern>
        <?php endif; ?>
    </defs>

    <?php // One piece of paper under the parts, so no light shows between them ?>
    <path d="<?php echo esc_attr($mockup->outline); ?>" fill="<?php echo esc_attr($tone['near']); ?>" />

    <?php // The opening: the inside of the back, its turned-in top edge, the back handle glued under a strip of paper ?>
    <path d="<?php echo esc_attr($mockup->opening); ?>" fill="<?php echo esc_attr($tone['inside']); ?>" />
    <path d="<?php echo esc_attr($mockup->opening); ?>" fill="url(#<?php echo esc_attr($uid); ?>-inside)" />
    <rect x="<?php echo esc_attr($x + $dx); ?>" y="<?php echo esc_attr($back); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($turn); ?>" fill="<?php echo esc_attr($tone['band']); ?>" />

    <?php foreach ($mockup->handles as $index => $handle) : ?>
        <?php // Twisted paper cord: a dark edge, the paper, the turns of the twist and their light side ?>
        <path d="<?php echo esc_attr($handle); ?>" fill="none" stroke="#000" stroke-opacity="0.22" stroke-width="<?php echo esc_attr($cord + 0.5); ?>" stroke-linecap="round" />
        <path d="<?php echo esc_attr($handle); ?>" fill="none" stroke="<?php echo esc_attr($tone['cord']); ?>" stroke-width="<?php echo esc_attr($cord); ?>" stroke-linecap="round" />
        <path d="<?php echo esc_attr($handle); ?>" fill="none" stroke="<?php echo esc_attr($tone['twist']); ?>" stroke-opacity="<?php echo esc_attr($kraft ? '0.45' : '0.35'); ?>" stroke-width="<?php echo esc_attr($cord); ?>" stroke-dasharray="0.4 0.6" />
        <path d="<?php echo esc_attr($handle); ?>" fill="none" stroke="<?php echo esc_attr($tone['shine']); ?>" stroke-opacity="0.6" stroke-width="<?php echo esc_attr(round($cord * 0.3, 2)); ?>" stroke-dasharray="0.4 0.6" stroke-dashoffset="0.5" />

        <?php if ($index === 0) : ?>
            <rect x="<?php echo esc_attr($patch['x']); ?>" y="<?php echo esc_attr($patch['y']); ?>" width="<?php echo esc_attr($patch['width']); ?>" height="<?php echo esc_attr($patch['height']); ?>" fill="<?php echo esc_attr($tone['band']); ?>" stroke="#000" stroke-opacity="0.12" stroke-width="0.25" />
        <?php endif; ?>
    <?php endforeach; ?>

    <?php // The gusset, folded inwards along its middle ?>
    <path d="<?php echo esc_attr($mockup->gusset[0]); ?>" fill="<?php echo esc_attr($tone['near']); ?>" />
    <path d="<?php echo esc_attr($mockup->gusset[1]); ?>" fill="<?php echo esc_attr($tone['far']); ?>" />
    <?php if ($kraft) : ?>
        <path d="<?php echo esc_attr($mockup->gusset[0].$mockup->gusset[1]); ?>" fill="url(#<?php echo esc_attr($uid); ?>-fibre)" />
    <?php endif; ?>
    <path d="<?php echo esc_attr($mockup->gusset[0].$mockup->gusset[1]); ?>" fill="url(#<?php echo esc_attr($uid); ?>-floor)" />

    <?php // The front, with the logo printed on it ?>
    <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" fill="<?php echo esc_attr($tone['front']); ?>" />

    <?php
    Template::output('mockup/parts/motif', [
        'uid' => $uid,
        'box' => $mockup->printBox,
        'motif' => 'motif/'.$mockup->motif,
        'viewBox' => '0 0 100 100',
        'fit' => 'meet',
        'vars' => $mockup->motifVars(),
        'backing' => null,
    ]);
    ?>

    <?php // Over the print as well: the paper's fibres, the light and the creases ?>
    <g pointer-events="none">
        <?php if ($kraft) : ?>
            <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" fill="url(#<?php echo esc_attr($uid); ?>-fibre)" />
        <?php endif; ?>
        <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" fill="url(#<?php echo esc_attr($uid); ?>-light)" />
        <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" fill="url(#<?php echo esc_attr($uid); ?>-floor)" />
        <path d="<?php echo esc_attr($mockup->creases); ?>" fill="none" stroke="#000" stroke-opacity="0.08" stroke-width="0.35" />
        <path d="M<?php echo esc_attr($x); ?> <?php echo esc_attr($y + $turn); ?>h<?php echo esc_attr($w); ?>" stroke="#000" stroke-opacity="0.06" stroke-width="0.3" />
    </g>

    <?php // Folded edges catch the light ?>
    <path d="M<?php echo esc_attr($x); ?> <?php echo esc_attr($y + $h); ?>V<?php echo esc_attr($y); ?>h<?php echo esc_attr($w); ?>" fill="none" stroke="#fff" stroke-opacity="<?php echo esc_attr($kraft ? '0.4' : '0.9'); ?>" stroke-width="0.45" />
    <path d="<?php echo esc_attr($mockup->outline); ?>" fill="none" stroke="#0a0a0a" stroke-opacity="0.12" stroke-width="0.4" stroke-linejoin="round" />
</svg>
