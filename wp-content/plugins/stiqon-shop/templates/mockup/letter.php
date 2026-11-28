<?php
/**
 * A letterhead as inline SVG. See Stiqon\Shop\Mockup\Letter for the formats.
 *
 * The letter is drawn in the sheet's own 100 x 141.4 box: logo at the top
 * right, sender line and address in the window of an envelope, date, subject,
 * body, closing and a footer of three columns – laid out after DIN 5008.
 *
 * @var \Stiqon\Shop\Mockup\Letter $mockup
 * @var string                       $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$stack = $mockup->stack;
$tilt = $mockup->tilt;
$x = $mockup->sheet['x'];
$y = $mockup->sheet['y'];
$w = $mockup->sheet['width'];
$h = $mockup->sheet['height'];
$cx = $x + $w / 2;
$cy = $y + $h / 2;

// The letter, in the sheet's box. Type and margins grow on the smaller format.
$t = $mockup->scale;
$ink = '#1d1d1b';
$left = 12 * $t;
$right = 100 - 9 * $t;
$measure = $right - $left;
$logo = 24 * $t;
$line = 1.15 * $t;
$pitch = 2.75 * $t;
$footer = 141.4 - 13 * $t;
$closing = $footer - 22 * $t;

// Body: paragraphs of lines, as shares of the measure. Whatever does not fit
// above the closing is left out, and the paragraph that is cut ends short.
$body = [];
$top = 63 * $t;
$cut = false;

foreach ([[1, 0.97, 0.99, 0.55], [0.98, 1, 0.95, 0.72], [1, 0.94, 0.4]] as $paragraph) {
    foreach ($paragraph as $share) {
        if ($top + $line > $closing - 4 * $t) {
            $cut = true;

            break 2;
        }

        $body[] = [$top, $measure * $share];
        $top += $pitch;
    }

    $top += $pitch * 0.7;
}

if ($cut && $body !== []) {
    $last = array_key_last($body);
    $body[$last][1] = min($body[$last][1], $measure * 0.6);
}

// Three columns of small print: address, contact, bank.
$columns = [[0.24, 0.19, 0.22], [0.2, 0.25, 0.16], [0.23, 0.17, 0.21]];
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="letter"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <?php if ($stack) : ?>
        <?php // Sheets underneath, fanned out slightly ?>
        <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.6" fill="#eeede8" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" transform="rotate(5 <?php echo esc_attr($cx); ?> <?php echo esc_attr($cy); ?>)" />
        <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.6" fill="#f6f5f1" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" transform="rotate(1 <?php echo esc_attr($cx); ?> <?php echo esc_attr($cy); ?>)" />
    <?php endif; ?>

    <g transform="rotate(<?php echo esc_attr($tilt); ?> <?php echo esc_attr($cx); ?> <?php echo esc_attr($cy); ?>)">
        <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.6" fill="#ffffff" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" />

        <svg x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" viewBox="0 0 100 141.4">
            <?php // Fold and hole marks at the left edge ?>
            <path d="M0 50h2.6M0 70.7h3.6M0 100h2.6" stroke="<?php echo esc_attr($ink); ?>" stroke-opacity="0.3" stroke-width="0.3" />

            <?php // The logo: the sample motif, replaced by the visitor's own motif once chosen ?>
            <?php
            Template::output('mockup/parts/motif', [
                'uid' => $uid,
                'box' => Svg::box($right - $logo, 6 * $t, $logo, $logo),
                'motif' => 'motif/'.$mockup->motif,
                'viewBox' => '0 0 100 100',
                'fit' => 'meet',
                'vars' => ['bare' => true],
                'backing' => null,
            ]);
            ?>

            <g fill="<?php echo esc_attr($ink); ?>">
                <?php // Sender line and address, where the window of an envelope shows them ?>
                <rect x="<?php echo esc_attr($left); ?>" y="<?php echo esc_attr(25 * $t); ?>" width="<?php echo esc_attr(36 * $t); ?>" height="<?php echo esc_attr(0.75 * $t); ?>" rx="<?php echo esc_attr(0.375 * $t); ?>" fill-opacity="0.32" />
                <?php foreach ([24, 30, 27, 19] as $i => $length) : ?>
                    <rect x="<?php echo esc_attr($left); ?>" y="<?php echo esc_attr((29.5 + $i * 3.1) * $t); ?>" width="<?php echo esc_attr($length * $t); ?>" height="<?php echo esc_attr(1.3 * $t); ?>" rx="<?php echo esc_attr(0.65 * $t); ?>" fill-opacity="0.6" />
                <?php endforeach; ?>

                <?php // Date, set to the right ?>
                <rect x="<?php echo esc_attr($right - 17 * $t); ?>" y="<?php echo esc_attr(45 * $t); ?>" width="<?php echo esc_attr(17 * $t); ?>" height="<?php echo esc_attr($line); ?>" rx="<?php echo esc_attr($line / 2); ?>" fill-opacity="0.45" />

                <?php // Subject and salutation ?>
                <rect x="<?php echo esc_attr($left); ?>" y="<?php echo esc_attr(52 * $t); ?>" width="<?php echo esc_attr(42 * $t); ?>" height="<?php echo esc_attr(1.6 * $t); ?>" rx="<?php echo esc_attr(0.8 * $t); ?>" fill-opacity="0.78" />
                <rect x="<?php echo esc_attr($left); ?>" y="<?php echo esc_attr(58 * $t); ?>" width="<?php echo esc_attr(21 * $t); ?>" height="<?php echo esc_attr($line); ?>" rx="<?php echo esc_attr($line / 2); ?>" fill-opacity="0.45" />

                <?php // Body ?>
                <?php foreach ($body as [$top, $length]) : ?>
                    <rect x="<?php echo esc_attr($left); ?>" y="<?php echo esc_attr($top); ?>" width="<?php echo esc_attr($length); ?>" height="<?php echo esc_attr($line); ?>" rx="<?php echo esc_attr($line / 2); ?>" fill-opacity="0.3" />
                <?php endforeach; ?>

                <?php // Closing and name, with room for the signature ?>
                <rect x="<?php echo esc_attr($left); ?>" y="<?php echo esc_attr($closing); ?>" width="<?php echo esc_attr(26 * $t); ?>" height="<?php echo esc_attr($line); ?>" rx="<?php echo esc_attr($line / 2); ?>" fill-opacity="0.45" />
                <rect x="<?php echo esc_attr($left); ?>" y="<?php echo esc_attr($closing + 12 * $t); ?>" width="<?php echo esc_attr(20 * $t); ?>" height="<?php echo esc_attr($line); ?>" rx="<?php echo esc_attr($line / 2); ?>" fill-opacity="0.45" />

                <?php // Footer: three columns of small print under a hairline ?>
                <?php foreach ($columns as $column => $lengths) : ?>
                    <?php foreach ($lengths as $row => $share) : ?>
                        <rect x="<?php echo esc_attr($left + $column * $measure / 3); ?>" y="<?php echo esc_attr($footer + (2.5 + $row * 2.3) * $t); ?>" width="<?php echo esc_attr($measure * $share); ?>" height="<?php echo esc_attr(0.75 * $t); ?>" rx="<?php echo esc_attr(0.375 * $t); ?>" fill-opacity="0.28" />
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </g>

            <path d="M<?php echo esc_attr($left); ?> <?php echo esc_attr($footer); ?>H<?php echo esc_attr($right); ?>" stroke="<?php echo esc_attr($ink); ?>" stroke-opacity="0.16" stroke-width="0.25" />

            <?php // Signature ?>
            <path
                d="M0 4.6c1.3-2.6 2.9-4.3 3.7-3.4s-1.4 5.2-.3 5.4 3.1-4.8 4.6-4.4-.7 3.8.6 3.9 2.4-2.5 3.7-2.3 1.1 1.5 2.4 1.3 2.8-.8 4.8-1.1"
                fill="none" stroke="#1d3557" stroke-opacity="0.8" stroke-width="0.32" stroke-linecap="round" stroke-linejoin="round"
                transform="translate(<?php echo esc_attr($left); ?> <?php echo esc_attr($closing + 3 * $t); ?>) scale(<?php echo esc_attr($t); ?>)"
            />
        </svg>
    </g>
</svg>
