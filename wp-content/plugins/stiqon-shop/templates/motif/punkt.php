<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Placeholder for a customer's own mark. Everything sits in the central band so wide shapes keep it.
 *
 * @var string      $uid  prefix for ids, unique on the page
 * @var bool        $bare without a background of its own (the sticker material shows through)
 * @var string|null $ink  ink of a bare print: "dark" for white and light materials, "light" for dark ones (black, navy); null as on sticker film and kraft
 */

defined('ABSPATH') || exit;

$ink ??= null;
// In light ink the mark is white, the colour of the motif's own ground.
$mark = $bare && $ink === 'light' ? '#ffffff' : '#0f0f0e';
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#ffffff" />
<?php endif; ?>
<circle cx="50" cy="43" r="13" fill="<?php echo esc_attr($mark); ?>" />
<circle cx="54.8" cy="38.2" r="3.4" fill="#d9a441" />
<text x="50" y="68" text-anchor="middle" font-size="7.6" font-weight="600" letter-spacing="0.5" fill="<?php echo esc_attr($mark); ?>">Ihr Logo</text>
