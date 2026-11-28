<?php
/**
 * Sample motif, drawn in a 100 x 64.7 box (business card proportion 85 : 55); essentials stay in the central area.
 * Law office: an initial in a ring, a name in the serif italic, a fine gold rule.
 *
 * @var string $uid  prefix for ids, unique on the page
 */

defined('ABSPATH') || exit;
?>
<rect width="100" height="64.7" fill="#1d3557" />
<circle cx="50" cy="18.5" r="6.2" fill="none" stroke="#d9a441" stroke-width="0.6" />
<text x="50" y="21.8" text-anchor="middle" font-size="9.4" fill="#d9a441" style="font-family:var(--font-accent);font-style:italic">B</text>
<text x="50" y="35.6" text-anchor="middle" font-size="9.2" fill="#f4efe6" style="font-family:var(--font-accent);font-style:italic">Dr. Lena Berger</text>
<text x="50" y="41.2" text-anchor="middle" font-size="2.8" font-weight="700" letter-spacing="1.5" fill="#d9a441">RECHTSANWÄLTIN</text>
<path d="M45 44.6h10" stroke="#d9a441" stroke-width="0.35" />
<g fill="#f4efe6" fill-opacity="0.5">
    <rect x="35" y="48.4" width="30" height="1.5" rx="0.75" />
    <rect x="38" y="51.6" width="24" height="1.5" rx="0.75" />
</g>
