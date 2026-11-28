<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Layered waves.
 *
 * @var string      $uid  prefix for ids, unique on the page
 * @var bool        $bare without a background of its own (the sticker material shows through)
 * @var string|null $ink  ink of a bare print: "dark" for white and light materials, "light" for dark ones (black, navy); null as on sticker film and kraft
 */

defined('ABSPATH') || exit;

$ink ??= null;
// From the back to the front, each wave darker; in light ink every one a step lighter.
$waves = $bare && $ink === 'light' ? ['#e9f0f3', '#a8c5da', '#457b9d'] : ['#a8c5da', '#457b9d', '#1d3557'];
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#e9f0f3" />
<?php endif; ?>
<path d="M0 44c12-8 22-8 34 0s22 8 34 0 20-8 32 0v56H0z" fill="<?php echo esc_attr($waves[0]); ?>" />
<path d="M0 58c12-8 22-8 34 0s22 8 34 0 20-8 32 0v42H0z" fill="<?php echo esc_attr($waves[1]); ?>" />
<path d="M0 72c12-8 22-8 34 0s22 8 34 0 20-8 32 0v28H0z" fill="<?php echo esc_attr($waves[2]); ?>" />
<circle cx="72" cy="24" r="9" fill="#d9a441" />
