import { closest } from './variation-match.js';

/**
 * Behaviour of the product page. Loaded only on product pages (see app.js).
 */
export default function register(Alpine) {
    /**
     * The product page. Its options are radio buttons that work on their own;
     * this keeps what surrounds them in step with the choice: the drawing, the
     * price without VAT, a choice that exists as a variation – and WooCommerce's
     * own script, which reads a select element per option.
     */
    Alpine.data('productOptions', () => ({
        form: null,

        /** What was changed besides the visitor's own choice, in words. */
        note: '',

        init() {
            this.form = this.$root.querySelector('form.variations_form');

            if (!this.form) {
                return;
            }

            this.form.addEventListener('change', (event) => {
                if (event.target.matches('input[type="radio"][data-select]')) {
                    this.settle(event.target);
                    this.pass();
                    this.follow();
                }
            });

            this.listen();

            // A page restored from the browser's memory may come back with other pills
            // checked than the page was sent with.
            this.pass();
            this.follow();
        },

        /**
         * WooCommerce's script says which variation a choice makes – found in the
         * list it has in the page or, for a product with many variations, asked of
         * the server. The price without VAT follows what it says. Its events are
         * jQuery's own, which an ordinary listener never hears.
         */
        listen() {
            const net = this.$root.querySelector('[data-net-price]');

            if (!net || !window.jQuery) {
                return;
            }

            const show = (text) => {
                net.innerHTML = text ?? '';
                net.hidden = !text;
            };

            window.jQuery(this.form)
                .on('found_variation.stiqon', (event, variation) => show(variation?.stiqon_net_price))
                .on('reset_data.stiqon', () => show(''));
        },

        /** The pills of all options. */
        radios() {
            return [...this.form.querySelectorAll('input[type="radio"][data-select]')];
        },

        /** What is chosen now: { attribute_pa_material: 'vinyl', … }. */
        chosen() {
            return Object.fromEntries(this.radios().filter((radio) => radio.checked).map((radio) => [radio.name, radio.value]));
        },

        /** The variations WooCommerce wrote into the form – or none, when it loads them on demand. */
        variations() {
            try {
                const variations = JSON.parse(this.form.dataset.product_variations);

                return Array.isArray(variations) ? variations : [];
            } catch {
                return [];
            }
        },

        /**
         * Not every combination of options exists. When the pill just chosen does
         * not go with the rest of the choice, the rest moves to the nearest
         * combination that does: every value of every option stays within reach,
         * and the page always shows something that can be bought.
         */
        settle(radio) {
            const variations = this.variations();

            this.note = '';

            // Loaded on demand: WooCommerce asks the server, and says so if nothing fits.
            if (variations.length === 0) {
                return;
            }

            const wanted = this.chosen();
            const settled = closest(variations.map((variation) => variation.attributes), wanted, [radio.name]);
            const moved = [];

            for (const other of this.radios()) {
                if (settled[other.name] === other.value && wanted[other.name] !== other.value) {
                    other.checked = true;
                    moved.push(`${other.dataset.group} ${other.dataset.label}`);
                }
            }

            if (moved.length > 0) {
                this.note = `Passend dazu gewählt: ${moved.join(', ')}.`;
            }
        },

        /**
         * Hands the choice on to the selects WooCommerce's script listens to, all
         * at once: one change at a time would pass through combinations that do not
         * exist, and that script empties a select whose value does not fit the others.
         */
        pass() {
            const chosen = this.chosen();
            let changed = null;

            for (const select of this.form.querySelectorAll('select[data-attribute_name]')) {
                const value = chosen[select.dataset.attribute_name];

                if (value === undefined || select.value === value) {
                    continue;
                }

                // That script keeps in a select only what goes with the other selects'
                // values, and rebuilds the list at every change.
                if (![...select.options].some((option) => option.value === value)) {
                    select.add(new Option(value, value));
                }

                select.value = value;
                changed = select;
            }

            changed?.dispatchEvent(new Event('change', { bubbles: true }));
        },

        /** Shows the drawing for the chosen material or paper. */
        follow() {
            const chosen = this.chosen();
            const stage = this.$root.querySelector('[data-product-stage]');

            if (stage && stage.dataset.option && chosen[stage.dataset.option] !== undefined) {
                stage.querySelectorAll('[data-view]').forEach((view) => {
                    view.hidden = view.dataset.view !== chosen[stage.dataset.option];
                });
            }
        },
    }));
}
