<?php
/**
 * The printed surface of a drawing: a sample motif, replaced by the visitor's own
 * motif once one is chosen in the browser (the theme's Alpine component
 * "motifLayer"). Every kind of drawing prints through this part, so the
 * visitor's motif reaches all of them the same way.
 *
 * @var string                                                   $uid         id prefix of the drawing
 * @var array{x: float, y: float, width: float, height: float}   $box         where the print goes, in the drawing's coordinates
 * @var string                                                   $motif       template of the sample motif: "motif/sonne", "motif/flyer/fest"
 * @var string                                                   $viewBox     the motif's own box: "0 0 100 100" for sticker motifs, "0 0 100 141.4" for flyer motifs
 * @var string                                                   $fit         "slice" fills the box and crops, "meet" shows all of it (a logo)
 * @var array<string, mixed>                                     $vars        further variables for the motif template, e.g. ['bare' => true]
 * @var string|null                                              $backing     colour under the visitor's motif, e.g. "#fff" on white paper; null for none
 * @var string                                                   $attributes  further attributes of the layer's group (clip-path, transform, style), escaped by the caller
 */

use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$fit ??= 'slice';
$vars ??= [];
$backing ??= null;
$attributes ??= '';
$aspect = 'xMidYMid '.($fit === 'meet' ? 'meet' : 'slice');
$x = esc_attr((string) $box['x']);
$y = esc_attr((string) $box['y']);
$w = esc_attr((string) $box['width']);
$h = esc_attr((string) $box['height']);
?>
<g x-data="motifLayer"<?php echo $attributes !== '' ? ' '.$attributes : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the caller, see above. ?>>
    <svg x="<?php echo $x; ?>" y="<?php echo $y; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" viewBox="<?php echo esc_attr($viewBox); ?>" preserveAspectRatio="<?php echo esc_attr($aspect); ?>" x-show="idle" data-motif-sample>
        <?php Template::output($motif, ['uid' => $uid, 'bare' => (bool) ($vars['bare'] ?? false)] + $vars); ?>
    </svg>
    <?php // Hidden by its own markup, not by a stylesheet: the drawing also has to be right where no script and no theme is, as in the administration. ?>
    <g x-show="active" style="display:none">
        <?php if ($backing !== null) : ?>
            <rect x="<?php echo $x; ?>" y="<?php echo $y; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" fill="<?php echo esc_attr($backing); ?>" />
        <?php endif; ?>
        <image x="<?php echo $x; ?>" y="<?php echo $y; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" preserveAspectRatio="<?php echo esc_attr($aspect); ?>" x-bind:href="url" data-motif-image />
    </g>
</g>
