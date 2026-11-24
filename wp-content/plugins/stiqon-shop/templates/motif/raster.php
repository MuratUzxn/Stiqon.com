<?php
/**
 * Sample motif, drawn in a 100 x 100 box.
 * Checker pattern with a label plate.
 *
 * @var string $uid  prefix for ids, unique on the page
 * @var bool   $bare without a background of its own (the sticker material shows through)
 */

defined('ABSPATH') || exit;
?>
<?php if (! $bare) : ?>
    <rect width="100" height="100" fill="#ffffff" />
<?php endif; ?>
<g fill="#141412">
    <?php foreach (range(0, 4) as $row) : ?>
        <?php foreach (range(0, 4) as $column) : ?>
            <?php if (($row + $column) % 2 === 0) : ?>
                <rect x="<?php echo esc_attr($column * 20); ?>" y="<?php echo esc_attr($row * 20); ?>" width="20" height="20" />
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endforeach; ?>
</g>
<rect x="18" y="36" width="64" height="28" rx="14" fill="#d9a441" />
<text x="50" y="55" text-anchor="middle" font-size="14" font-weight="700" letter-spacing="-0.4" fill="#141412">Hallo</text>
