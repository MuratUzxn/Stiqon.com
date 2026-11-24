<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Bold word with a small burst.
 *
 * @var string $uid  prefix for ids, unique on the page
 * @var bool   $bare without a background of its own (the sticker material shows through)
 */

defined('ABSPATH') || exit;
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#0f0f0e" />
<?php endif; ?>
<text x="50" y="63" text-anchor="middle" font-size="36" font-weight="700" letter-spacing="-1.6" fill="<?php echo esc_attr($bare ? '#0f0f0e' : '#ffffff'); ?>">NEU</text>
<path d="M77 17l2.6 6.2 6.6-1.4-3.6 5.7 4.6 4.9-6.7.6-1.4 6.6-4-5.4-6.1 2.7 1.9-6.4-5.8-3.4 6.3-2.2-.4-6.7z" fill="#d9a441" />
<path d="M22 78h56" stroke="<?php echo esc_attr($bare ? '#0f0f0e' : '#ffffff'); ?>" stroke-width="1" />
