/**
 * Shop behaviour.
 *
 * Alpine is bundled with this script. Components are registered here; templates
 * only ever reference their properties and methods (x-show="open",
 * x-on:click="toggle") and carry no logic of their own, so behaviour has one
 * home and can be read and tested in one place.
 */
import Alpine from 'alpinejs';
import { HINT, hint, preview, read, remove, write } from './motif-store.js';

const MOTIF_TYPES = ['image/png', 'image/jpeg', 'image/webp'];
const MOTIF_MAX_BYTES = 25 * 1024 * 1024;

document.addEventListener('alpine:init', () => {

    /**
     * The visitor's own motif: an object URL to the chosen image, shown on every
     * mockup of the page. It stays in the browser – in memory, and stored there
     * so that the next page shows it too. Nothing is uploaded.
     */
    Alpine.store('motif', {
        url: null,
        name: '',
        error: '',
        // Something the visitor should know that is not a mistake of theirs.
        notice: '',
        // What just happened, in a sentence; read out by screen readers.
        announcement: '',
        // The button the visitor last opened the file dialog with.
        trigger: null,

        init() {
            this.restore();

            // Another tab chose or removed a motif.
            window.addEventListener('storage', (event) => {
                if (event.key === HINT) {
                    this.restore();
                }
            });

            // A page taken out of the browser's memory (the Back button) missed what happened since.
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) {
                    this.restore();
                }
            });
        },

        /** Shows what an earlier page or another tab stored – or nothing, if nothing is stored. */
        async restore() {
            let record = null;

            try {
                record = await read();
            } catch {
                // Nothing stored, or the browser does not let this page store anything.
            }

            if (record && record.blob instanceof Blob) {
                await this.show(record.blob, record.name);
            } else {
                this.release();
            }

            // The head script hid the sample motifs while this was pending.
            await Alpine.nextTick();
            document.documentElement.removeAttribute('data-motif');
        },

        async choose(file) {
            if (!file) {
                return;
            }

            if (!MOTIF_TYPES.includes(file.type)) {
                this.fail('Bitte ein Bild im Format PNG, JPEG oder WebP wählen.');

                return;
            }

            if (file.size > MOTIF_MAX_BYTES) {
                this.fail('Das Bild ist größer als 25 MB.');

                return;
            }

            let blob;

            try {
                blob = await preview(file);
            } catch {
                this.fail('Dieses Bild lässt sich nicht lesen. Bitte eine andere Datei wählen.');

                return;
            }

            await this.show(blob, file.name);

            this.error = '';
            this.notice = '';
            this.announcement = `Ihr Motiv „${file.name}“ ist jetzt auf allen Ansichten zu sehen.`;

            try {
                const savedAt = Date.now();

                await write({ name: file.name, savedAt, blob });
                hint(savedAt);
            } catch {
                hint(null);
                this.notice = 'Ihr Motiv ist auf dieser Seite zu sehen. Dieses Fenster erlaubt nicht, es für die nächste Seite zu behalten.';
                this.announcement = `${this.announcement} ${this.notice}`;
            }
        },

        clear() {
            this.release();
            this.error = '';
            this.notice = '';
            this.announcement = 'Ihr Motiv wurde entfernt. Die Ansichten zeigen wieder Beispiele.';

            // Other tabs look into the store as soon as the note changes – so the
            // store is emptied first and the note taken away after.
            remove().catch(() => {}).finally(() => hint(null));
        },

        /** Puts an image on the mockups once the browser has decoded it, so it does not flicker in. */
        async show(blob, name) {
            const url = URL.createObjectURL(blob);

            try {
                const image = new Image();

                image.src = url;
                await image.decode();
            } catch {
                // The mockups will simply show it a moment later.
            }

            this.release();
            this.url = url;
            this.name = name;
        },

        release() {
            if (this.url) {
                URL.revokeObjectURL(this.url);
            }

            this.url = null;
            this.name = '';
        },

        /**
         * Shows a message until it is closed or a motif is chosen; the current
         * motif stays as it is. The text is set anew a moment later, so that the
         * same message is read out again when the same mistake happens twice.
         */
        fail(message) {
            this.error = '';
            window.setTimeout(() => {
                this.error = message;
            }, 50);
        },

        dismiss() {
            this.error = '';
            this.notice = '';
        },
    });

    /** Printed area of a mockup: sample motif until the visitor chooses one. */
    Alpine.data('motifLayer', () => ({
        get url() {
            return this.$store.motif.url;
        },

        get active() {
            return this.$store.motif.url !== null;
        },

        get idle() {
            return this.$store.motif.url === null;
        },
    }));

    /** A button with its own hidden file input for choosing the motif. */
    Alpine.data('motifPicker', () => ({
        pick(event) {
            this.$store.motif.trigger = event.currentTarget;
            this.$refs.file.click();
        },

        chosen(event) {
            this.$store.motif.choose(event.target.files[0]);
            event.target.value = '';
        },
    }));

    /**
     * A row of cards that scrolls sideways, with buttons for the previous and the
     * next card. The buttons know when the row is at either end.
     */
    Alpine.data('cardRow', () => ({
        atStart: true,
        atEnd: false,

        init() {
            this.update();
        },

        update() {
            const row = this.$refs.row;

            this.atStart = row.scrollLeft <= 1;
            this.atEnd = row.scrollLeft + row.clientWidth >= row.scrollWidth - 1;
        },

        step(direction) {
            const row = this.$refs.row;
            const card = row.firstElementChild;
            const gap = Number.parseFloat(getComputedStyle(row).columnGap) || 0;
            const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            row.scrollBy({
                left: direction * (card ? card.getBoundingClientRect().width + gap : row.clientWidth),
                behavior: calm ? 'auto' : 'smooth',
            });
        },

        previous() {
            this.step(-1);
        },

        next() {
            this.step(1);
        },
    }));

    /**
     * The filters of a gallery. They are links and work on their own; with this,
     * a click filters the cards in place instead of loading the page again. The
     * state is the markup itself: which chip says aria-current, which card is hidden.
     */
    Alpine.data('galleryFilter', () => ({
        choose(event) {
            // A click that asks for a new tab or window stays a plain link.
            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            event.preventDefault();

            const chip = event.currentTarget;
            const chosen = { ...this.chosen(), [chip.dataset.filter]: chip.dataset.value };

            this.apply(chosen);
            window.history.replaceState(window.history.state, '', this.address(chosen));
        },

        /** What is chosen now, per filter; an empty value means "all". */
        chosen() {
            const chosen = { produkt: '', option: '' };

            this.$root.querySelectorAll('[data-filter][aria-current="true"]').forEach((chip) => {
                chosen[chip.dataset.filter] = chip.dataset.value;
            });

            return chosen;
        },

        /** The page's address for a choice – the same one the server writes into the chips. */
        address(chosen) {
            const url = new URL(this.$root.dataset.page, window.location.href);

            if (chosen.produkt) {
                url.searchParams.set('produkt', chosen.produkt);
            }

            if (chosen.option) {
                url.searchParams.set(this.$root.dataset.optionParameter, chosen.option);
            }

            return url.toString();
        },

        apply(chosen) {
            const cards = [...this.$root.querySelectorAll('[data-card]')];
            let shown = 0;

            cards.forEach((card) => {
                const matches = (!chosen.produkt || card.dataset.produkt === chosen.produkt)
                    && (!chosen.option || card.dataset.option === chosen.option);

                card.hidden = !matches;
                shown += matches ? 1 : 0;
            });

            this.$root.querySelectorAll('[data-filter]').forEach((chip) => {
                chip.setAttribute('aria-current', chosen[chip.dataset.filter] === chip.dataset.value ? 'true' : 'false');
                chip.href = this.address({ ...chosen, [chip.dataset.filter]: chip.dataset.value });
            });

            this.$root.querySelector('[data-count]').textContent = shown === cards.length
                ? `${cards.length} Ausführungen`
                : `${shown} von ${cards.length} Ausführungen`;
            this.$root.querySelector('[data-empty]').hidden = shown > 0;
        },
    }));

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

    /** The bar that reports the chosen motif and lets the visitor remove it. */
    Alpine.data('motifStatus', () => ({
        get active() {
            return this.$store.motif.url !== null;
        },

        get name() {
            return this.$store.motif.name;
        },

        get url() {
            return this.$store.motif.url;
        },

        get error() {
            return this.$store.motif.error;
        },

        get hasError() {
            return this.$store.motif.error !== '';
        },

        /** What the message at the bottom shows: a mistake, or else something to know. */
        get message() {
            return this.$store.motif.error || this.$store.motif.notice;
        },

        get hasMessage() {
            return this.$store.motif.error !== '' || this.$store.motif.notice !== '';
        },

        get announcement() {
            return this.$store.motif.announcement;
        },

        clear(event) {
            // With the keyboard, the focus sits on the button that is about to
            // disappear with the bar. It goes back to where the motif was chosen.
            const byKeyboard = event.currentTarget.matches(':focus-visible');

            this.$store.motif.clear();

            if (byKeyboard) {
                const shown = (button) => button && button.isConnected && button.offsetParent !== null;
                const buttons = [...document.querySelectorAll('[data-motif-button]')];
                const target = shown(this.$store.motif.trigger) ? this.$store.motif.trigger : buttons.find(shown);

                target?.focus();
            }
        },

        dismiss() {
            this.$store.motif.dismiss();
        },
    }));
});

window.Alpine = Alpine;
Alpine.start();
