<?php
/**
 * The options of a product and the button, for a product with variations.
 *
 * Each option is a group of radio buttons drawn as pills. They are the form's
 * real fields: the form works without script, and the plugin finds the
 * variation for what was chosen.
 *
 * WooCommerce's own script (price of the choice, which combinations exist)
 * reads select elements. One per option is kept for it – never shown, and
 * without a name, so it is not sent with the form. The page's script passes a
 * choice made on the pills on to it.
 *
 * @version 11.1.0
 */

defined('ABSPATH') || exit;

global $product;

$variations_json = wp_json_encode($available_variations);
$variations_attr = function_exists('wc_esc_json') ? wc_esc_json($variations_json) : _wp_specialchars($variations_json, ENT_QUOTES, 'UTF-8', true);
$groups = stiqon_product_option_groups($product, $attributes);

do_action('woocommerce_before_add_to_cart_form'); ?>

<form class="variations_form cart mt-8" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype="multipart/form-data" data-product_id="<?php echo absint($product->get_id()); ?>" data-product_variations="<?php echo $variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped for an attribute above ?>">
    <?php do_action('woocommerce_before_variations_form'); ?>

    <?php if (empty($available_variations) && false !== $available_variations) : ?>
        <p class="stock out-of-stock rounded-card bg-mist px-5 py-4 text-muted"><?php echo esc_html(apply_filters('woocommerce_out_of_stock_message', __('This product is currently out of stock and unavailable.', 'woocommerce'))); ?></p>
    <?php else : ?>
        <div class="variations grid gap-6">
            <?php foreach ($groups as $group) : ?>
                <fieldset>
                    <legend class="text-label text-muted"><?php echo esc_html($group['label']); ?></legend>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <?php foreach ($group['values'] as $value => $label) : ?>
                            <label class="relative">
                                <input
                                    type="radio" class="peer absolute inset-0 cursor-pointer opacity-0 disabled:cursor-default"
                                    name="<?php echo esc_attr($group['name']); ?>" value="<?php echo esc_attr($value); ?>"
                                    data-select="<?php echo esc_attr($group['key']); ?>" <?php checked($group['chosen'], (string) $value); ?>
                                >
                                <span class="text-action inline-flex h-11 items-center rounded-full bg-ink/8 px-4 transition-colors duration-200 ease-soft peer-checked:bg-ink peer-checked:text-paper peer-hover:bg-ink/14 peer-checked:peer-hover:bg-ink peer-focus-visible:outline-2 peer-focus-visible:outline-offset-3 peer-focus-visible:outline-ink peer-disabled:text-muted peer-disabled:line-through peer-disabled:opacity-60">
                                    <?php echo esc_html($label); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <select id="<?php echo esc_attr($group['key']); ?>" data-attribute_name="<?php echo esc_attr($group['name']); ?>" data-show_option_none="yes" hidden>
                        <option value=""></option>
                        <?php foreach ($group['values'] as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($group['chosen'], (string) $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>
            <?php endforeach; ?>
        </div>

        <div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>

        <?php do_action('woocommerce_after_variations_table'); ?>

        <div class="single_variation_wrap mt-8">
            <?php
            do_action('woocommerce_before_single_variation');
            do_action('woocommerce_single_variation');
            do_action('woocommerce_after_single_variation');
            ?>
        </div>
    <?php endif; ?>

    <?php do_action('woocommerce_after_variations_form'); ?>
</form>

<?php
do_action('woocommerce_after_add_to_cart_form');
