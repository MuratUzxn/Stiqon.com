<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Checker pattern with a label plate.
 *
 * @var string      $uid  prefix for ids, unique on the page
 * @var bool        $bare without a background of its own (the sticker material shows through)
 * @var string|null $ink  ink of a bare print: "dark" for white and light materials, "deep" for mid-tones (kraft board and paper, grey cloth), "light" for dark ones (black, navy); null as on sticker film and kraft stickers
 */

defined('ABSPATH') || exit;

$ink ??= null;
// In light ink the squares are white, the colour of the motif's own ground, and the
// label is cut out of the print: the dark material shows through its letters. In deep
// ink the plate stays gold: a bronze one would bury the dark label, and a plate this
// large shows on kraft and grey cloth by its hue.
$light = $bare && $ink === 'light';
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#ffffff" />
<?php endif; ?>
<?php if ($light) : ?>
    <defs>
        <mask id="<?php echo esc_attr($uid); ?>-hallo">
            <rect width="100" height="100" fill="#fff" />
            <text x="50" y="55" text-anchor="middle" font-size="14" font-weight="700" letter-spacing="-0.4" fill="#000">Hallo</text>
        </mask>
    </defs>
<?php endif; ?>
<g fill="<?php echo esc_attr($light ? '#ffffff' : '#141412'); ?>"<?php if ($light) : ?> mask="url(#<?php echo esc_attr($uid); ?>-hallo)"<?php endif; ?>>
    <?php foreach (range(0, 4) as $row) : ?>
        <?php foreach (range(0, 4) as $column) : ?>
            <?php if (($row + $column) % 2 === 0) : ?>
                <rect x="<?php echo esc_attr($column * 20); ?>" y="<?php echo esc_attr($row * 20); ?>" width="20" height="20" />
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endforeach; ?>
</g>
<rect x="18" y="36" width="64" height="28" rx="14" fill="#d9a441"<?php if ($light) : ?> mask="url(#<?php echo esc_attr($uid); ?>-hallo)"<?php endif; ?> />
<?php if (! $light) : ?>
<text x="50" y="55" text-anchor="middle" font-size="14" font-weight="700" letter-spacing="-0.4" fill="#141412">Hallo</text>
<?php endif; ?>
