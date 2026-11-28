<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Half sun over stripes.
 *
 * @var string      $uid  prefix for ids, unique on the page
 * @var bool        $bare without a background of its own (the sticker material shows through)
 * @var string|null $ink  ink of a bare print: "dark" for white and light materials, "deep" for mid-tones (kraft board and paper, grey cloth), "light" for dark ones (black, navy); null as on sticker film and kraft stickers
 */

defined('ABSPATH') || exit;

$ink ??= null;
// In light ink the rays take the sand of the motif's own ground.
$rays = $bare && $ink === 'light' ? '#ead9c0' : '#1d1d1b';
// On its own ground the sun sets: the ground hides its lower half. Printed bare without
// that ground the whole disc would show and the stripes would cross it, so a print in
// an ink draws the half sun itself. Bare stickers keep the whole disc, as approved.
$half = $bare && in_array($ink, ['dark', 'deep', 'light'], true);
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#ead9c0" />
<?php endif; ?>
<?php if ($half) : ?>
<path d="M26 58a24 24 0 0 1 48 0z" fill="#c8553d" />
<?php else : ?>
<circle cx="50" cy="58" r="24" fill="#c8553d" />
<rect x="0" y="58" width="100" height="42" fill="<?php echo esc_attr($bare ? 'none' : '#ead9c0'); ?>" />
<?php endif; ?>
<g stroke="#c8553d" stroke-width="3.2" stroke-linecap="round">
    <path d="M22 66h56M28 74h44M34 82h32" />
</g>
<g stroke="<?php echo esc_attr($rays); ?>" stroke-width="1.4" stroke-linecap="round">
    <path d="M50 22v-8M30 30l-5-6M70 30l5-6M18 46l-8-2M82 46l8-2" />
</g>
