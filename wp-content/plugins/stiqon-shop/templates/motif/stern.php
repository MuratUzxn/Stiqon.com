<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Star (contour cut).
 *
 * @var string $uid  prefix for ids, unique on the page
 * @var bool   $bare without a background of its own (the sticker material shows through)
 */

defined('ABSPATH') || exit;
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#c8553d" />
<?php endif; ?>
<text x="50" y="57" text-anchor="middle" font-size="13" font-weight="700" letter-spacing="-0.3" fill="#ffffff">Top!</text>
