<?php
/**
 * Messages from WooCommerce ("… wurde Ihrem Warenkorb hinzugefügt"), one grey
 * card each.
 *
 * @version 8.6.0
 */

defined('ABSPATH') || exit;

if (! $notices) {
    return;
}
?>
<?php foreach ($notices as $notice) : ?>
    <div class="woocommerce-message shop-notice"<?php echo wc_get_notice_data_attr($notice); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> role="alert">
        <?php echo wc_kses_notice($notice['notice']); ?>
    </div>
<?php endforeach; ?>
