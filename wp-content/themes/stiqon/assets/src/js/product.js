/**
 * Behaviour of the product page. Loaded only on product pages (see app.js).
 */
export default function register(Alpine) {
    /**
     * The product page. Its options are radio buttons that work on their own;
     * this keeps what surrounds them in step with the choice: the drawing, the
     * price without VAT, which options can still be combined – and WooCommerce's
     * own script, which reads a select element per option.
     */
    Alpine.data('productOptions', () => ({
        form: null,

        init() {
            this.form = this.$root.querySelector('form.variations_form');

            if (!this.form) {
                return;
            }

            this.form.addEventListener('change', (event) => {
                if (event.target.matches('input[type="radio"][data-select]')) {
                    this.pass(event.target);
                    this.follow();
                }
            });

            // A page restored from the browser's memory may come back with other pills
            // checked than the page was sent with.
            this.form.querySelectorAll('input[type="radio"][data-select]:checked').forEach((radio) => this.pass(radio));
            this.follow();
        },

        /** Hands a choice on to the select that WooCommerce's script listens to. */
        pass(radio) {
            const select = this.form.querySelector(`select[data-attribute_name="${CSS.escape(radio.name)}"]`);

            if (select && select.value !== radio.value) {
                select.value = radio.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },

        /** What is chosen now: { attribute_pa_material: 'vinyl', … }. */
        chosen() {
            const radios = this.form.querySelectorAll('input[type="radio"][data-select]:checked');

            return Object.fromEntries([...radios].map((radio) => [radio.name, radio.value]));
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

        /** Whether a variation fits a choice; an empty value on a variation means "any". */
        fits(variation, chosen) {
            return Object.entries(variation.attributes).every(([name, value]) => value === '' || chosen[name] === value);
        },

        follow() {
            const chosen = this.chosen();
            const variations = this.variations();

            // The drawing for the chosen material or paper.
            const stage = this.$root.querySelector('[data-product-stage]');

            if (stage && stage.dataset.option && chosen[stage.dataset.option] !== undefined) {
                stage.querySelectorAll('[data-view]').forEach((view) => {
                    view.hidden = view.dataset.view !== chosen[stage.dataset.option];
                });
            }

            if (variations.length === 0) {
                return;
            }

            // The price without VAT of the chosen variation.
            const variation = variations.find((candidate) => this.fits(candidate, chosen));
            const net = this.$root.querySelector('[data-net-price]');

            if (net && variation) {
                net.innerHTML = variation.stiqon_net_price ?? '';
                net.hidden = !variation.stiqon_net_price;
            }

            // An option that no variation offers together with the rest of the choice.
            this.form.querySelectorAll('input[type="radio"][data-select]').forEach((radio) => {
                const tried = { ...chosen, [radio.name]: radio.value };

                radio.disabled = !radio.checked && !variations.some((candidate) => this.fits(candidate, tried));
            });
        },
    }));
}
