<?php
/**
 * What went wrong, as one grey card with a line per message.
 *
 * It stays the list WooCommerce prints: its checkout script turns each line
 * that belongs to a field into a link to that field, and repeats the message
 * below the field.
 *
 * @version 8.6.0
 */

defined('ABSPATH') || exit;

if (! $notices) {
    return;
}
?>
<ul class="woocommerce-error shop-notice shop-notice--list" role="alert">
    <?php foreach ($notices as $notice) : ?>
        <li<?php echo wc_get_notice_data_attr($notice); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo stiqon_notice_content(wc_kses_notice($notice['notice'])); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- passed through wc_kses_notice() ?></li>
    <?php endforeach; ?>
</ul>
