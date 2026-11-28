<?php
/**
 * A poster as inline SVG, hanging from two binder clips on a string. See
 * Stiqon\Shop\Mockup\Poster for the formats.
 *
 * @var \Stiqon\Shop\Mockup\Poster $mockup
 * @var string                       $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$gloss = $mockup->gloss;
$tilt = $mockup->tilt;
$x = $mockup->sheet['x'];
$y = $mockup->sheet['y'];
$w = $mockup->sheet['width'];
$h = $mockup->sheet['height'];
$line = $mockup->line;
[[$leftPinX, $leftPinY], [$rightPinX, $rightPinY]] = $mockup->pins;
[$leftClip, $rightClip] = $mockup->clips;

// The string runs from pin to pin through the clips' handles, pulled down by the poster.
$string = "M{$leftPinX} {$leftPinY}L{$leftClip} {$line}L{$rightClip} {$line}L{$rightPinX} {$rightPinY}";

// Binder clips of one size for every format: the jaw grips the sheet's top
// edge, the wire handles are folded up and hang on the string. Both clips
// share their paths – a page shows many drawings.
$jawTop = round($y - 1.4, 2);
$jawBottom = round($y + 3.1, 2);
$loopTop = round($line - 0.4, 2);
$handles = '';
$jaws = '';
$edges = '';

foreach ($mockup->clips as $clip) {
    $handles .= sprintf(
        'M%s %sL%s %sQ%s %s %s %sQ%s %s %s %sL%s %s',
        round($clip - 2.3, 2), $jawTop + 0.5,
        round($clip - 1.5, 2), $line + 0.8,
        round($clip - 1.4, 2), $loopTop, $clip, $loopTop,
        round($clip + 1.4, 2), $loopTop, round($clip + 1.5, 2), $line + 0.8,
        round($clip + 2.3, 2), $jawTop + 0.5,
    );
    $jaws .= sprintf(
        'M%1$s %3$sV%4$sQ%1$s %5$s %6$s %5$sH%7$sQ%2$s %5$s %2$s %4$sV%3$sz',
        round($clip - 3.2, 2), round($clip + 3.2, 2),
        $jawBottom, $jawTop + 1, $jawTop,
        round($clip - 2.2, 2), round($clip + 2.2, 2),
    );
    // The rounded back of the jaw catches the light, and so does its lower lip.
    $edges .= sprintf('M%s %sH%sM%s %sH%s', round($clip - 2.4, 2), $jawTop + 0.55, round($clip + 2.4, 2), round($clip - 3.1, 2), $jawBottom - 0.25, round($clip + 3.1, 2));
}
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="poster"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <?php if ($gloss) : ?>
            <?php // Diagonal band of light: glossy photo paper ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-gloss" x1="0.05" y1="0" x2="0.95" y2="1">
                <stop offset="0.3" stop-color="#fff" stop-opacity="0" />
                <stop offset="0.45" stop-color="#fff" stop-opacity="0.26" />
                <stop offset="0.55" stop-color="#fff" stop-opacity="0.08" />
                <stop offset="0.7" stop-color="#fff" stop-opacity="0" />
            </linearGradient>
        <?php endif; ?>

        <?php // The black of a binder clip, lighter on its rounded back ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-clip" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#55554f" />
            <stop offset="0.3" stop-color="#262624" />
            <stop offset="1" stop-color="#121211" />
        </linearGradient>

        <?php // Where a clip presses on the paper, it shades it a little ?>
        <radialGradient id="<?php echo esc_attr($uid); ?>-contact">
            <stop offset="0" stop-color="#000" stop-opacity="0.22" />
            <stop offset="1" stop-color="#000" stop-opacity="0" />
        </radialGradient>

        <radialGradient id="<?php echo esc_attr($uid); ?>-pin" cx="0.38" cy="0.34" r="0.72">
            <stop offset="0" stop-color="#ffffff" />
            <stop offset="1" stop-color="#9f9f9a" />
        </radialGradient>
    </defs>

    <g transform="rotate(<?php echo esc_attr($tilt); ?> 60 60)">
        <path d="<?php echo esc_attr($string); ?>" fill="none" stroke="#2f2f2d" stroke-opacity="0.85" stroke-width="0.3" stroke-linecap="round" stroke-linejoin="round" />
        <?php foreach ($mockup->pins as [$pinX, $pinY]) : ?>
            <circle cx="<?php echo esc_attr($pinX); ?>" cy="<?php echo esc_attr($pinY); ?>" r="1.15" fill="url(#<?php echo esc_attr($uid); ?>-pin)" stroke="#0a0a0a" stroke-opacity="0.2" stroke-width="0.2" />
        <?php endforeach; ?>

        <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" rx="0.4" fill="#ffffff" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" />

        <?php // Printed side: the sample motif, replaced by the visitor's own motif once chosen ?>
        <?php
        Template::output('mockup/parts/motif', [
            'uid' => $uid,
            'box' => Svg::box($x, $y, $w, $h),
            'motif' => 'motif/flyer/'.$mockup->motif,
            'viewBox' => '0 0 100 141.4',
            'fit' => 'slice',
            'backing' => '#fff',
        ]);
        ?>

        <?php if ($gloss) : ?>
            <rect x="<?php echo esc_attr($x); ?>" y="<?php echo esc_attr($y); ?>" width="<?php echo esc_attr($w); ?>" height="<?php echo esc_attr($h); ?>" fill="url(#<?php echo esc_attr($uid); ?>-gloss)" pointer-events="none" />
        <?php endif; ?>

        <?php foreach ($mockup->clips as $clip) : ?>
            <ellipse cx="<?php echo esc_attr($clip); ?>" cy="<?php echo esc_attr($jawBottom + 0.2); ?>" rx="4.2" ry="1.3" fill="url(#<?php echo esc_attr($uid); ?>-contact)" />
        <?php endforeach; ?>

        <?php // The wire handles, dark with a bright edge, then the jaws ?>
        <path d="<?php echo esc_attr($handles); ?>" fill="none" stroke="#5f5f5a" stroke-width="0.6" stroke-linecap="round" stroke-linejoin="round" />
        <path d="<?php echo esc_attr($handles); ?>" fill="none" stroke="#d2d2cd" stroke-width="0.24" stroke-linecap="round" stroke-linejoin="round" />
        <path d="<?php echo esc_attr($jaws); ?>" fill="url(#<?php echo esc_attr($uid); ?>-clip)" />
        <path d="<?php echo esc_attr($edges); ?>" stroke="#ffffff" stroke-opacity="0.18" stroke-width="0.3" stroke-linecap="round" />
    </g>
</svg>
