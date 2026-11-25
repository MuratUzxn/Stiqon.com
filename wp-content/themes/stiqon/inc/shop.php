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
 * stylesheets would fight it rule by rule. Cart, checkout and account keep
 * them until they get templates of their own.
 */
add_filter('woocommerce_enqueue_styles', static fn ($styles) => function_exists('is_product') && is_product() ? [] : $styles);

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
