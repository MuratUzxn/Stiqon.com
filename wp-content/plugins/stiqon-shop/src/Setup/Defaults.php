<?php

namespace Stiqon\Shop\Setup;

/**
 * Settings the shop starts with. Each is applied once, the first time the
 * plugin is activated after it was added here. All of them stay changeable in
 * the administration, and switching the plugin off and on again does not undo
 * such a change.
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
        // The shop has no blog: new content is closed to comments and pings.
        'default_comment_status' => 'closed',
        'default_ping_status' => 'closed',
        // WooCommerce's order attribution sets tracking cookies (sbjs_*) on a visitor's first page.
        'woocommerce_feature_order_attribution_enabled' => 'no',
        // No usage data to WooCommerce, no adverts for extensions in the administration.
        'woocommerce_allow_tracking' => 'no',
        'woocommerce_show_marketplace_suggestions' => 'no',
    ];

    /** Names of the settings that have been applied, so none is applied twice. */
    public const APPLIED = 'stiqon_shop_defaults_applied';

    public static function apply(): void
    {
        $applied = array_values(array_filter((array) get_option(self::APPLIED, []), 'is_string'));

        foreach (self::PRIVACY as $option => $value) {
            if (! in_array($option, $applied, true)) {
                update_option($option, $value);

                $applied[] = $option;
            }
        }

        update_option(self::APPLIED, $applied, false);
    }
}
