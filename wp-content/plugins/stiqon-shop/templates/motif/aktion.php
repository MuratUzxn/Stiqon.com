<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Offer badge: large figure in a ring.
 *
 * @var string      $uid  prefix for ids, unique on the page
 * @var bool        $bare without a background of its own (the sticker material shows through)
 * @var string|null $ink  ink of a bare print: "dark" for white and light materials, "light" for dark ones (black, navy); null as on sticker film and kraft
 */

defined('ABSPATH') || exit;

$ink ??= null;
// The figure is white on the badge's own black ground, and so in light ink. In dark
// ink the small label is dark as well: gold is too pale to read on grey cloth or kraft.
$figure = $bare && $ink !== 'light' ? '#141412' : '#ffffff';
$label = $bare && $ink === 'dark' ? '#141412' : '#d9a441';
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#141412" />
<?php endif; ?>
<circle cx="50" cy="50" r="40" fill="none" stroke="#d9a441" stroke-width="1.6" />
<circle cx="50" cy="50" r="36" fill="none" stroke="#d9a441" stroke-width="0.5" stroke-dasharray="1.2 2.4" />
<text x="50" y="58" text-anchor="middle" font-size="27" font-weight="700" letter-spacing="-1.2" fill="<?php echo esc_attr($figure); ?>">−20 %</text>
<text x="50" y="72" text-anchor="middle" font-size="6.4" font-weight="600" letter-spacing="2.6" fill="<?php echo esc_attr($label); ?>">AKTION</text>
