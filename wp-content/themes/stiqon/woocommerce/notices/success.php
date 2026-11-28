<?php
/**
 * Messages from WooCommerce ("… wurde Ihrem Warenkorb hinzugefügt"), one grey
 * card each: the sentence, and after it the button that belongs to it.
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
        <?php echo stiqon_notice_content(wc_kses_notice($notice['notice'])); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- passed through wc_kses_notice() ?>
    </div>
<?php endforeach; ?>
