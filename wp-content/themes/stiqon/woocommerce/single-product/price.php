<?php
/**
 * The price. For a product with options this is the price of the current
 * choice – not the range from cheapest to dearest – so the page says from its
 * first moment what the visitor would pay. WooCommerce's and Germanized's
 * scripts replace it when the choice changes.
 *
 * Without the page's script nothing follows a change of choice. There, a
 * product whose options cost differently shows WooCommerce's range instead
 * (data-needs-script and data-without-script: see header.php; an inline style,
 * not the hidden attribute, which Tailwind's base styles keep hidden with
 * !important); the script takes the range out of the page as it starts.
 *
 * @version 3.0.0
 */
defined('ABSPATH') || exit;

global $product;

$priced = stiqon_product_choice($product)['variation'] ?? $product;
?>
<p class="<?php echo esc_attr(apply_filters('woocommerce_product_price_class', 'price')); ?>"><?php if (stiqon_price_depends_on_choice($product)) : ?><span data-needs-script><?php echo $priced->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- price markup from WooCommerce ?></span><span data-without-script style="display: none"><?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- price markup from WooCommerce ?></span><?php else : ?><?php echo $priced->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- price markup from WooCommerce ?><?php endif; ?></p>
