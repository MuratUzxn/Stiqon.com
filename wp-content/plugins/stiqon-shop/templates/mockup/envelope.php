<?php
/**
 * An envelope as inline SVG. See Stiqon\Shop\Mockup\Envelope for the formats.
 *
 * @var \Stiqon\Shop\Mockup\Envelope $mockup
 * @var string                        $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$x = $mockup->sheet['x'];
$y = $mockup->sheet['y'];
$w = $mockup->sheet['width'];
$h = $mockup->sheet['height'];
$logo = $mockup->logo();
$window = $mockup->windowBox();

// Next to the logo, two short lines: the sender's name and address.
$senderX = round($logo['x'] + $logo['width'] + 2.4, 2);
$senderY = round($logo['y'] + $logo['height'] / 2 - 2.2, 2);
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="envelope"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <?php // Light from the top left: the paper is a shade warmer towards the far corner. ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-paper" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#ffffff" />
            <stop offset="1" stop-color="#f3f2ee" />
        </linearGradient>

        <?php if ($window) : ?>
            <?php // Window film: milky, with a soft reflection across it. ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-film" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#ffffff" stop-opacity="0.55" />
                <stop offset="0.34" stop-color="#ffffff" stop-opacity="0.18" />
                <stop offset="0.5" stop-color="#ffffff" stop-opacity="0.5" />
                <stop offset="0.62" stop-color="#ffffff" stop-opacity="0.12" />
                <stop offset="1" stop-color="#ffffff" stop-opacity="0.3" />
            </linearGradient>
        <?php endif; ?>
    </defs>

    <g transform="rotate(<?php echo esc_attr($mockup->tilt); ?> 60 60) translate(<?php echo esc_attr($x); ?> <?php echo esc_attr($y); ?>)">
        <?php // Two layers of paper give the edge a little depth ?>
        <rect x="0.45" y="0.6" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.6" fill="#dddcd6" />
        <rect width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.6" fill="url(#<?php echo esc_attr($uid); ?>-paper)" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" />

        <?php // The flap on the back shows faintly through the paper ?>
        <path d="<?php echo esc_attr($mockup->flap()); ?>" fill="#0a0a0a" fill-opacity="0.025" stroke="#0a0a0a" stroke-opacity="0.06" stroke-width="0.35" stroke-linejoin="round" />

        <?php // The sender's logo, printed: the sample motif, replaced by the visitor's own motif once chosen ?>
        <?php
        Template::output('mockup/parts/motif', [
            'uid' => $uid,
            'box' => $logo,
            'motif' => 'motif/'.$mockup->motif,
            'viewBox' => '0 0 100 100',
            'fit' => 'meet',
            'vars' => $mockup->motifVars(),
            'backing' => null,
        ]);
        ?>
        <g fill="#1d1d1b">
            <rect x="<?php echo esc_attr($senderX); ?>" y="<?php echo esc_attr($senderY); ?>" width="<?php echo esc_attr(round($w * 0.17, 2)); ?>" height="1.3" rx="0.65" fill-opacity="0.5" />
            <rect x="<?php echo esc_attr($senderX); ?>" y="<?php echo esc_attr(round($senderY + 2.9, 2)); ?>" width="<?php echo esc_attr(round($w * 0.12, 2)); ?>" height="1" rx="0.5" fill-opacity="0.28" />
        </g>

        <?php // The address: on the letter behind the window, or printed on the front ?>
        <g fill="#1d1d1b">
            <?php foreach ($mockup->address() as $index => [$lineX, $lineY, $length]) : ?>
                <rect x="<?php echo esc_attr($lineX); ?>" y="<?php echo esc_attr($lineY); ?>" width="<?php echo esc_attr($length); ?>" height="1.5" rx="0.75" fill-opacity="<?php echo esc_attr($index === 0 ? '0.55' : '0.38'); ?>" />
            <?php endforeach; ?>
        </g>

        <?php if ($window) : ?>
            <?php // The window: a sheet of milky film over the letter inside ?>
            <rect x="<?php echo esc_attr($window['x']); ?>" y="<?php echo esc_attr($window['y']); ?>" width="<?php echo esc_attr($window['width']); ?>" height="<?php echo esc_attr($window['height']); ?>" rx="1.2" fill="#dfe6ea" fill-opacity="0.55" />
            <rect x="<?php echo esc_attr($window['x']); ?>" y="<?php echo esc_attr($window['y']); ?>" width="<?php echo esc_attr($window['width']); ?>" height="<?php echo esc_attr($window['height']); ?>" rx="1.2" fill="url(#<?php echo esc_attr($uid); ?>-film)" stroke="#0a0a0a" stroke-opacity="0.12" stroke-width="0.3" />
        <?php endif; ?>
    </g>
</svg>
