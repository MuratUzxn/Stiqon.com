<?php
/**
 * Quantity and the button of a product with variations.
 *
 * @version 10.5.2
 */

defined('ABSPATH') || exit;

global $product;
?>
<div class="woocommerce-variation-add-to-cart variations_button flex flex-wrap items-center gap-3">
    <?php do_action('woocommerce_before_add_to_cart_button'); ?>

    <?php
    do_action('woocommerce_before_add_to_cart_quantity');

    woocommerce_quantity_input([
        'min_value' => $product->get_min_purchase_quantity(),
        'max_value' => $product->get_max_purchase_quantity(),
        'input_value' => isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : $product->get_min_purchase_quantity(), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- repopulates the field; wc_stock_amount() normalises it
    ]);

    do_action('woocommerce_after_add_to_cart_quantity');
    ?>

    <button type="submit" class="single_add_to_cart_button button alt text-action inline-flex h-12 grow items-center justify-center rounded-full bg-ink px-6 text-paper transition-colors duration-200 ease-soft hover:bg-ink-soft sm:grow-0 sm:min-w-64"><?php echo esc_html($product->single_add_to_cart_text()); ?></button>

    <?php do_action('woocommerce_after_add_to_cart_button'); ?>

    <input type="hidden" name="add-to-cart" value="<?php echo absint($product->get_id()); ?>">
    <input type="hidden" name="product_id" value="<?php echo absint($product->get_id()); ?>">
    <input type="hidden" name="variation_id" class="variation_id" value="0">
</div>
