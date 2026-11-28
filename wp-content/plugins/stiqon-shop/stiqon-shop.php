<?php
/**
 * Plugin Name: Stiqon Shop-Funktionen
 * Description: Gezeichnete Produktansichten, Motiv-Vorschau und weitere Funktionen des Stiqon-Shops.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * Text Domain: stiqon-shop
 */

defined('ABSPATH') || exit;

require __DIR__.'/src/autoload.php';
require __DIR__.'/src/helpers.php';

Stiqon\Shop\Plugin::boot(__FILE__);
