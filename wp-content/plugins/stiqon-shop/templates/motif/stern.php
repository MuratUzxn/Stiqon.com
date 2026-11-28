<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Star (contour cut).
 *
 * @var string      $uid  prefix for ids, unique on the page
 * @var bool        $bare without a background of its own (the sticker material shows through)
 * @var string|null $ink  ink of a bare print: "dark" for white and light materials, "deep" for mid-tones (kraft board and paper, grey cloth), "light" for dark ones (black, navy); null as on sticker film and kraft stickers
 */

defined('ABSPATH') || exit;

$ink ??= null;
// White lettering, made for the star's red ground; on a white, light or mid-tone material it is dark.
$lettering = $bare && in_array($ink, ['dark', 'deep'], true) ? '#1d1d1b' : '#ffffff';
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#c8553d" />
<?php endif; ?>
<text x="50" y="57" text-anchor="middle" font-size="13" font-weight="700" letter-spacing="-0.3" fill="<?php echo esc_attr($lettering); ?>">Top!</text>
