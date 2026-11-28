<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * A thank-you in the serif italic.
 *
 * @var string      $uid  prefix for ids, unique on the page
 * @var bool        $bare without a background of its own (the sticker material shows through)
 * @var string|null $ink  ink of a bare print: "dark" for white and light materials, "deep" for mid-tones (kraft board and paper, grey cloth), "light" for dark ones (black, navy); null as on sticker film and kraft stickers
 */

defined('ABSPATH') || exit;

$ink ??= null;
// In light ink the lettering takes the pink of the motif's own ground.
$lettering = $bare && $ink === 'light' ? '#f3d6cc' : '#7a2e2e';
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#f3d6cc" />
<?php endif; ?>
<text x="50" y="58" text-anchor="middle" font-size="30" fill="<?php echo esc_attr($lettering); ?>" style="font-family:var(--font-accent);font-style:italic">Danke!</text>
<path d="M30 68c8 5 32 5 40 0" fill="none" stroke="<?php echo esc_attr($lettering); ?>" stroke-width="1.1" stroke-linecap="round" />
<g fill="<?php echo esc_attr($lettering); ?>">
    <path d="M27 31l1.4 3.4 3.4 1.4-3.4 1.4-1.4 3.4-1.4-3.4-3.4-1.4 3.4-1.4z" />
    <path d="M74 28l1 2.4 2.4 1-2.4 1-1 2.4-1-2.4-2.4-1 2.4-1z" />
</g>
