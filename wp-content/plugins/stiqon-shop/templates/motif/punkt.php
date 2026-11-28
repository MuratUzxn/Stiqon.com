<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Placeholder for a customer's own mark. Everything sits in the central band so wide shapes keep it.
 *
 * @var string $uid  prefix for ids, unique on the page
 * @var bool   $bare without a background of its own (the sticker material shows through)
 */

defined('ABSPATH') || exit;
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#ffffff" />
<?php endif; ?>
<circle cx="50" cy="43" r="13" fill="#0f0f0e" />
<circle cx="54.8" cy="38.2" r="3.4" fill="#d9a441" />
<text x="50" y="68" text-anchor="middle" font-size="7.6" font-weight="600" letter-spacing="0.5" fill="#0f0f0e">Ihr Logo</text>
