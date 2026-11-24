<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * A thank-you in the serif italic.
 *
 * @var string $uid  prefix for ids, unique on the page
 * @var bool   $bare without a background of its own (the sticker material shows through)
 */

defined('ABSPATH') || exit;
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#f3d6cc" />
<?php endif; ?>
<text x="50" y="58" text-anchor="middle" font-size="30" fill="#7a2e2e" style="font-family:var(--font-accent);font-style:italic">Danke!</text>
<path d="M30 68c8 5 32 5 40 0" fill="none" stroke="#7a2e2e" stroke-width="1.1" stroke-linecap="round" />
<g fill="#7a2e2e">
    <path d="M27 31l1.4 3.4 3.4 1.4-3.4 1.4-1.4 3.4-1.4-3.4-3.4-1.4 3.4-1.4z" />
    <path d="M74 28l1 2.4 2.4 1-2.4 1-1 2.4-1-2.4-2.4-1 2.4-1z" />
</g>
