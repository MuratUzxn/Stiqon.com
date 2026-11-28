<?php
/**
 * A Kundenstopper as inline SVG. See Stiqon\Shop\Mockup\Standee for the formats.
 *
 * Drawn upright, then slanted as one (Standee::$tilt): the frame's far side
 * stands higher. To the left of the frame, the inside of the A shows: the
 * second panel's back, its lower bar and its leg.
 *
 * @var \Stiqon\Shop\Mockup\Standee $mockup
 * @var string                      $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Standee;
use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$p = Standee::PROFILE;
['x' => $fx, 'y' => $fy, 'width' => $fw, 'height' => $fh] = $mockup->frame;
['x' => $px, 'y' => $py, 'width' => $pw, 'height' => $ph] = $mockup->poster;
[$bx, $by] = $mockup->back;
$right = round($fx + $fw, 2);
$bottom = round($fy + $fh, 2);
$feet = round($mockup->ground - Standee::FEET, 2);

// The second leg runs from the hinge at the top down to its foot; its inner
// edge is where the second panel's back starts.
$leg = 2.4;
$footTop = round($by - Standee::FEET, 2);
$inner = static fn (float $y): float => round($fx + 1.2 + ($bx + $leg - $fx - 1.2) * ($y - $fy) / ($footTop - $fy), 2);
$barBottom = round($footTop - Standee::LEGS, 2);
$barTop = round($barBottom - 0.85 * $p, 2);

// Seen from the left, the far side stands higher: a slant about the middle.
$slant = round(tan(deg2rad($mockup->tilt)), 4);
$transform = 'matrix(1 '.$slant.' 0 1 0 '.round(-60 * $slant, 3).')';

$members = [
    'top' => "M{$fx} {$fy}H{$right}L".round($right - $p, 2).' '.round($fy + $p, 2).'H'.round($fx + $p, 2).'z',
    'left' => "M{$fx} {$fy}L".round($fx + $p, 2).' '.round($fy + $p, 2).'V'.round($bottom - $p, 2)."L{$fx} {$bottom}z",
    'bottom' => "M{$fx} {$bottom}L".round($fx + $p, 2).' '.round($bottom - $p, 2).'H'.round($right - $p, 2)."L{$right} {$bottom}z",
    'right' => "M{$right} {$fy}V{$bottom}L".round($right - $p, 2).' '.round($bottom - $p, 2).'V'.round($fy + $p, 2).'z',
];
$mitres = 'M'.$fx.' '.$fy.'l'.$p.' '.$p.'M'.$right.' '.$fy.'l-'.$p.' '.$p.'M'.$fx.' '.$bottom.'l'.$p.' -'.$p.'M'.$right.' '.$bottom.'l-'.$p.' -'.$p;
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="standee"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <?php // The click frame's profile is rounded: a bright ridge between darker edges. Top and left face the light. ?>
        <?php foreach (['top' => [0, 0, 0, 1], 'left' => [0, 0, 1, 0], 'bottom' => [0, 1, 0, 0], 'right' => [1, 0, 0, 0]] as $side => [$x1, $y1, $x2, $y2]) : ?>
            <?php $lit = in_array($side, ['top', 'left'], true); ?>
            <linearGradient id="<?php echo esc_attr($uid.'-'.$side); ?>" x1="<?php echo esc_attr($x1); ?>" y1="<?php echo esc_attr($y1); ?>" x2="<?php echo esc_attr($x2); ?>" y2="<?php echo esc_attr($y2); ?>">
                <stop offset="0" stop-color="<?php echo esc_attr($lit ? '#c3c7cd' : '#a9aeb5'); ?>" />
                <stop offset="0.32" stop-color="<?php echo esc_attr($lit ? '#f7f8f9' : '#e2e5e8'); ?>" />
                <stop offset="0.68" stop-color="<?php echo esc_attr($lit ? '#d9dce0' : '#c3c7cc'); ?>" />
                <stop offset="1" stop-color="<?php echo esc_attr($lit ? '#a5aab1' : '#8e949c'); ?>" />
            </linearGradient>
        <?php endforeach; ?>

        <?php // The second panel's back, in the shade of the first ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-inside" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#a2a7ae" />
            <stop offset="1" stop-color="#7b8189" />
        </linearGradient>

        <linearGradient id="<?php echo esc_attr($uid); ?>-rubber" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#4a4e54" />
            <stop offset="1" stop-color="#1f2124" />
        </linearGradient>

        <?php // The clear cover over the poster catches a soft band of light ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-cover" x1="0.1" y1="0" x2="0.9" y2="1">
            <stop offset="0.3" stop-color="#fff" stop-opacity="0" />
            <stop offset="0.45" stop-color="#fff" stop-opacity="0.2" />
            <stop offset="0.55" stop-color="#fff" stop-opacity="0.06" />
            <stop offset="0.7" stop-color="#fff" stop-opacity="0" />
        </linearGradient>
    </defs>

    <g transform="<?php echo esc_attr($transform); ?>">
        <?php // Inside the A: the second panel's back, its lower bar, its leg and foot ?>
        <path
            d="M<?php echo esc_attr($fx + 0.5); ?> <?php echo esc_attr($fy + 1); ?>L<?php echo esc_attr($inner($barTop)); ?> <?php echo esc_attr($barTop); ?>H<?php echo esc_attr($fx + 0.5); ?>z"
            fill="url(#<?php echo esc_attr($uid); ?>-inside)"
        />
        <path
            d="M<?php echo esc_attr($inner($barTop) - 0.4); ?> <?php echo esc_attr($barTop); ?>H<?php echo esc_attr($fx + 0.5); ?>V<?php echo esc_attr($barBottom); ?>H<?php echo esc_attr($inner($barBottom) - 0.4); ?>z"
            fill="url(#<?php echo esc_attr($uid); ?>-right)"
        />
        <path
            d="M<?php echo esc_attr($fx + 0.4); ?> <?php echo esc_attr($fy + 0.4); ?>L<?php echo esc_attr($bx); ?> <?php echo esc_attr($footTop); ?>H<?php echo esc_attr($bx + $leg); ?>L<?php echo esc_attr($fx + 1.6); ?> <?php echo esc_attr($fy + 0.4); ?>z"
            fill="url(#<?php echo esc_attr($uid); ?>-right)"
        />
        <rect x="<?php echo esc_attr($bx - 0.7); ?>" y="<?php echo esc_attr($footTop); ?>" width="<?php echo esc_attr($leg + 1.4); ?>" height="<?php echo esc_attr(Standee::FEET); ?>" rx="0.8" fill="url(#<?php echo esc_attr($uid); ?>-rubber)" />

        <?php // The front panel's legs and feet ?>
        <rect x="<?php echo esc_attr($fx); ?>" y="<?php echo esc_attr($bottom - 0.5); ?>" width="<?php echo esc_attr($p); ?>" height="<?php echo esc_attr($feet - $bottom + 0.5); ?>" fill="url(#<?php echo esc_attr($uid); ?>-left)" />
        <rect x="<?php echo esc_attr($right - $p); ?>" y="<?php echo esc_attr($bottom - 0.5); ?>" width="<?php echo esc_attr($p); ?>" height="<?php echo esc_attr($feet - $bottom + 0.5); ?>" fill="url(#<?php echo esc_attr($uid); ?>-right)" />
        <?php foreach ([$fx, $right - $p] as $foot) : ?>
            <rect x="<?php echo esc_attr($foot - 0.9); ?>" y="<?php echo esc_attr($feet); ?>" width="<?php echo esc_attr($p + 1.8); ?>" height="<?php echo esc_attr(Standee::FEET); ?>" rx="0.8" fill="url(#<?php echo esc_attr($uid); ?>-rubber)" />
        <?php endforeach; ?>

        <?php // The click frame, mitred at its corners ?>
        <?php foreach ($members as $side => $member) : ?>
            <path d="<?php echo esc_attr($member); ?>" fill="url(#<?php echo esc_attr($uid.'-'.$side); ?>)" />
        <?php endforeach; ?>
        <path d="<?php echo esc_attr($mitres); ?>" stroke="#000" stroke-opacity="0.16" stroke-width="0.2" />
        <rect x="<?php echo esc_attr($fx); ?>" y="<?php echo esc_attr($fy); ?>" width="<?php echo esc_attr($fw); ?>" height="<?php echo esc_attr($fh); ?>" fill="none" stroke="#000" stroke-opacity="0.12" stroke-width="0.3" />

        <?php // The poster, under its clear cover ?>
        <?php
        Template::output('mockup/parts/motif', [
            'uid' => $uid,
            'box' => $mockup->poster,
            'motif' => 'motif/flyer/'.$mockup->motif,
            'viewBox' => '0 0 100 141.4',
            'fit' => 'slice',
            'backing' => '#fff',
        ]);
        ?>
        <rect x="<?php echo esc_attr($px); ?>" y="<?php echo esc_attr($py); ?>" width="<?php echo esc_attr($pw); ?>" height="0.7" fill="#000" fill-opacity="0.2" />
        <rect x="<?php echo esc_attr($px); ?>" y="<?php echo esc_attr($py); ?>" width="0.6" height="<?php echo esc_attr($ph); ?>" fill="#000" fill-opacity="0.14" />
        <rect x="<?php echo esc_attr($px); ?>" y="<?php echo esc_attr($py); ?>" width="<?php echo esc_attr($pw); ?>" height="<?php echo esc_attr($ph); ?>" fill="url(#<?php echo esc_attr($uid); ?>-cover)" pointer-events="none" />

        <?php // Hinges join the two panels at the top ?>
        <?php foreach ([$fx - 0.3, $right - $p - 0.9] as $hinge) : ?>
            <rect x="<?php echo esc_attr($hinge); ?>" y="<?php echo esc_attr($fy - 1.2); ?>" width="<?php echo esc_attr($p + 1.2); ?>" height="2" rx="0.9" fill="url(#<?php echo esc_attr($uid); ?>-rubber)" />
        <?php endforeach; ?>
    </g>
</svg>
