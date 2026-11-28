<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Two leaves and a short label.
 *
 * @var string $uid  prefix for ids, unique on the page
 * @var bool   $bare without a background of its own (the sticker material shows through)
 */

defined('ABSPATH') || exit;
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#dfe6d6" />
<?php endif; ?>
<path d="M50 70c-18-6-26-22-22-42 18 4 28 18 22 42z" fill="#55704a" />
<path d="M50 70c4-22 16-32 34-32-2 18-14 30-34 32z" fill="#7d9a6c" />
<path d="M50 72V46" stroke="#2f4329" stroke-width="1.2" stroke-linecap="round" />
<text x="50" y="88" text-anchor="middle" font-size="8" font-weight="700" letter-spacing="3.4" fill="#2f4329">REGIONAL</text>
