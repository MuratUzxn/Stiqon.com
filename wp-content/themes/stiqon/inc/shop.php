<?php
/**
 * How the theme presents WooCommerce's pages.
 *
 * The product page keeps WooCommerce's template and hook structure: the files
 * under woocommerce/ replace single templates, and the functions here take the
 * places of single hooked functions. Everything else stays where WooCommerce
 * puts it, so extensions (Germanized, a supplier's plugin, payment buttons)
 * find the hooks they expect.
 */

defined('ABSPATH') || exit;

/**
 * On product pages the theme styles everything itself; WooCommerce's
 * stylesheets would fight it rule by rule. The same goes for cart, checkout
 * and account, see stiqon_declare_shop_page().
 */
add_filter('woocommerce_enqueue_styles', static fn ($styles) => function_exists('is_product') && is_product() ? [] : $styles);

/**
 * Whether the page being shown is one WooCommerce draws inside a page of the
 * site: cart, checkout (with the order confirmation), account – and the page
 * with Germanized's form for withdrawing from a contract, which is built from
 * the same form markup.
 */
function stiqon_is_woocommerce_page(): bool
{
    if (! function_exists('is_cart')) {
        return false;
    }

    return is_cart() || is_checkout() || is_account_page()
        || (function_exists('eu_owb_get_withdrawal_page_id') && is_page() && get_queried_object_id() === (int) eu_owb_get_withdrawal_page_id());
}

/**
 * The inside of a message from WooCommerce, laid out as the shop shows it: the
 * sentence – as one piece, however many links and bold words it holds – and
 * beside it the button that belongs to it ("Warenkorb anzeigen"), which
 * WooCommerce prints behind its sentence, or in front of it.
 */
function stiqon_notice_content(string $html): string
{
    $button = '<a\b[^>]*\bclass="[^"]*\bbutton\b[^"]*"[^>]*>(?:(?!</a>).)*</a>';
    $action = '';

    if (preg_match('~^\s*('.$button.')(.*)$~s', $html, $parts)) {
        [, $action, $html] = $parts;
    } elseif (preg_match('~^(.*?)('.$button.')\s*$~s', $html, $parts)) {
        [, $html, $action] = $parts;
    }

    return '<span class="min-w-0 flex-1">'.trim($html).'</span>'.$action;
}

/**
 * "×" as the way to take something out of the cart is small and says little.
 * The link keeps everything WooCommerce's script looks for and gets a word.
 */
add_filter('woocommerce_cart_item_remove_link', static function (mixed $link): mixed {
    return is_string($link) ? str_replace('>&times;</a>', '>Entfernen</a>', $link) : $link;
});

/**
 * The row of the sums that names the way of shipping. WooCommerce heads it
 * "Sendung", thinking of an order that leaves in several parcels; for the one
 * parcel an order of this shop is, the word customers look for is "Versand".
 */
add_filter('woocommerce_shipping_package_name', static function (mixed $name, mixed $index = 0): mixed {
    return (int) $index === 0 && count(WC()->shipping()->get_packages()) < 2 ? 'Versand' : $name;
}, 10, 2);

/**
 * Below the way of shipping the cart adds that the options "will be updated
 * during checkout" – before an address is known, other countries may cost
 * otherwise. A shop that delivers to one country has nothing to add there.
 * The marker lets the stylesheet take the empty line away.
 */
add_filter('woocommerce_shipping_estimate_html', static function (mixed $html): mixed {
    return count(WC()->countries->get_shipping_countries()) === 1 ? '<span class="stiqon-nothing-to-add"></span>' : $html;
});

/**
 * The line at the checkout that opens the field for a voucher. WooCommerce
 * gives its link one text to read ("Klicken Sie hier, um …") and another one
 * for screen readers; whoever uses both – speaking to the computer, for
 * instance – cannot name what they see. One text, which says what the link does.
 */
add_filter('woocommerce_checkout_coupon_message', static function (): string {
    return 'Haben Sie einen Gutschein? <a href="#" role="button" aria-controls="woocommerce-checkout-form-coupon" aria-expanded="false" class="showcoupon">Gutscheincode eingeben</a>';
});

/**
 * The account's menu. Nothing sold here is a download, and the first page is
 * an overview, whatever software calls it.
 */
add_filter('woocommerce_account_menu_items', static function (mixed $items): mixed {
    if (! is_array($items)) {
        return $items;
    }

    unset($items['downloads']);

    if (isset($items['dashboard'])) {
        $items['dashboard'] = 'Übersicht';
    }

    return $items;
});

/**
 * From an empty cart WooCommerce leads "back to the shop" – its list page,
 * which this shop does not have. The way back is the home page with both areas.
 */
add_filter('woocommerce_return_to_shop_redirect', static fn (): string => home_url('/'));
add_filter('woocommerce_return_to_shop_text', static fn (): string => 'Sticker und Flyer ansehen');

add_action('init', function (): void {
    // The drawing takes the place of product photos.
    remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10);
    remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
    add_action('woocommerce_before_single_product_summary', 'stiqon_product_stage', 20);

    // Beside it: where the product belongs, then WooCommerce's title, price, summary and form.
    add_action('woocommerce_single_product_summary', 'stiqon_product_path', 4);
    add_action('woocommerce_single_product_summary', 'stiqon_product_net_price', 15);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);

    // Below: the description as plain text instead of tabs, and the area's other products.
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
    add_action('woocommerce_after_single_product_summary', 'stiqon_product_description', 10);
    add_action('woocommerce_after_single_product_summary', 'stiqon_product_siblings', 20);
});

/**
 * WooCommerce has list pages of its own: the shop page, one per category, tag
 * and option. The shop's lists are the area pages, so those addresses lead
 * there – a category to its area, everything else to the home page, which
 * shows both areas. A search for products goes to the shop's one search
 * (inc/search.php).
 */
add_action('template_redirect', function (): void {
    if (! function_exists('is_shop') || is_search()) {
        return;
    }

    if (is_product_category()) {
        $category = get_queried_object();
        $slug = $category instanceof WP_Term ? $category->slug : '';

        wp_safe_redirect(isset(stiqon_required_pages()[$slug]) ? stiqon_page_url($slug) : home_url('/'), 301);
        exit;
    }

    if (is_shop() || is_product_taxonomy()) {
        wp_safe_redirect(home_url('/'), 302);
        exit;
    }
});

/**
 * The area a product belongs to: the first of its categories that the theme
 * has an area page for.
 *
 * @return array{slug: string, label: string, url: string}|null
 */
function stiqon_product_area(WC_Product $product): ?array
{
    // The product's categories and everything above them: an area page also
    // shows what is filed under a sub-category of its category.
    $categories = array_map('intval', wc_get_product_cat_ids($product->get_id()));

    foreach (stiqon_required_pages() as $slug => $label) {
        $category = get_term_by('slug', $slug, 'product_cat');

        if ($category instanceof WP_Term && in_array($category->term_id, $categories, true)) {
            return ['slug' => $slug, 'label' => $label, 'url' => stiqon_page_url($slug)];
        }
    }

    return null;
}

/**
 * The product, drawn large. One drawing per value of the option that can be
 * seen (a sticker's material); the one for the current choice is shown, and the
 * page's script switches between them when another is chosen.
 */
function stiqon_product_stage(): void
{
    global $product;

    $views = stiqon_product_views($product);

    if ($views['views'] === []) {
        return;
    }

    $chosen = $views['key'] !== null ? (stiqon_product_choice($product)['chosen'][$views['key']] ?? null) : null;
    $values = array_column($views['views'], 'value');
    $shown = in_array($chosen, $values, true) ? $chosen : $values[0];

    echo '<div class="lg:sticky lg:top-8">';
    echo '<div class="relative aspect-square overflow-hidden rounded-tile bg-mist" data-product-stage'.stiqon_attributes(['data-option' => $views['key']]).'>';

    foreach ($views['views'] as $view) {
        echo '<div class="absolute inset-0 flex items-center justify-center p-[9%]"'.stiqon_attributes(['data-view' => $view['value'], 'hidden' => $view['value'] !== $shown]).'>'
            .$view['html'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- finished markup from the plugin
            .'</div>';
    }

    echo '</div>';
    echo '<div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2" data-needs-script>'
        .stiqon_motif_button('Eigenes Motiv ansehen', ['variant' => 'secondary']) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        .'<p class="text-label text-muted">Ihr Bild bleibt in Ihrem Browser.</p>'
        .'</div>';
    echo '</div>';
}

/**
 * Where the product belongs: Start › Sticker.
 */
function stiqon_product_path(): void
{
    global $product;

    $area = stiqon_product_area($product);

    echo '<nav aria-label="Pfad" class="text-label text-muted"><ol class="flex flex-wrap items-center gap-1.5">';
    echo '<li><a href="'.esc_url(home_url('/')).'" class="rounded-sm transition-colors hover:text-ink">Start</a></li>';

    if ($area !== null) {
        echo '<li aria-hidden="true">›</li><li><a href="'.esc_url($area['url']).'" class="rounded-sm transition-colors hover:text-ink">'.esc_html($area['label']).'</a></li>';
    }

    echo '</ol></nav>';
}

/**
 * The price without VAT, small, for business customers. Shown where it differs
 * from the price above; the page's script keeps it in step with the choice.
 */
function stiqon_product_net_price(): void
{
    global $product;

    $priced = stiqon_product_choice($product)['variation'] ?? $product;
    $net = stiqon_net_price_text($priced);

    echo '<p class="text-label mt-1 text-muted" data-net-price'.($net === '' ? ' hidden' : '').'>'.wp_kses_post($net).'</p>';
}

/**
 * "20,92 € netto" – or nothing for a product without a price, or one whose
 * displayed price is the net price already.
 */
function stiqon_net_price_text(WC_Product $product): string
{
    if ($product->get_price() === '') {
        return '';
    }

    $net = (float) wc_get_price_excluding_tax($product);
    $shown = (float) wc_get_price_to_display($product);

    return abs($shown - $net) < 0.005 ? '' : wc_price($net).' netto';
}

/**
 * Each variation carries its net price text, for the script that follows the choice.
 */
add_filter('woocommerce_available_variation', function (array $data, $product, $variation): array {
    $data['stiqon_net_price'] = stiqon_net_price_text($variation);

    return $data;
}, 10, 3);

/**
 * The description, as written in the product editor.
 */
function stiqon_product_description(): void
{
    global $product;

    $description = (string) get_the_content();

    if (trim($description) === '' && ! $product->get_meta('_stiqon_example')) {
        return;
    }

    echo '<section class="mt-16 max-w-2xl lg:mt-24" aria-labelledby="beschreibung">';
    echo '<h2 id="beschreibung" class="text-heading">Beschreibung</h2>';
    echo '<div class="prose mt-3 text-muted">';
    the_content();
    echo '</div>';

    if ($product->get_meta('_stiqon_example')) {
        echo '<p class="text-label mt-6 text-muted">Beispielprodukt mit Beispielpreisen. Auswahl und Preise kommen später vom Drucklieferanten.</p>';
    }

    echo '</section>';
}

/**
 * The other products of the same area, each on its first material or paper.
 */
function stiqon_product_siblings(): void
{
    global $product;

    $area = stiqon_product_area($product);

    if ($area === null) {
        return;
    }

    $cards = stiqon_products_beside($product, $area['slug'], 4);

    if ($cards === []) {
        return;
    }

    echo '<section class="mt-16 lg:mt-24" aria-labelledby="weitere">';
    echo '<h2 id="weitere" class="text-title">Weitere '.esc_html($area['label']).'</h2>';
    echo '<ul class="mt-8 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-4 md:grid-cols-3 lg:grid-cols-4">';

    foreach ($cards as $card) {
        echo stiqon_product_card($card); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- finished markup
    }

    echo '</ul></section>';
}

/**
 * The options of a product as groups of choices, in the order WooCommerce shows
 * them, with the chosen value of each.
 *
 * @param  array<string, list<string>>  $attributes  as WooCommerce hands them to its variation form
 * @return list<array{key: string, name: string, label: string, chosen: string, values: array<string, string>}>
 */
function stiqon_product_option_groups(WC_Product $product, array $attributes): array
{
    $choice = stiqon_product_choice($product);
    $chosen = $choice['chosen'];
    $groups = [];

    foreach ($attributes as $attribute => $offered) {
        $key = sanitize_title($attribute);
        $name = 'attribute_'.$key;

        // WooCommerce lists every value a variation uses, also of variations that
        // are switched off or have no price. The plugin knows what can be bought.
        if (is_array($choice['offered'] ?? null)) {
            $offered = array_values(array_intersect(array_map('strval', $offered), $choice['offered'][$key] ?? []));
        }

        // value => what is shown for it, in the order set for the attribute.
        $values = [];

        if (taxonomy_exists($attribute)) {
            foreach (wc_get_product_terms($product->get_id(), $attribute, ['fields' => 'all']) as $term) {
                if (in_array($term->slug, $offered, true)) {
                    $values[$term->slug] = $term->name;
                }
            }
        } else {
            foreach ($offered as $text) {
                $values[(string) $text] = (string) $text;
            }
        }

        if ($values === []) {
            continue;
        }

        // Without the plugin: what the request asks for, what the product preselects, the first value.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only compared with the product's own values.
        $wanted = [$chosen[$name] ?? null, isset($_REQUEST[$name]) && is_string($_REQUEST[$name]) ? wp_unslash($_REQUEST[$name]) : null, $product->get_variation_default_attribute($attribute)];
        $selected = (string) array_key_first($values);

        foreach ($wanted as $value) {
            if (is_string($value) && isset($values[$value])) {
                $selected = $value;

                break;
            }
        }

        $groups[] = ['key' => $key, 'name' => $name, 'label' => wc_attribute_label($attribute, $product), 'chosen' => $selected, 'values' => $values];
    }

    return $groups;
}

/**
 * In the cart, at the checkout and wherever else WooCommerce shows a small
 * picture of what is being bought, the shop shows the drawing of exactly that
 * variant. In the cart WooCommerce makes it a link to the product, which the
 * drawing's own description names; at the checkout it is decoration beside
 * the name.
 */
add_filter('woocommerce_cart_item_thumbnail', static function (mixed $thumbnail, mixed $item): mixed {
    $product = is_array($item) ? ($item['data'] ?? null) : null;
    $view = $product instanceof WC_Product ? stiqon_product_view($product) : '';

    if ($view === '') {
        return $thumbnail;
    }

    // At the checkout Germanized sets the picture beside the name, and WooCommerce
    // prints both through the filter for editor content – which takes a drawing
    // apart and leaves the lettering of its motif. A marker passes; the drawing
    // itself follows where the number is printed, see below.
    if (is_checkout()) {
        return '<span class="stiqon-drawing-follows"></span>';
    }

    return '<span class="block w-20 rounded-control bg-mist p-2">'.$view.'</span>';
}, 10, 2);

/**
 * The drawing of each row of the order at the checkout. Before Germanized closes
 * the wrapper it puts around name and picture (priority 1), so that the drawing
 * ends up inside it.
 */
add_filter('woocommerce_checkout_cart_item_quantity', static function (mixed $html, mixed $item): mixed {
    $product = is_array($item) ? ($item['data'] ?? null) : null;
    $view = $product instanceof WC_Product ? stiqon_product_view($product) : '';

    return $view === '' || ! is_string($html) ? $html : $html.'<span class="stiqon-order-drawing" aria-hidden="true">'.$view.'</span>';
}, 0, 2);

/**
 * E-mails carry the shop's logo in their head – unless the owner chose a
 * picture of their own (WooCommerce › Einstellungen › E-Mails). The address is
 * worked out for every mail, so it stays right when the shop moves.
 */
add_filter('option_woocommerce_email_header_image', 'stiqon_email_logo');
add_filter('default_option_woocommerce_email_header_image', 'stiqon_email_logo');

function stiqon_email_logo(mixed $image): mixed
{
    return is_string($image) && $image !== '' ? $image : get_theme_file_uri('assets/brand/logo-mail-320.png');
}

/**
 * WooCommerce puts a small picture of each product into its e-mails. The
 * products of this shop are drawn, not photographed, and a drawing cannot be
 * sent in a mail – what would show is WooCommerce's grey stand-in. The rows
 * do without.
 */
add_filter('woocommerce_email_order_items_args', static function (mixed $args): mixed {
    if (is_array($args)) {
        $args['show_image'] = false;
    }

    return $args;
});

/**
 * Germanized prints the button for withdrawing from a contract after the
 * footer, on shop pages. This theme has it inside the footer, on every page
 * (footer.php), so the second one is taken away.
 */
add_action('wp_loaded', static function (): void {
    remove_action('wp_footer', ['Vendidero\\OrderWithdrawalButton\\Package', 'print_button'], 5);
});
