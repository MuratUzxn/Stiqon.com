<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Half sun over stripes.
 *
 * @var string $uid  prefix for ids, unique on the page
 * @var bool   $bare without a background of its own (the sticker material shows through)
 */

defined('ABSPATH') || exit;
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#ead9c0" />
<?php endif; ?>
<circle cx="50" cy="58" r="24" fill="#c8553d" />
<rect x="0" y="58" width="100" height="42" fill="<?php echo esc_attr($bare ? 'none' : '#ead9c0'); ?>" />
<g stroke="#c8553d" stroke-width="3.2" stroke-linecap="round">
    <path d="M22 66h56M28 74h44M34 82h32" />
</g>
<g stroke="#1d1d1b" stroke-width="1.4" stroke-linecap="round">
    <path d="M50 22v-8M30 30l-5-6M70 30l5-6M18 46l-8-2M82 46l8-2" />
</g>
