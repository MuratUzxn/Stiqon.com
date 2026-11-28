<?php
/**
 * Stiqon theme.
 *
 * Presentation only: everything that should survive a change of theme (mockup
 * drawing, catalogue logic, security settings) lives in the plugin
 * "Stiqon Shop-Funktionen".
 */

defined('ABSPATH') || exit;

require __DIR__.'/inc/setup.php';
require __DIR__.'/inc/assets.php';
require __DIR__.'/inc/components.php';
require __DIR__.'/inc/site.php';
require __DIR__.'/inc/cleanup.php';
require __DIR__.'/inc/plugin-missing.php';
require __DIR__.'/inc/checks.php';
require __DIR__.'/inc/shop.php';
require __DIR__.'/inc/search.php';
require __DIR__.'/inc/announcement.php';
