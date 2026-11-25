<?php
/**
 * The price. For a product with options this is the price of the current
 * choice – not the range from cheapest to dearest – so the page says from its
 * first moment what the visitor would pay. WooCommerce's and Germanized's
 * scripts replace it when the choice changes.
 *
 * @version 3.0.0
 */

defined('ABSPATH') || exit;

global $product;

$priced = stiqon_product_choice($product)['variation'] ?? $product;
?>
<p class="<?php echo esc_attr(apply_filters('woocommerce_product_price_class', 'price')); ?>"><?php echo $priced->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- price markup from WooCommerce ?></p>
