<?php
/**
 * A folded leaflet as inline SVG. See Stiqon\Shop\Mockup\Leaflet for how its panels stand.
 *
 * @var \Stiqon\Shop\Mockup\Leaflet $mockup
 * @var string                        $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$w = $mockup->panelWidth;
$h = $mockup->panelHeight;
$panels = $mockup->panels;
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="leaflet"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <?php // Light falls on each panel by how it is turned; folds that point away lie in each other's shade. ?>
        <?php foreach (array_keys($panels) as $index) : ?>
            <?php [$left, $right] = $mockup->shade($index); ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-shade-<?php echo esc_attr($index); ?>" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0" stop-color="#000" stop-opacity="<?php echo esc_attr($left); ?>" />
                <stop offset="1" stop-color="#000" stop-opacity="<?php echo esc_attr($right); ?>" />
            </linearGradient>
        <?php endforeach; ?>

        <?php if ($mockup->gloss) : ?>
            <linearGradient id="<?php echo esc_attr($uid); ?>-gloss" x1="0.05" y1="0" x2="0.95" y2="1">
                <stop offset="0.3" stop-color="#fff" stop-opacity="0" />
                <stop offset="0.45" stop-color="#fff" stop-opacity="0.26" />
                <stop offset="0.55" stop-color="#fff" stop-opacity="0.08" />
                <stop offset="0.7" stop-color="#fff" stop-opacity="0" />
            </linearGradient>
        <?php endif; ?>
    </defs>

    <?php if ($mockup->heavy) : ?>
        <?php // The top edge of thicker stock, seen from a little above ?>
        <?php foreach ($panels as $panel) : ?>
            <path
                d="M<?php echo esc_attr($panel['left'][0]); ?> <?php echo esc_attr($panel['left'][1] - 1.2); ?>L<?php echo esc_attr($panel['right'][0]); ?> <?php echo esc_attr($panel['right'][1] - 1.2); ?>V<?php echo esc_attr($panel['right'][1] + 1); ?>L<?php echo esc_attr($panel['left'][0]); ?> <?php echo esc_attr($panel['left'][1] + 1); ?>z"
                fill="#d6d6d0" stroke="#0a0a0a" stroke-opacity="0.12" stroke-width="0.3" stroke-linejoin="round"
            />
        <?php endforeach; ?>
    <?php endif; ?>

    <?php foreach ($panels as $index => $panel) : ?>
        <?php $outline = $mockup->outline($index); ?>
        <path d="<?php echo esc_attr($outline); ?>" fill="#ffffff" />

        <?php if ($panel['face'] === 'cover') : ?>
            <?php // Front panel: the sample motif, replaced by the visitor's own motif once chosen ?>
            <?php
            Template::output('mockup/parts/motif', [
                'uid' => $uid,
                'box' => Svg::box(0, 0, $w, $h),
                'motif' => 'motif/flyer/'.$mockup->motif,
                'viewBox' => '0 0 100 141.4',
                'fit' => 'slice',
                'backing' => '#fff',
                'attributes' => 'transform="'.esc_attr($panel['matrix']).'"',
            ]);
            ?>
        <?php else : ?>
            <g transform="<?php echo esc_attr($panel['matrix']); ?>">
                <?php Template::output('mockup/parts/booklet-page', ['width' => $w, 'height' => $h, 'layout' => $index % 2 === 0 ? 'picture' : 'text']); ?>
            </g>
        <?php endif; ?>

        <path d="<?php echo esc_attr($outline); ?>" fill="url(#<?php echo esc_attr($uid); ?>-shade-<?php echo esc_attr($index); ?>)" />

        <?php if ($mockup->gloss) : ?>
            <path d="<?php echo esc_attr($outline); ?>" fill="url(#<?php echo esc_attr($uid); ?>-gloss)" opacity="<?php echo esc_attr(round(0.35 + 0.65 * $panel['light'], 2)); ?>" pointer-events="none" />
        <?php endif; ?>
    <?php endforeach; ?>

    <?php // Folds: a fine light line where the paper turns towards the viewer, a darker one where it turns away ?>
    <?php foreach ($mockup->folds as $fold) : ?>
        <path
            d="M<?php echo esc_attr($fold['x']); ?> <?php echo esc_attr($fold['y']); ?>v<?php echo esc_attr($h); ?>"
            stroke="<?php echo esc_attr($fold['ridge'] ? '#ffffff' : '#000000'); ?>" stroke-opacity="<?php echo esc_attr($fold['ridge'] ? '0.75' : '0.16'); ?>" stroke-width="<?php echo esc_attr($fold['ridge'] ? '0.35' : '0.4'); ?>"
        />
    <?php endforeach; ?>

    <?php foreach (array_keys($panels) as $index) : ?>
        <path d="<?php echo esc_attr($mockup->outline($index)); ?>" fill="none" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" stroke-linejoin="round" />
    <?php endforeach; ?>
</svg>
