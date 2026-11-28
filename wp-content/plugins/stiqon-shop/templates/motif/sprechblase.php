<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Speech bubble (contour cut): the bubble itself is the sticker's outline.
 *
 * @var string      $uid  prefix for ids, unique on the page
 * @var bool        $bare without a background of its own (the sticker material shows through)
 * @var string|null $ink  ink of a bare print: "dark" for white and light materials, "deep" for mid-tones (kraft board and paper, grey cloth), "light" for dark ones (black, navy); null as on sticker film and kraft stickers
 */

defined('ABSPATH') || exit;

$ink ??= null;
// In light ink the lettering takes the yellow of the bubble.
$lettering = $bare && $ink === 'light' ? '#f2c94c' : '#141412';
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#f2c94c" />
<?php endif; ?>
<text x="50" y="47" text-anchor="middle" font-size="19" font-weight="700" letter-spacing="-0.8" fill="<?php echo esc_attr($lettering); ?>">Hallo!</text>
<text x="50" y="59" text-anchor="middle" font-size="5.6" font-weight="700" letter-spacing="2.2" fill="<?php echo esc_attr($lettering); ?>">WILLKOMMEN</text>
