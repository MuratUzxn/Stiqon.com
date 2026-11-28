<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * A single serif initial in a ring.
 *
 * @var string      $uid  prefix for ids, unique on the page
 * @var bool        $bare without a background of its own (the sticker material shows through)
 * @var string|null $ink  ink of a bare print: "dark" for white and light materials, "light" for dark ones (black, navy); null as on sticker film and kraft
 */

defined('ABSPATH') || exit;

$ink ??= null;
// Cream on the motif's own green ground, and so in light ink.
$line = $bare && $ink !== 'light' ? '#1f3d2e' : '#e9dfc8';
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#1f3d2e" />
<?php endif; ?>
<circle cx="50" cy="50" r="38" fill="none" stroke="<?php echo esc_attr($line); ?>" stroke-width="0.9" />
<text x="50" y="68" text-anchor="middle" font-size="58" fill="<?php echo esc_attr($line); ?>" style="font-family:var(--font-accent);font-style:italic">M</text>
