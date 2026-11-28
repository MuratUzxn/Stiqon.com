<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Two leaves and a short label.
 *
 * @var string      $uid  prefix for ids, unique on the page
 * @var bool        $bare without a background of its own (the sticker material shows through)
 * @var string|null $ink  ink of a bare print: "dark" for white and light materials, "deep" for mid-tones (kraft board and paper, grey cloth), "light" for dark ones (black, navy); null as on sticker film and kraft stickers
 */

defined('ABSPATH') || exit;

$ink ??= null;
// In light ink the leaves turn a shade lighter, and stem and label take the pale green of the motif's own ground.
$light = $bare && $ink === 'light';
$leaves = $light ? ['#7d9a6c', '#a6bf93'] : ['#55704a', '#7d9a6c'];
$label = $light ? '#dfe6d6' : '#2f4329';
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#dfe6d6" />
<?php endif; ?>
<path d="M50 70c-18-6-26-22-22-42 18 4 28 18 22 42z" fill="<?php echo esc_attr($leaves[0]); ?>" />
<path d="M50 70c4-22 16-32 34-32-2 18-14 30-34 32z" fill="<?php echo esc_attr($leaves[1]); ?>" />
<path d="M50 72V46" stroke="<?php echo esc_attr($label); ?>" stroke-width="1.2" stroke-linecap="round" />
<text x="50" y="88" text-anchor="middle" font-size="8" font-weight="700" letter-spacing="3.4" fill="<?php echo esc_attr($label); ?>">REGIONAL</text>
