<?php

namespace Stiqon\Shop\Setup;

/**
 * Settings the shop starts with. They are applied once, when the plugin is
 * activated; all of them stay changeable in the administration afterwards.
 */
final class Defaults
{
    /**
     * Each of these switches off something that would send visitors' data to
     * another company or set cookies nobody asked for.
     */
    public const PRIVACY = [
        // Profile pictures come from gravatar.com: a request to a third party for every page that shows one.
        'show_avatars' => '0',
        // The shop has no blog, so nothing can be commented on or pinged.
        'default_comment_status' => 'closed',
        'default_ping_status' => 'closed',
        // WooCommerce's order attribution sets tracking cookies (sbjs_*) on a visitor's first page.
        'woocommerce_feature_order_attribution_enabled' => 'no',
        // No usage data to WooCommerce, no adverts for extensions in the administration.
        'woocommerce_allow_tracking' => 'no',
        'woocommerce_show_marketplace_suggestions' => 'no',
    ];

    public static function apply(): void
    {
        foreach (self::PRIVACY as $option => $value) {
            update_option($option, $value);
        }
    }
}
