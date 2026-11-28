<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Seal: lettering on a ring around a serif word.
 *
 * @var string      $uid  prefix for ids, unique on the page
 * @var bool        $bare without a background of its own (the sticker material shows through)
 * @var string|null $ink  ink of a bare print: "dark" for white and light materials, "light" for dark ones (black, navy); null as on sticker film and kraft
 */

defined('ABSPATH') || exit;

$ink ??= null;
// In light ink the seal takes the cream of the motif's own ground.
$line = $bare && $ink === 'light' ? '#f1ebdd' : '#1d1d1b';
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#f1ebdd" />
<?php endif; ?>
<defs>
    <path id="<?php echo esc_attr($uid); ?>-ring" d="M16.5 50a33.5 33.5 0 1 1 67 0a33.5 33.5 0 1 1 -67 0" />
</defs>
<circle cx="50" cy="50" r="42" fill="none" stroke="<?php echo esc_attr($line); ?>" stroke-width="1.1" />
<circle cx="50" cy="50" r="26" fill="none" stroke="<?php echo esc_attr($line); ?>" stroke-width="0.7" />
<text font-size="6.6" font-weight="600" fill="<?php echo esc_attr($line); ?>">
    <textPath href="#<?php echo esc_attr($uid); ?>-ring" textLength="206" lengthAdjust="spacing">HANDGEMACHT · MIT SORGFALT · IN KLEINER AUFLAGE ·</textPath>
</text>
<text x="50" y="56.5" text-anchor="middle" font-size="19" fill="<?php echo esc_attr($line); ?>" style="font-family:var(--font-accent);font-style:italic">Fein</text>
