<?php
/**
 * A notepad as inline SVG. See Stiqon\Shop\Mockup\Notepad for the formats.
 *
 * The pad is a stack of sheets on grey board, glued at the top under a dark
 * strip. Its edge shows below and to the right of the top sheet; logo and
 * ruling are drawn in the top sheet's own 100 x 141.4 box.
 *
 * @var \Stiqon\Shop\Mockup\Notepad $mockup
 * @var string                        $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$ruling = $mockup->ruling;
$tilt = $mockup->tilt;
$x = $mockup->sheet['x'];
$y = $mockup->sheet['y'];
$w = $mockup->sheet['width'];
$h = $mockup->sheet['height'];
$dx = $mockup->depth['x'];
$dy = $mockup->depth['y'];
$cx = $x + ($w + $dx) / 2;
$cy = $y + ($h + $dy) / 2;

// Strip and ruling keep their size in millimetres; $mm turns millimetres into the sheet's units.
$mm = $mockup->unitsPerMillimetre();
$strip = 7 * $mm;
$logo = 25;
$header = $strip + 5 + $logo + 5;
$bottom = 141.4 - 6 * $mm;

// The ruling as one path: lines 8 mm apart or squares of 5 mm, within a
// margin of 6 mm. A blank sheet has none.
$margin = 6 * $mm;
$ruled = '';

if ($ruling === 'lined') {
    for ($line = $header + 8 * $mm; $line <= $bottom; $line += 8 * $mm) {
        $ruled .= 'M'.round($margin, 2).' '.round($line, 2).'H'.round(100 - $margin, 2);
    }
}

if ($ruling === 'grid') {
    $square = 5 * $mm;
    $columns = (int) floor((100 - 2 * $margin) / $square);
    $rows = (int) floor(($bottom - $header) / $square);
    $left = (100 - $columns * $square) / 2;

    foreach (range(0, $columns) as $column) {
        $ruled .= 'M'.round($left + $column * $square, 2).' '.round($header, 2).'V'.round($header + $rows * $square, 2);
    }

    foreach (range(0, $rows) as $row) {
        $ruled .= 'M'.round($left, 2).' '.round($header + $row * $square, 2).'H'.round($left + $columns * $square, 2);
    }
}

// Sheets underneath, from the board at the back to the sheet just under the top one.
$layers = [[1.0, '#b6afa2'], [0.8, '#e8e6e0'], [0.6, '#eeede8'], [0.4, '#f3f2ee'], [0.2, '#f8f7f4']];
$band = $strip * $w / 100;
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="notepad"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <linearGradient id="<?php echo esc_attr($uid); ?>-strip" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#4a4a47" />
            <stop offset="1" stop-color="#353533" />
        </linearGradient>
        <linearGradient id="<?php echo esc_attr($uid); ?>-crease" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#000" stop-opacity="0.14" />
            <stop offset="1" stop-color="#000" stop-opacity="0" />
        </linearGradient>
    </defs>

    <g transform="rotate(<?php echo esc_attr($tilt); ?> <?php echo esc_attr($cx); ?> <?php echo esc_attr($cy); ?>)">
        <?php // The edge of the pad ?>
        <?php foreach ($layers as [$share, $fill]) : ?>
            <rect x="<?php echo esc_attr($x + $dx * $share); ?>" y="<?php echo esc_attr($y + $dy * $share); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.5" fill="<?php echo esc_attr($fill); ?>" stroke="#0a0a0a" stroke-opacity="0.08" stroke-width="0.25" />
        <?php endforeach; ?>

        <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.5" fill="#ffffff" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.35" />

        <svg x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" viewBox="0 0 100 141.4">
            <?php // Ruling: squares are drawn finer than lines, as on paper ?>
            <?php if ($ruled !== '') : ?>
                <path d="<?php echo esc_attr($ruled); ?>" stroke="#457b9d" stroke-opacity="<?php echo esc_attr($ruling === 'grid' ? '0.32' : '0.45'); ?>" stroke-width="<?php echo esc_attr($ruling === 'grid' ? '0.3' : '0.42'); ?>" />
            <?php endif; ?>

            <?php // The logo: the sample motif, replaced by the visitor's own motif once chosen ?>
            <?php
            Template::output('mockup/parts/motif', [
                'uid' => $uid,
                'box' => Svg::box(8, $strip + 5, $logo, $logo),
                'motif' => 'motif/'.$mockup->motif,
                'viewBox' => '0 0 100 100',
                'fit' => 'meet',
                'vars' => ['bare' => true],
                'backing' => null,
            ]);
            ?>

            <?php // The paper bends a little where it leaves the glued strip ?>
            <rect y="<?php echo esc_attr($strip); ?>" width="100" height="1.6" fill="url(#<?php echo esc_attr($uid); ?>-crease)" />
        </svg>

        <?php // The binding strip, over the top of the pad and down its edge ?>
        <path
            d="M<?php echo esc_attr($x + $w); ?> <?php echo esc_attr($y); ?>L<?php echo esc_attr($x + $w + $dx); ?> <?php echo esc_attr($y + $dy); ?>V<?php echo esc_attr($y + $dy + $band); ?>L<?php echo esc_attr($x + $w); ?> <?php echo esc_attr($y + $band); ?>z"
            fill="#262624"
        />
        <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($band); ?>" rx="0.4" fill="url(#<?php echo esc_attr($uid); ?>-strip)" />
        <path d="M<?php echo esc_attr($x + 0.4); ?> <?php echo esc_attr($y + 0.3); ?>H<?php echo esc_attr($x + $w - 0.4); ?>" stroke="#fff" stroke-opacity="0.12" stroke-width="0.3" />
    </g>
</svg>
