<?php

namespace Stiqon\Shop\Admin;

use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\ProductView;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Kinds;
use Stiqon\Shop\Mockup\Sticker;
use WC_Product;

/**
 * The tab "Vorschau" of a product in the administration: how the shop draws
 * the product, what it made out by itself, and fields to say otherwise.
 *
 * The shop draws its products instead of photographing them. What kind of
 * product one is, its shape or format and the sample motif on it are read
 * from its name and category; what is chosen here is stored on the product
 * (meta "_stiqon_mockup", see Catalog\ProductView) and comes first.
 * "Automatisch" stores nothing.
 */
final class ProductPreview
{
    public const FIELD = 'stiqon_mockup';

    private const PANEL = 'stiqon_preview_product_data';

    /**
     * Names of the fields for a kind's form that came before there were more
     * kinds than stickers and flyers. Every other kind sends "<type>_form".
     */
    private const FORM_FIELDS = ['sticker' => 'shape', 'flyer' => 'format'];

    public static function register(): void
    {
        add_filter('woocommerce_product_data_tabs', [self::class, 'tab']);
        add_action('woocommerce_product_data_panels', [self::class, 'panel']);
        // WooCommerce runs this while it saves the product form, after it has
        // checked the form's token and the right to edit the product.
        add_action('woocommerce_admin_process_product_object', [self::class, 'save']);
    }

    /**
     * @param  array<string, array<string, mixed>>  $tabs
     * @return array<string, array<string, mixed>>
     */
    public static function tab(array $tabs): array
    {
        $tabs['stiqon_preview'] = ['label' => 'Vorschau', 'target' => self::PANEL, 'class' => [], 'priority' => 65];

        return $tabs;
    }

    /**
     * What to store for a product, from what the fields of the tab sent. Only
     * values the drawings know are kept; an empty value means "automatic".
     *
     * @param  array<string, string>  $sent  type, and for each kind its form ("shape" for stickers, "format" for flyers, "<type>_form" otherwise) and "<type>_motif"
     * @param  list<string>  $categories  names of the product's categories
     * @return array<string, string>
     */
    public static function chosen(array $sent, string $name, array $categories = []): array
    {
        $chosen = is_string($sent['type'] ?? null) && Kinds::has($sent['type']) ? ['type' => $sent['type']] : [];
        $type = $chosen['type'] ?? Guess::type($name, ...$categories);

        if ($type === null) {
            return $chosen;
        }

        $wanted = [
            Kinds::of($type)::form()['key'] => $sent[self::formField($type)] ?? '',
            'motif' => $sent[$type.'_motif'] ?? '',
        ];

        return $chosen + Spec::known($type, array_filter($wanted, 'strlen'));
    }

    public static function save(WC_Product $product): void
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- verified by WooCommerce, see register().
        if (! isset($_POST[self::FIELD]) || ! is_array($_POST[self::FIELD])) {
            return;
        }

        $sent = array_map('sanitize_key', array_filter(wp_unslash($_POST[self::FIELD]), 'is_string'));
        // phpcs:enable

        $stored = $product->get_meta(ProductView::META);
        $kept = array_diff_key(is_array($stored) ? $stored : [], array_flip(self::decided()));
        $chosen = self::chosen($sent, $product->get_name(), self::categories($product)) + $kept;

        if ($chosen === []) {
            $product->delete_meta_data(ProductView::META);
        } else {
            $product->update_meta_data(ProductView::META, $chosen);
        }
    }

    public static function panel(): void
    {
        global $product_object;

        $product = $product_object instanceof WC_Product ? $product_object : new WC_Product();
        $name = $product->get_name('edit');
        $stored = $product->get_meta(ProductView::META);
        $stored = is_array($stored) ? $stored : [];

        // What the names alone say – the meaning of "Automatisch" in each field.
        $guessedType = Guess::type($name, ...self::categories($product));
        $type = is_string($stored['type'] ?? null) && Kinds::has($stored['type']) ? $stored['type'] : $guessedType;
        $known = $type === null ? [] : Spec::known($type, $stored);

        $automatic = static fn (?string $made, string $drawn = ''): string => $made !== null
            ? 'Automatisch – erkannt: '.$made
            : 'Automatisch – nichts erkannt'.($drawn !== '' ? ', gezeichnet wird: '.$drawn : '');

        $views = $product->get_id() > 0 && $type !== null ? stiqon_product_views($product, true) : ['key' => null, 'views' => []];
        ?>
        <div id="<?php echo esc_attr(self::PANEL); ?>" class="panel woocommerce_options_panel hidden" data-type="<?php echo esc_attr((string) $guessedType); ?>">
            <style>
                #woocommerce-product-data ul.wc-tabs li.stiqon_preview_options a::before { content: "\f128"; }
                #<?php echo esc_html(self::PANEL); ?> .stiqon-views { display: flex; flex-wrap: wrap; gap: 12px; margin: 0; padding: 12px; }
                #<?php echo esc_html(self::PANEL); ?> .stiqon-views figure { box-sizing: border-box; width: 150px; margin: 0; }
                #<?php echo esc_html(self::PANEL); ?> .stiqon-views div { box-sizing: border-box; display: flex; align-items: center; justify-content: center; aspect-ratio: 1; padding: 9%; border-radius: 16px; background: #f3f3f3; }
                #<?php echo esc_html(self::PANEL); ?> .stiqon-views svg { display: block; overflow: visible; width: 100%; height: auto; filter: drop-shadow(0 1px 1px rgb(0 0 0 / 0.1)) drop-shadow(0 8px 10px rgb(0 0 0 / 0.12)); }
                #<?php echo esc_html(self::PANEL); ?> .stiqon-views figcaption { margin-top: 6px; color: #50575e; font-size: 12px; line-height: 1.4; }
                #<?php echo esc_html(self::PANEL); ?> .stiqon-views figcaption strong { display: block; color: #1d2327; }
            </style>

            <div class="options_group">
                <p class="form-field">
                    Der Shop zeigt dieses Produkt als Zeichnung. Art, Form und Beispielmotiv liest er aus Name und Kategorie;
                    hier lässt sich jede Angabe festlegen. Änderungen erscheinen nach dem Speichern.
                </p>

                <?php if ($views['views'] === []) : ?>
                    <p class="form-field"><em><?php echo esc_html($product->get_id() > 0
                        ? 'Aus Name und Kategorie geht nicht hervor, was für ein Produkt dies ist. Ohne Angabe unter „Art“ erscheint das Produkt ohne Zeichnung.'
                        : 'Die Zeichnung erscheint, sobald das Produkt gespeichert ist.'); ?></em></p>
                <?php else : ?>
                    <div class="stiqon-views" role="group" aria-label="So wird das Produkt gezeichnet">
                        <?php foreach ($views['views'] as $view) : ?>
                            <figure>
                                <?php // Markup from the plugin's own templates, escaped there. ?>
                                <div><?php echo $view['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                                <?php if ($view['name'] !== '') : ?>
                                    <figcaption><strong><?php echo esc_html($view['name']); ?></strong><?php echo esc_html(self::reading((string) $type, $view['name'])); ?></figcaption>
                                <?php endif; ?>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="options_group">
                <?php
                woocommerce_wp_select([
                    'id' => 'stiqon_mockup_type',
                    'name' => self::FIELD.'[type]',
                    'label' => 'Art',
                    'value' => $stored['type'] ?? '',
                    'options' => ['' => $automatic($guessedType !== null ? Kinds::of($guessedType)::label() : null)] + Kinds::labels(),
                ]);

                // Each kind's own fields; the script below shows those of the product's kind.
                foreach (Kinds::types() as $kind) {
                    $form = Kinds::of($kind)::form();
                    $guessedForm = Guess::product($kind, $name)[$form['key']] ?? null;
                    $field = self::formField($kind);

                    woocommerce_wp_select([
                        'id' => 'stiqon_mockup_'.$field,
                        'name' => self::FIELD.'['.$field.']',
                        'label' => $form['label'],
                        'wrapper_class' => 'stiqon-for-'.$kind,
                        'value' => $type === $kind ? ($known[$form['key']] ?? '') : '',
                        // A kind's first form is the one it is drawn in when nothing says otherwise.
                        'options' => ['' => $automatic($form['values'][$guessedForm] ?? null, (string) reset($form['values']))] + $form['values'],
                    ]);

                    $motifs = Kinds::of($kind)::motifs();

                    woocommerce_wp_select([
                        'id' => 'stiqon_mockup_'.$kind.'_motif',
                        'name' => self::FIELD.'['.$kind.'_motif]',
                        'label' => 'Beispielmotiv',
                        'wrapper_class' => 'stiqon-for-'.$kind,
                        'value' => $type === $kind ? ($known['motif'] ?? '') : '',
                        'options' => ['' => 'Automatisch – nach dem Namen gewählt'] + array_combine($motifs, array_map(static fn (string $motif): string => self::motifLabel($motif, $kind), $motifs)),
                        'description' => 'Steht für das Motiv der Kundschaft, solange sie kein eigenes gewählt hat.',
                        'desc_tip' => true,
                    ]);
                }
                ?>
            </div>

            <script>
                (function () {
                    var panel = document.getElementById(<?php echo wp_json_encode(self::PANEL); ?>);
                    var type = document.getElementById('stiqon_mockup_type');

                    // Every kind has fields of its own (a sticker's shape, a flyer's format …): only those of the product's kind are shown.
                    function show() {
                        var kind = type.value || panel.getAttribute('data-type');

                        <?php echo wp_json_encode(Kinds::types()); ?>.forEach(function (other) {
                            panel.querySelectorAll('.stiqon-for-' + other).forEach(function (field) {
                                field.hidden = other !== kind;
                            });
                        });
                    }

                    type.addEventListener('change', show);
                    show();
                })();
            </script>
        </div>
        <?php
    }

    /**
     * In a few words, what the drawing takes from the name of an option.
     */
    private static function reading(string $type, string $option): string
    {
        $says = Guess::option($type, $option);

        if ($type === 'sticker') {
            return isset($says['material'])
                ? 'gezeichnet als '.Sticker::MATERIAL_NAMES[$says['material']].(($says['finish'] ?? null) === 'matte' ? ', matt' : '')
                : 'Material nicht erkannt – gezeichnet als '.Sticker::MATERIAL_NAMES['vinyl'];
        }

        if ($type === 'flyer') {
            if (! isset($says['heavy'])) {
                return 'Grammatur nicht erkannt – gezeichnet als leichtes Papier';
            }

            return $says['heavy'] ? 'gezeichnet als festes Papier' : 'gezeichnet als leichtes Papier';
        }

        return $says === [] ? 'nichts erkannt – gezeichnet wie ohne Auswahl' : 'erkannt und gezeichnet';
    }

    /**
     * The field that sends a kind's form.
     */
    private static function formField(string $type): string
    {
        return self::FORM_FIELDS[$type] ?? $type.'_form';
    }

    /**
     * What the fields of the tab decide; anything else stored for the drawing is left alone.
     *
     * @return list<string>
     */
    private static function decided(): array
    {
        return array_values(array_unique(['type', 'motif', ...array_map(static fn (string $type): string => Kinds::of($type)::form()['key'], Kinds::types())]));
    }

    /**
     * A motif's name in the field. "(Kontur)" says that a sticker with this
     * motif is cut along its outline – on every other kind it is just a picture.
     */
    private static function motifLabel(string $motif, string $kind): string
    {
        if ($kind === 'sticker' && in_array($motif, Sticker::CONTOUR_MOTIFS, true)) {
            return ucfirst($motif).' (Kontur)';
        }

        return ['eroeffnung' => 'Eröffnung', 'cafe' => 'Café'][$motif] ?? ucfirst($motif);
    }

    /**
     * @return list<string>
     */
    private static function categories(WC_Product $product): array
    {
        $names = $product->get_id() > 0 ? wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']) : [];

        return is_array($names) ? array_values($names) : [];
    }
}
