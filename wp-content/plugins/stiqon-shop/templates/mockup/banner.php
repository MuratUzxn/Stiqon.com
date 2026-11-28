<?php
/**
 * A PVC banner as inline SVG. See Stiqon\Shop\Mockup\Banner for the formats.
 *
 * The banner is pulled taut at its corners only, so its edges are drawn in a
 * little between them, and creases run from the corners into the material.
 *
 * @var \Stiqon\Shop\Mockup\Banner $mockup
 * @var string                     $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
['x' => $x, 'y' => $y, 'width' => $w, 'height' => $h] = $mockup->sheet;
$outline = $mockup->outline();
$cx = round($x + $w / 2, 2);
$cy = round($y + $h / 2, 2);

// Ropes fade out on their way to the frame: fully there at the eyelets, gone where they leave the picture.
[[$from, $to]] = $mockup->ropes;
$eyelet = hypot($from[0] - $cx, $from[1] - $cy);
$reach = hypot($to[0] - $cx, $to[1] - $cy);

// A crease runs from the top left corner towards the middle; the other corners mirror it.
$toward = [$cx - $from[0], $cy - $from[1]];
$length = hypot(...$toward);
$along = [$toward[0] / $length, $toward[1] / $length];
$across = [-$along[1], $along[0]];
$crease = static function (float $offset, float $share) use ($from, $along, $across, $length): string {
    $start = [$from[0] + 1.2 * $along[0] + $offset * $across[0], $from[1] + 1.2 * $along[1] + $offset * $across[1]];
    $end = [$start[0] + $share * $length * $along[0] + 0.8 * $offset * $across[0], $start[1] + $share * $length * $along[1] + 0.8 * $offset * $across[1]];

    return 'M'.round($start[0] - 0.35 * $across[0], 2).' '.round($start[1] - 0.35 * $across[1], 2)
        .'L'.round($end[0], 2).' '.round($end[1], 2)
        .'L'.round($start[0] + 0.35 * $across[0], 2).' '.round($start[1] + 0.35 * $across[1], 2).'z';
};
$mirrors = ['', 'matrix(-1 0 0 1 '.(2 * $cx).' 0)', 'matrix(1 0 0 -1 0 '.(2 * $cy).')', 'matrix(-1 0 0 -1 '.(2 * $cx).' '.(2 * $cy).')'];
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="banner"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <clipPath id="<?php echo esc_attr($uid); ?>-print">
            <path d="<?php echo esc_attr($outline); ?>" />
        </clipPath>

        <?php // Taut at the corners, the material sags a little between them: lighter in the middle, darker along the edges. ?>
        <radialGradient id="<?php echo esc_attr($uid); ?>-tension" cx="0.45" cy="0.42" r="0.62">
            <stop offset="0" stop-color="#fff" stop-opacity="0.12" />
            <stop offset="0.55" stop-color="#fff" stop-opacity="0" />
            <stop offset="1" stop-color="#000" stop-opacity="0.12" />
        </radialGradient>

        <linearGradient id="<?php echo esc_attr($uid); ?>-crease-light" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#fff" stop-opacity="0.45" />
            <stop offset="1" stop-color="#fff" stop-opacity="0" />
        </linearGradient>
        <linearGradient id="<?php echo esc_attr($uid); ?>-crease-dark" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#000" stop-opacity="0.22" />
            <stop offset="1" stop-color="#000" stop-opacity="0" />
        </linearGradient>

        <radialGradient id="<?php echo esc_attr($uid); ?>-eyelet" cx="0.38" cy="0.32" r="0.8">
            <stop offset="0" stop-color="#ffffff" />
            <stop offset="0.5" stop-color="#cfd3d8" />
            <stop offset="1" stop-color="#8b9199" />
        </radialGradient>

        <radialGradient id="<?php echo esc_attr($uid); ?>-reach" gradientUnits="userSpaceOnUse" cx="<?php echo esc_attr($cx); ?>" cy="<?php echo esc_attr($cy); ?>" r="<?php echo esc_attr(round($reach, 2)); ?>">
            <stop offset="<?php echo esc_attr(round(($eyelet + 2) / $reach, 3)); ?>" stop-color="#fff" />
            <stop offset="1" stop-color="#000" />
        </radialGradient>
        <mask id="<?php echo esc_attr($uid); ?>-ropes">
            <rect width="120" height="120" fill="url(#<?php echo esc_attr($uid); ?>-reach)" />
        </mask>
    </defs>

    <g transform="rotate(<?php echo esc_attr($mockup->tilt); ?> <?php echo esc_attr($cx); ?> <?php echo esc_attr($cy); ?>)">
        <?php // White PVC under the print ?>
        <path d="<?php echo esc_attr($outline); ?>" fill="#ffffff" />

        <?php
        Template::output('mockup/parts/motif', [
            'uid' => $uid,
            'box' => $mockup->sheet,
            'motif' => 'motif/banner/'.$mockup->motif,
            'viewBox' => '0 0 300 100',
            'fit' => 'slice',
            'backing' => '#fff',
            'attributes' => 'clip-path="url(#'.esc_attr($uid).'-print)"',
        ]);
        ?>

        <g clip-path="url(#<?php echo esc_attr($uid); ?>-print)" pointer-events="none">
            <path d="<?php echo esc_attr($outline); ?>" fill="url(#<?php echo esc_attr($uid); ?>-tension)" />

            <?php // Creases from the corners, where the ropes pull ?>
            <?php foreach ($mirrors as $mirror) : ?>
                <g<?php echo $mirror !== '' ? ' transform="'.esc_attr($mirror).'"' : ''; ?>>
                    <path d="<?php echo esc_attr($crease(-0.9, 0.34)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-crease-light)" />
                    <path d="<?php echo esc_attr($crease(0.5, 0.26)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-crease-dark)" />
                    <path d="<?php echo esc_attr($crease(1.8, 0.18)); ?>" fill="url(#<?php echo esc_attr($uid); ?>-crease-light)" />
                </g>
            <?php endforeach; ?>

            <?php // The hem: folded back and welded, a little stiffer than the rest ?>
            <path d="<?php echo esc_attr($outline); ?>" fill="none" stroke="#fff" stroke-opacity="0.12" stroke-width="7" />
            <path d="<?php echo esc_attr($mockup->outline(3.5)); ?>" fill="none" stroke="#000" stroke-opacity="0.16" stroke-width="0.3" />
            <path d="<?php echo esc_attr($mockup->outline(3.85)); ?>" fill="none" stroke="#fff" stroke-opacity="0.3" stroke-width="0.3" />
        </g>
        <path d="<?php echo esc_attr($outline); ?>" fill="none" stroke="#000" stroke-opacity="0.14" stroke-width="0.35" />

        <?php // Eyelets ?>
        <?php foreach ($mockup->eyelets as [$ex, $ey]) : ?>
            <circle cx="<?php echo esc_attr($ex); ?>" cy="<?php echo esc_attr($ey); ?>" r="1.15" fill="url(#<?php echo esc_attr($uid); ?>-eyelet)" stroke="#6b7179" stroke-opacity="0.8" stroke-width="0.14" />
            <circle cx="<?php echo esc_attr($ex); ?>" cy="<?php echo esc_attr($ey); ?>" r="0.5" fill="#4a4e54" />
        <?php endforeach; ?>

        <?php // Ropes through the corner eyelets, out to the frame ?>
        <g mask="url(#<?php echo esc_attr($uid); ?>-ropes)" fill="none" stroke-linecap="round">
            <?php foreach ($mockup->ropes as [$start, $end]) : ?>
                <?php $line = 'M'.$start[0].' '.$start[1].'L'.$end[0].' '.$end[1]; ?>
                <path d="<?php echo esc_attr($line); ?>" stroke="#8f877a" stroke-width="1.2" />
                <path d="<?php echo esc_attr($line); ?>" stroke="#f3eee4" stroke-width="0.8" />
                <path d="<?php echo esc_attr($line); ?>" stroke="#bdb3a2" stroke-width="0.8" stroke-dasharray="0.45 0.55" />
            <?php endforeach; ?>
        </g>
    </g>
</svg>
