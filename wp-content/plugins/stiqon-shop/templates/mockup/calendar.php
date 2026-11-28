<?php
/**
 * A calendar as inline SVG. See Stiqon\Shop\Mockup\Calendar for the variants.
 *
 * Wall: a portrait calendar on a nail, wire-o binding at the top, the picture
 * of the month above the month grid. Desk: a landscape page on a cardboard
 * stand folded like a tent, picture and month grid side by side. Planner: a
 * landscape sheet with the picture at its head and a column of days per month.
 *
 * @var \Stiqon\Shop\Mockup\Calendar $mockup
 * @var string                         $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Calendar;
use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$variant = $mockup->variant;
$tilt = $mockup->tilt;
$x = $mockup->sheet['x'];
$y = $mockup->sheet['y'];
$w = $mockup->sheet['width'];
$h = $mockup->sheet['height'];
$grid = $mockup->grid;
$photo = $mockup->picture;

// The calendar's own print: grey days, Sundays in the soft red German
// calendars print them in, and what someone has marked or entered.
$fills = [
    'blank' => '#f6f5f2',
    'day' => '#ebeae5',
    'sunday' => '#f1d7ce',
    'mark' => '#c8553d',
    'note' => '#d9a441',
    'heading' => '#1d1d1b',
    'weekend' => '#dbd8d0',
    'holiday' => '#a8c5da',
    'event' => '#d9a441',
];

$days = $variant === 'planner'
    ? Calendar::paths($mockup->year())
    : Calendar::paths(array_merge($mockup->weekdays(), $mockup->month()), 0.5);

// The picture of the month: the sample motif, replaced by the visitor's own motif once chosen.
$picture = [
    'uid' => $uid,
    'box' => Svg::box($photo['x'], $photo['y'], $photo['width'], $photo['height']),
    'motif' => 'motif/'.$mockup->motif,
    'viewBox' => '0 0 100 100',
    'fit' => 'slice',
    'backing' => '#fff',
];

// Wire-o binding along the top edge of the page: a pair of wires through
// each punched hole and over the edge. On the wall calendar the hanger takes
// the middle.
$holes = '';
$wires = '';

if ($variant !== 'planner') {
    $hanger = $variant === 'wall' ? 4.8 : 0.0;

    for ($loop = 3.2; $loop <= $w - 3.2 + 0.01; $loop += 2.2) {
        if (abs($loop - $w / 2) < $hanger) {
            continue;
        }

        $at = round($x + $loop, 2);
        $holes .= 'M'.($at - 0.2).' '.($y + 2.2).'h0.4';
        $wires .= 'M'.($at - 0.32).' '.($y + 2.3).'V'.($y - 1.2).'M'.($at + 0.32).' '.($y + 2.3).'V'.($y - 1.2);
    }
}
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="calendar"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <?php // A nail head or a drawing pin: a small dome of metal ?>
        <radialGradient id="<?php echo esc_attr($uid); ?>-metal" cx="0.38" cy="0.34" r="0.72">
            <stop offset="0" stop-color="#ffffff" />
            <stop offset="1" stop-color="#9f9f9a" />
        </radialGradient>

        <?php if ($variant === 'desk') : ?>
            <?php // Inside the tent the cardboard lies in its own shadow, darkest under the ridge ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-stand" x1="0.5" y1="0" x2="0.2" y2="1">
                <stop offset="0" stop-color="#a59f93" />
                <stop offset="1" stop-color="#cdc7bb" />
            </linearGradient>
        <?php endif; ?>
    </defs>

    <?php if ($variant === 'wall') : ?>
        <?php [[$nailX, $nailY]] = $mockup->fixings; ?>
        <g transform="rotate(<?php echo esc_attr($tilt); ?> <?php echo esc_attr($nailX); ?> <?php echo esc_attr($nailY); ?>)">
            <?php // The months underneath and the cardboard back: the edge of the pad ?>
            <rect x="<?php echo esc_attr($x + 0.5); ?>" y="<?php echo esc_attr($y + 0.9); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.6" fill="#d8d7d1" />
            <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.6" fill="#ffffff" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" />

            <?php Template::output('mockup/parts/motif', $picture); ?>

            <?php // Month and year, the weekdays, the days ?>
            <rect x="<?php echo esc_attr($grid['x']); ?>" y="<?php echo esc_attr($grid['y'] - 7.4); ?>" width="15" height="2.4" rx="1.2" fill="#1d1d1b" fill-opacity="0.85" />
            <rect x="<?php echo esc_attr($grid['x'] + $grid['width'] - 8); ?>" y="<?php echo esc_attr($grid['y'] - 7.0); ?>" width="8" height="1.6" rx="0.8" fill="#1d1d1b" fill-opacity="0.35" />
            <?php foreach ($days as $kind => $path) : ?>
                <path d="<?php echo esc_attr($path); ?>" fill="<?php echo esc_attr($fills[$kind]); ?>" />
            <?php endforeach; ?>

            <?php // Wire-o binding, and the wire hanger in its middle hooked over the nail ?>
            <path d="<?php echo esc_attr($holes); ?>" stroke="#1d1d1b" stroke-opacity="0.3" stroke-width="1.2" stroke-linecap="round" />
            <path d="<?php echo esc_attr($wires); ?>" fill="none" stroke="#4b4b48" stroke-width="0.34" stroke-linecap="round" />
            <path
                d="M<?php echo esc_attr($nailX - 4); ?> <?php echo esc_attr($y + 0.8); ?>L<?php echo esc_attr($nailX - 0.9); ?> <?php echo esc_attr($nailY + 1.3); ?>Q<?php echo esc_attr($nailX); ?> <?php echo esc_attr($nailY - 0.7); ?> <?php echo esc_attr($nailX + 0.9); ?> <?php echo esc_attr($nailY + 1.3); ?>L<?php echo esc_attr($nailX + 4); ?> <?php echo esc_attr($y + 0.8); ?>"
                fill="none" stroke="#4b4b48" stroke-width="0.45" stroke-linecap="round" stroke-linejoin="round"
            />
        </g>
        <circle cx="<?php echo esc_attr($nailX); ?>" cy="<?php echo esc_attr($nailY); ?>" r="1.05" fill="url(#<?php echo esc_attr($uid); ?>-metal)" stroke="#0a0a0a" stroke-opacity="0.2" stroke-width="0.2" />
    <?php elseif ($variant === 'desk') : ?>
        <?php [$ridge, $front, $back] = $mockup->side; ?>
        <?php // The open side: the inside of the cardboard stand, its cut edge, and the edges of the pages ?>
        <path d="M<?php echo esc_attr($ridge[0]); ?> <?php echo esc_attr($ridge[1]); ?>L<?php echo esc_attr($front[0]); ?> <?php echo esc_attr($front[1]); ?>L<?php echo esc_attr($back[0]); ?> <?php echo esc_attr($back[1]); ?>z" fill="url(#<?php echo esc_attr($uid); ?>-stand)" />
        <path d="M<?php echo esc_attr($ridge[0]); ?> <?php echo esc_attr($ridge[1]); ?>L<?php echo esc_attr($back[0]); ?> <?php echo esc_attr($back[1]); ?>" stroke="#8f897d" stroke-width="0.6" stroke-linecap="round" />
        <path d="M<?php echo esc_attr($ridge[0]); ?> <?php echo esc_attr($ridge[1]); ?>L<?php echo esc_attr($front[0]); ?> <?php echo esc_attr($front[1]); ?>L<?php echo esc_attr($front[0] - 1); ?> <?php echo esc_attr($front[1] - 0.1); ?>L<?php echo esc_attr($ridge[0] - 0.9); ?> <?php echo esc_attr($ridge[1] + 0.4); ?>z" fill="#e6e4de" />

        <g transform="matrix(<?php echo esc_attr(implode(' ', $mockup->face)); ?>)">
            <rect x="0" y="0" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.6" fill="#ffffff" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" />

            <?php Template::output('mockup/parts/motif', $picture); ?>

            <?php // Month over year, level with the top of the picture; the weekdays, the days ?>
            <rect x="<?php echo esc_attr($grid['x']); ?>" y="<?php echo esc_attr($photo['y'] + 1.5); ?>" width="16" height="3" rx="1.5" fill="#1d1d1b" fill-opacity="0.85" />
            <rect x="<?php echo esc_attr($grid['x']); ?>" y="<?php echo esc_attr($photo['y'] + 6.5); ?>" width="9" height="1.6" rx="0.8" fill="#1d1d1b" fill-opacity="0.35" />
            <?php foreach ($days as $kind => $path) : ?>
                <path d="<?php echo esc_attr($path); ?>" fill="<?php echo esc_attr($fills[$kind]); ?>" />
            <?php endforeach; ?>

            <?php // Wire-o binding over the ridge ?>
            <path d="<?php echo esc_attr($holes); ?>" stroke="#1d1d1b" stroke-opacity="0.3" stroke-width="1.2" stroke-linecap="round" />
            <path d="<?php echo esc_attr($wires); ?>" fill="none" stroke="#4b4b48" stroke-width="0.34" stroke-linecap="round" />
        </g>
    <?php else : ?>
        <?php $title = $photo['x'] + $photo['width'] + 4; ?>
        <g transform="rotate(<?php echo esc_attr($tilt); ?> 60 60)">
            <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.5" fill="#ffffff" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" />

            <?php Template::output('mockup/parts/motif', $picture); ?>

            <?php // Title and year beside the picture, then a column of days for each month ?>
            <rect x="<?php echo esc_attr($title); ?>" y="<?php echo esc_attr($photo['y'] + 3); ?>" width="28" height="3.2" rx="1.6" fill="#1d1d1b" fill-opacity="0.85" />
            <rect x="<?php echo esc_attr($title); ?>" y="<?php echo esc_attr($photo['y'] + 8.6); ?>" width="19" height="1.6" rx="0.8" fill="#1d1d1b" fill-opacity="0.35" />
            <rect x="<?php echo esc_attr($title); ?>" y="<?php echo esc_attr($photo['y'] + 11.8); ?>" width="13" height="1.6" rx="0.8" fill="#1d1d1b" fill-opacity="0.35" />
            <rect x="<?php echo esc_attr($grid['x'] + $grid['width'] - 13); ?>" y="<?php echo esc_attr($photo['y'] + 3); ?>" width="13" height="3.2" rx="1.6" fill="#c8553d" />
            <?php foreach ($days as $kind => $path) : ?>
                <path d="<?php echo esc_attr($path); ?>" fill="<?php echo esc_attr($fills[$kind]); ?>" />
            <?php endforeach; ?>

            <?php foreach ($mockup->fixings as [$pinX, $pinY]) : ?>
                <circle cx="<?php echo esc_attr($pinX); ?>" cy="<?php echo esc_attr($pinY); ?>" r="1.5" fill="url(#<?php echo esc_attr($uid); ?>-metal)" stroke="#0a0a0a" stroke-opacity="0.2" stroke-width="0.2" />
            <?php endforeach; ?>
        </g>
    <?php endif; ?>
</svg>
