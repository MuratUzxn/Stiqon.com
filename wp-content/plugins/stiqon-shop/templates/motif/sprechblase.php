<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Speech bubble (contour cut): the bubble itself is the sticker's outline.
 *
 * @var string $uid  prefix for ids, unique on the page
 * @var bool   $bare without a background of its own (the sticker material shows through)
 */

defined('ABSPATH') || exit;
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#f2c94c" />
<?php endif; ?>
<text x="50" y="47" text-anchor="middle" font-size="19" font-weight="700" letter-spacing="-0.8" fill="#141412">Hallo!</text>
<text x="50" y="59" text-anchor="middle" font-size="5.6" font-weight="700" letter-spacing="2.2" fill="#141412">WILLKOMMEN</text>
