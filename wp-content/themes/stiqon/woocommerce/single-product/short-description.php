<?php
/**
 * The product in one or two sentences.
 *
 * @version 3.3.0
 */

defined('ABSPATH') || exit;

global $post;

$short_description = apply_filters('woocommerce_short_description', $post->post_excerpt);

if (! $short_description) {
    return;
}
?>
<div class="woocommerce-product-details__short-description text-lead mt-5 text-muted">
    <?php echo $short_description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered by WooCommerce; may carry markup from extensions ?>
</div>
