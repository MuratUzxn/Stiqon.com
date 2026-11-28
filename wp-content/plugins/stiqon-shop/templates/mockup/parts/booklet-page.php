<?php
/**
 * A page of a leaflet or a brochure that is not the cover, as a sample: a
 * heading, text as grey bars and a picture block. Drawn unfolded in a box of
 * $width x $height at the origin; the caller lays it onto its panel.
 *
 * Everything is sized for a page 40 wide and scales with $width; the page
 * takes as many lines of text as its height has room for.
 *
 * @var float  $width
 * @var float  $height
 * @var string $layout  "picture": the picture above the text; "text": the heading first, the picture further down
 */

defined('ABSPATH') || exit;

$unit = $width / 40;
$margin = 4.4 * $unit;
$inner = $width - 2 * $margin;
$bottom = $height - $margin;
$picture = round($inner * 0.74, 2);
$lengths = [1, 0.93, 0.97, 0.88, 0.95, 0.58];

// The page from top to bottom: [what, top, width].
$rows = [];
$top = $margin + 0.6 * $unit;
$lines = static function (int $count) use (&$rows, &$top, $unit, $inner, $bottom, $lengths): void {
    for ($line = 0; $line < $count && $top + 1.3 * $unit <= $bottom; $line++) {
        $rows[] = ['line', $top, $inner * $lengths[$line % count($lengths)]];
        $top += 2.9 * $unit;
    }

    $top += 2.2 * $unit;
};

if ($layout === 'picture') {
    $rows[] = ['picture', $top, $inner];
    $top += $picture + 4.2 * $unit;
}

$rows[] = ['heading', $top, $inner * 0.68];
$top += 5.2 * $unit;
$lines(5);

if ($layout !== 'picture' && $top + $picture <= $bottom) {
    $rows[] = ['picture', $top, $inner];
    $top += $picture + 4.2 * $unit;
}

$lines(99);
?>
<?php foreach ($rows as [$what, $y, $length]) : ?>
    <?php if ($what === 'picture') : ?>
        <rect x="<?php echo esc_attr(round($margin, 2)); ?>" y="<?php echo esc_attr(round($y, 2)); ?>" width="<?php echo esc_attr(round($length, 2)); ?>" height="<?php echo esc_attr($picture); ?>" fill="#457b9d" fill-opacity="0.2" />
        <circle cx="<?php echo esc_attr(round($margin + $length * 0.72, 2)); ?>" cy="<?php echo esc_attr(round($y + $picture * 0.3, 2)); ?>" r="<?php echo esc_attr(round($picture * 0.13, 2)); ?>" fill="#d9a441" fill-opacity="0.85" />
        <path
            d="M<?php echo esc_attr(round($margin, 2)); ?> <?php echo esc_attr(round($y + $picture, 2)); ?>V<?php echo esc_attr(round($y + $picture * 0.66, 2)); ?>Q<?php echo esc_attr(round($margin + $length * 0.3, 2)); ?> <?php echo esc_attr(round($y + $picture * 0.36, 2)); ?> <?php echo esc_attr(round($margin + $length * 0.62, 2)); ?> <?php echo esc_attr(round($y + $picture * 0.7, 2)); ?>T<?php echo esc_attr(round($margin + $length, 2)); ?> <?php echo esc_attr(round($y + $picture * 0.62, 2)); ?>V<?php echo esc_attr(round($y + $picture, 2)); ?>z"
            fill="#1f3d2e" fill-opacity="0.34"
        />
    <?php elseif ($what === 'heading') : ?>
        <rect x="<?php echo esc_attr(round($margin, 2)); ?>" y="<?php echo esc_attr(round($y, 2)); ?>" width="<?php echo esc_attr(round($length, 2)); ?>" height="<?php echo esc_attr(round(2.4 * $unit, 2)); ?>" rx="<?php echo esc_attr(round(1.2 * $unit, 2)); ?>" fill="#1d3557" fill-opacity="0.88" />
    <?php else : ?>
        <rect x="<?php echo esc_attr(round($margin, 2)); ?>" y="<?php echo esc_attr(round($y, 2)); ?>" width="<?php echo esc_attr(round($length, 2)); ?>" height="<?php echo esc_attr(round(1.3 * $unit, 2)); ?>" rx="<?php echo esc_attr(round(0.65 * $unit, 2)); ?>" fill="#1d1d1b" fill-opacity="0.3" />
    <?php endif; ?>
<?php endforeach; ?>
