<?php
/**
 * Sample motif, drawn in a 100 x 64.7 box (business card proportion 85 : 55); essentials stay in the central area.
 * Practice: two leaves and a left-aligned name; the green band on the right may be cropped.
 *
 * @var string $uid  prefix for ids, unique on the page
 */

defined('ABSPATH') || exit;
?>
<rect width="100" height="64.7" fill="#ffffff" />
<rect x="86" width="14" height="64.7" fill="#1f3d2e" />
<circle cx="93" cy="50" r="2.2" fill="#d9a441" />
<path d="M26 26.4c-4.6-2.6-6-7.6-3.6-13 4.8 1.6 6.6 7.2 3.6 13z" fill="#1f3d2e" />
<path d="M26.6 26.4c.4-5 3.4-8.4 8-9.2-.2 4.8-3.2 8-8 9.2z" fill="#d9a441" />
<text x="22" y="35.5" font-size="6.6" font-weight="700" letter-spacing="-0.2" fill="#1f3d2e">Praxis am Park</text>
<text x="22" y="40.8" font-size="2.7" font-weight="700" letter-spacing="1.1" fill="#1f3d2e">PHYSIOTHERAPIE</text>
<g fill="#1f3d2e" fill-opacity="0.42">
    <rect x="22" y="46.2" width="30" height="1.5" rx="0.75" />
    <rect x="22" y="49.4" width="24" height="1.5" rx="0.75" />
    <rect x="22" y="52.6" width="27" height="1.5" rx="0.75" />
</g>
