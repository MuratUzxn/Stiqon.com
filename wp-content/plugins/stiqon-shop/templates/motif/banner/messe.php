<?php
/**
 * Sample motif, drawn in a 300 x 100 box (3 : 1); essentials stay within x 58–242 and y 24–76,
 * which 2 : 1 and 4 : 1 keep clear of the hem and its eyelets.
 * Trade fair: a maker's mark beside the dates and the stand.
 *
 * @var string $uid  prefix for ids, unique on the page
 */

defined('ABSPATH') || exit;
?>
<rect width="300" height="100" fill="#1d3557" />
<g stroke="#f4efe6" stroke-opacity="0.2" stroke-width="0.8">
    <path d="M0 30h34M0 42h26M0 54h38M266 46h34M274 58h26M262 70h38" />
</g>
<circle cx="76" cy="50" r="16" fill="#d9a441" />
<path d="M76 37.5l4.8 12.5h-9.6z" fill="#1d3557" />
<path d="M76 62.5l4.8-12.5h-9.6z" fill="#1d3557" fill-opacity="0.35" />
<text x="104" y="38" font-size="11" fill="#f4efe6" style="font-family:var(--font-accent);font-style:italic">Atelier Nord</text>
<text x="103" y="60" font-size="20" font-weight="700" letter-spacing="-0.6" fill="#f4efe6">12.–14. März</text>
<text x="104" y="71" font-size="5.4" font-weight="700" letter-spacing="1.8" fill="#d9a441">HALLE 3 · STAND B12</text>
