<?php
/**
 * Sample motif, drawn in a 300 x 100 box (3 : 1); essentials stay within x 58–242 and y 24–76,
 * which 2 : 1 and 4 : 1 keep clear of the hem and its eyelets.
 * Building site: what is being built, in large type, beside the houses to come.
 *
 * @var string $uid  prefix for ids, unique on the page
 */

defined('ABSPATH') || exit;
?>
<rect width="300" height="100" fill="#efe6d8" />
<?php // The houses to come, on a strip of ground ?>
<circle cx="174" cy="80" r="9" fill="#1f3d2e" fill-opacity="0.7" />
<rect x="180" y="32" width="28" height="58" fill="#1f3d2e" />
<rect x="208" y="48" width="38" height="42" fill="#457b9d" />
<path d="M246 90V54l24-14 30 16v34z" fill="#c8553d" />
<g fill="#efe6d8">
    <?php foreach ([38, 48, 58, 68, 78] as $top) : ?>
        <rect x="186" y="<?php echo esc_attr($top); ?>" width="6" height="5" />
        <rect x="197" y="<?php echo esc_attr($top); ?>" width="6" height="5" />
    <?php endforeach; ?>
    <?php foreach ([54, 64, 74] as $top) : ?>
        <rect x="214" y="<?php echo esc_attr($top); ?>" width="8" height="5" />
        <rect x="232" y="<?php echo esc_attr($top); ?>" width="8" height="5" />
    <?php endforeach; ?>
    <rect x="262" y="60" width="9" height="8" />
    <rect x="278" y="60" width="9" height="8" />
</g>
<rect y="88" width="300" height="12" fill="#1f3d2e" />
<?php // The builder's mark, and what is being built ?>
<circle cx="65" cy="33.5" r="7.5" fill="#1f3d2e" />
<path d="M60.4 35.2l4.6-4.2 4.6 4.2v3.9h-3v-2.5h-3.2v2.5h-3z" fill="#efe6d8" />
<g font-size="5" font-weight="700" letter-spacing="1.5" fill="#1f3d2e">
    <text x="77" y="32.4">WOHNQUARTIER</text>
    <text x="77" y="39">LINDENHOF</text>
</g>
<text x="58" y="55" font-size="6.4" font-weight="700" letter-spacing="2.2" fill="#c8553d">HIER ENTSTEHEN</text>
<text x="57" y="72" font-size="16" font-weight="700" letter-spacing="-0.5" fill="#1d1d1b">24 Wohnungen</text>
