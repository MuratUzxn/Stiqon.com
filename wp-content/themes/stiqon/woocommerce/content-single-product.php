<?php
/**
 * The product page: the product drawn large on a grey surface and, beside it,
 * what WooCommerce and its extensions say about it – title, price, options,
 * the button. Laid out after mobbin.com's pages of product configurators.
 *
 * Replaces WooCommerce's template of the same name. Every hook of the original
 * is fired, in the original order; what is hooked where is arranged in
 * inc/shop.php.
 *
 * @version 3.6.0
 */

defined('ABSPATH') || exit;

global $product;

do_action('woocommerce_before_single_product');

if (post_password_required()) {
    echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- form markup from WordPress

    return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product); ?> x-data="productOptions">
    <div class="grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:gap-14">
        <div>
            <?php do_action('woocommerce_before_single_product_summary'); ?>
        </div>

        <div class="summary entry-summary lg:py-4">
            <?php do_action('woocommerce_single_product_summary'); ?>
        </div>
    </div>

    <?php do_action('woocommerce_after_single_product_summary'); ?>
</div>

<?php do_action('woocommerce_after_single_product'); ?>
