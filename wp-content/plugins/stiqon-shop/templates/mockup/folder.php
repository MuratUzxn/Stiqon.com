<?php
/**
 * A presentation folder as inline SVG. See Stiqon\Shop\Mockup\Folder.
 *
 * Closed: the cover with the motif, on the back cover and the pocket inside,
 * with a sheet peeking out at the open side. Open: the inside of both covers,
 * a sheet in the pocket and a business card in the pocket's slits; the
 * printed side of the pocket carries the motif.
 *
 * @var \Stiqon\Shop\Mockup\Folder $mockup
 * @var string                       $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$open = $mockup->open;
$gloss = $mockup->gloss;
$tilt = $mockup->tilt;
$x = $mockup->panel['x'];
$y = $mockup->panel['y'];
$w = $mockup->panel['width'];
$h = $mockup->panel['height'];
$spine = $mockup->spine;
$clip = 'clip-path="url(#'.esc_attr($uid).'-print)"';

if ($open) {
    // The right half: the inside of the back cover, with the pocket over its lower part.
    $px = $x + $w + $spine;
    $cx = $x + $w + $spine / 2;
    $cy = $y + $h / 2;
    $pocket = sprintf(
        'M%1$s %2$sC%3$s %4$s %5$s %6$s %7$s %8$sV%9$sH%1$sz',
        $px, $y + 0.56 * $h,
        $px + 0.38 * $w, $y + 0.5 * $h,
        $px + 0.72 * $w, $y + 0.6 * $h,
        $px + $w, $y + 0.62 * $h,
        $y + $h,
    );

    // The pocket shows the lower part of the motif, as if the cover's design
    // went on there: lettering at the top of a motif stays clear of its edge.
    $print = Svg::box($px, $y + 0.18 * $h, $w, 0.82 * $h);

    // The sheet in the pocket, a little askew.
    $sheet = ['x' => $px + 3, 'y' => $y + 3.4, 'width' => $w - 6, 'height' => $h - 5];

    // A business card of 85 x 55 mm in two slits at the pocket's lower corner,
    // its lower corners tucked in behind the pocket.
    $mm = $w / 220;
    $cardLeft = $px + $w - 3 - 85 * $mm;
    $cardRight = $px + $w - 3;
    $cardBottom = $y + $h - 2.3;
    $cardTop = $cardBottom - 55 * $mm;
    $tuck = 2.2;
    $card = sprintf(
        'M%1$s %3$sH%2$sV%5$sL%6$s %4$sH%7$sL%1$s %5$sz',
        $cardLeft, $cardRight, $cardTop, $cardBottom,
        $cardBottom - $tuck, $cardRight - $tuck, $cardLeft + $tuck,
    );
    $slits = sprintf(
        'M%1$s %2$sL%3$s %4$sM%5$s %2$sL%6$s %4$s',
        $cardLeft - 0.9, $cardBottom - $tuck - 0.9,
        $cardLeft + $tuck + 0.9, $cardBottom + 0.9,
        $cardRight + 0.9, $cardRight - $tuck - 0.9,
    );
} else {
    $right = $x + $w;
    $cx = $x + ($w + 5) / 2;
    $cy = $y + $h / 2;

    // An A4 sheet in the folder (210 of its 220 mm), sticking out at the open side.
    $sheet = ['x' => $right + 4.6 - 0.955 * $w, 'y' => $y + 5, 'width' => 0.955 * $w, 'height' => 0.958 * $h];

    // The pocket inside shows past the cover's open edge, over the sheet; its top corner is cut at a slant.
    $flap = sprintf(
        'M%1$s %2$sH%3$sL%4$s %5$sV%6$sH%1$sz',
        $x + 1, $y + 0.6 * $h,
        $right - 2.4,
        $right + 2.4, $y + 0.6 * $h + 5,
        $y + $h + 0.6,
    );
}
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="folder"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <clipPath id="<?php echo esc_attr($uid); ?>-print">
            <?php if ($open) : ?>
                <path d="<?php echo esc_attr($pocket); ?>" />
            <?php else : ?>
                <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" />
            <?php endif; ?>
        </clipPath>

        <?php // Diagonal band of light: the gloss laminate ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-gloss" x1="0.05" y1="0" x2="0.95" y2="1">
            <stop offset="0.3" stop-color="#fff" stop-opacity="0" />
            <stop offset="0.46" stop-color="#fff" stop-opacity="0.26" />
            <stop offset="0.56" stop-color="#fff" stop-opacity="0.08" />
            <stop offset="0.72" stop-color="#fff" stop-opacity="0" />
        </linearGradient>

        <?php if ($open) : ?>
            <?php // The covers bend a little towards the spine and catch less light there ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-inside" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0" stop-color="#f8f7f3" />
                <stop offset="0.7" stop-color="#f3f1ec" />
                <stop offset="1" stop-color="#e4e1da" />
            </linearGradient>
            <linearGradient id="<?php echo esc_attr($uid); ?>-inside-back" x1="1" y1="0" x2="0" y2="0">
                <stop offset="0" stop-color="#f8f7f3" />
                <stop offset="0.7" stop-color="#f3f1ec" />
                <stop offset="1" stop-color="#e4e1da" />
            </linearGradient>
        <?php else : ?>
            <?php // The cover's shadow on the sheet that peeks out ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-cast" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0" stop-color="#000" stop-opacity="0.16" />
                <stop offset="1" stop-color="#000" stop-opacity="0" />
            </linearGradient>
        <?php endif; ?>
    </defs>

    <g transform="rotate(<?php echo esc_attr($tilt); ?> <?php echo esc_attr($cx); ?> <?php echo esc_attr($cy); ?>)">
        <?php if ($open) : ?>
            <?php // The edge of the board under both halves ?>
            <rect x="<?php echo esc_attr($x + 0.4); ?>" y="<?php echo esc_attr($y + 0.6); ?>" width="<?php echo esc_attr(2 * $w + $spine); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="#d5d3cc" />

            <?php // Inside of the front cover, spine, inside of the back cover ?>
            <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w + $spine / 2); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="url(#<?php echo esc_attr($uid); ?>-inside)" />
            <rect x="<?php echo esc_attr($px - $spine / 2); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w + $spine / 2); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="url(#<?php echo esc_attr($uid); ?>-inside-back)" />
            <rect x="<?php echo esc_attr($x + $w); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($spine); ?>" height="<?php echo esc_attr($h); ?>" fill="#e7e5df" />
            <path d="M<?php echo esc_attr($x + $w); ?> <?php echo esc_attr($y); ?>v<?php echo esc_attr($h); ?>M<?php echo esc_attr($px); ?> <?php echo esc_attr($y); ?>v<?php echo esc_attr($h); ?>" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.25" />
            <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr(2 * $w + $spine); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="none" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.35" />

            <?php // The sheet in the pocket ?>
            <g transform="rotate(-1.2 <?php echo esc_attr($sheet['x'] + $sheet['width'] / 2); ?> <?php echo esc_attr($sheet['y'] + $sheet['height'] / 2); ?>)">
                <rect x="<?php echo esc_attr($sheet['x'] + 0.3); ?>" y="<?php echo esc_attr($sheet['y'] + 0.5); ?>" width="<?php echo esc_attr($sheet['width']); ?>" height="<?php echo esc_attr($sheet['height']); ?>" rx="0.4" fill="#000" fill-opacity="0.07" />
                <rect x="<?php echo esc_attr($sheet['x']); ?>" y="<?php echo esc_attr($sheet['y']); ?>" width="<?php echo esc_attr($sheet['width']); ?>" height="<?php echo esc_attr($sheet['height']); ?>" rx="0.4" fill="#ffffff" stroke="#0a0a0a" stroke-opacity="0.12" stroke-width="0.3" />
                <g fill="#1d1d1b">
                    <rect x="<?php echo esc_attr($sheet['x'] + 3.6); ?>" y="<?php echo esc_attr($sheet['y'] + 5); ?>" width="<?php echo esc_attr(0.38 * $sheet['width']); ?>" height="1.5" rx="0.75" fill-opacity="0.72" />
                    <?php foreach ([0.82, 0.78, 0.8, 0.5, 0.81, 0.76, 0.79, 0.62, 0.8, 0.74] as $row => $share) : ?>
                        <rect x="<?php echo esc_attr($sheet['x'] + 3.6); ?>" y="<?php echo esc_attr($sheet['y'] + 10 + $row * 2.3 + ($row > 3 ? 1.2 : 0)); ?>" width="<?php echo esc_attr($share * $sheet['width']); ?>" height="0.9" rx="0.45" fill-opacity="0.3" />
                    <?php endforeach; ?>
                </g>
            </g>

            <?php // The pocket stands off the sheet a little: a soft shadow along its edge ?>
            <path d="<?php echo esc_attr($pocket); ?>" fill="#000" fill-opacity="0.06" transform="translate(0 -0.7)" />
            <path d="<?php echo esc_attr($pocket); ?>" fill="#000" fill-opacity="0.06" transform="translate(0 -0.3)" />
            <path d="<?php echo esc_attr($pocket); ?>" fill="#ffffff" />

            <?php // The pocket's printed side: the sample motif, replaced by the visitor's own motif once chosen ?>
            <?php
            Template::output('mockup/parts/motif', [
                'uid' => $uid,
                'box' => $print,
                'motif' => 'motif/flyer/'.$mockup->motif,
                'viewBox' => '0 0 100 141.4',
                'fit' => 'slice',
                'vars' => [],
                'backing' => '#fff',
                'attributes' => $clip,
            ]);
            ?>

            <?php if ($gloss) : ?>
                <rect x="<?php echo esc_attr($px); ?>" y="<?php echo esc_attr($y + 0.5 * $h); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr(0.5 * $h); ?>" fill="url(#<?php echo esc_attr($uid); ?>-gloss)" clip-path="url(#<?php echo esc_attr($uid); ?>-print)" pointer-events="none" />
            <?php endif; ?>

            <path d="<?php echo esc_attr($pocket); ?>" fill="none" stroke="#0a0a0a" stroke-opacity="0.14" stroke-width="0.3" />

            <?php // The business card in the pocket's slits ?>
            <path d="<?php echo esc_attr($card); ?>" fill="#000" fill-opacity="0.12" transform="translate(0.35 0.5)" />
            <path d="<?php echo esc_attr($card); ?>" fill="#ffffff" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.25" />
            <g fill="#1d1d1b">
                <rect x="<?php echo esc_attr($cardLeft + 2); ?>" y="<?php echo esc_attr($cardTop + 2.6); ?>" width="8" height="1.1" rx="0.55" fill-opacity="0.72" />
                <rect x="<?php echo esc_attr($cardLeft + 2); ?>" y="<?php echo esc_attr($cardTop + 4.7); ?>" width="6" height="0.7" rx="0.35" fill-opacity="0.35" />
                <rect x="<?php echo esc_attr($cardLeft + 2); ?>" y="<?php echo esc_attr($cardTop + 7.6); ?>" width="9.5" height="0.7" rx="0.35" fill-opacity="0.3" />
            </g>
            <path d="<?php echo esc_attr($slits); ?>" stroke="#0a0a0a" stroke-opacity="0.4" stroke-width="0.3" stroke-linecap="round" />
        <?php else : ?>
            <?php // The back cover, just behind the front one ?>
            <rect x="<?php echo esc_attr($x + 1.3); ?>" y="<?php echo esc_attr($y + 1.1); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="#e8e6e0" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.3" />

            <?php // The sheet inside, peeking out at the open side ?>
            <rect
                x="<?php echo esc_attr($sheet['x']); ?>" y="<?php echo esc_attr($sheet['y']); ?>" width="<?php echo esc_attr($sheet['width']); ?>" height="<?php echo esc_attr($sheet['height']); ?>" rx="0.4"
                fill="#ffffff" stroke="#0a0a0a" stroke-opacity="0.12" stroke-width="0.3"
                transform="rotate(1.5 <?php echo esc_attr($sheet['x'] + $sheet['width'] / 2); ?> <?php echo esc_attr($sheet['y'] + $sheet['height'] / 2); ?>)"
            />

            <?php // The pocket inside, over the sheet, its edge showing past the cover ?>
            <path d="<?php echo esc_attr($flap); ?>" fill="#000" fill-opacity="0.08" transform="translate(0 -0.5)" />
            <path d="<?php echo esc_attr($flap); ?>" fill="#ebe8e1" stroke="#0a0a0a" stroke-opacity="0.12" stroke-width="0.3" />

            <rect x="<?php echo esc_attr($right); ?>" y="<?php echo esc_attr($y + 2); ?>" width="2.4" height="<?php echo esc_attr($h - 1); ?>" fill="url(#<?php echo esc_attr($uid); ?>-cast)" />

            <?php // The board's edge, then the cover ?>
            <rect x="<?php echo esc_attr($x + 0.5); ?>" y="<?php echo esc_attr($y + 0.6); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="#d3d2cb" />
            <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="#ffffff" />

            <?php // Printed cover: the sample motif, replaced by the visitor's own motif once chosen ?>
            <?php
            Template::output('mockup/parts/motif', [
                'uid' => $uid,
                'box' => Svg::box($x, $y, $w, $h),
                'motif' => 'motif/flyer/'.$mockup->motif,
                'viewBox' => '0 0 100 141.4',
                'fit' => 'slice',
                'vars' => [],
                'backing' => '#fff',
                'attributes' => $clip,
            ]);
            ?>

            <?php if ($gloss) : ?>
                <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" fill="url(#<?php echo esc_attr($uid); ?>-gloss)" clip-path="url(#<?php echo esc_attr($uid); ?>-print)" pointer-events="none" />
            <?php endif; ?>

            <?php // The fold along the spine catches the light ?>
            <path d="M<?php echo esc_attr($x + 0.5); ?> <?php echo esc_attr($y + 1); ?>V<?php echo esc_attr($y + $h - 1); ?>" stroke="#fff" stroke-opacity="0.35" stroke-width="0.5" />
            <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.8" fill="none" stroke="#0a0a0a" stroke-opacity="0.12" stroke-width="0.35" />
        <?php endif; ?>
    </g>
</svg>
