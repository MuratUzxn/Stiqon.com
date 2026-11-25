/**
 * Shop behaviour.
 *
 * Alpine is bundled with this script. Components are registered here; templates
 * only ever reference their properties and methods (x-show="open",
 * x-on:click="toggle") and carry no logic of their own, so behaviour has one
 * home and can be read and tested in one place.
 */
import Alpine from 'alpinejs';

const MOTIF_TYPES = ['image/png', 'image/jpeg', 'image/webp'];
const MOTIF_MAX_BYTES = 25 * 1024 * 1024;

document.addEventListener('alpine:init', () => {

    /**
     * The visitor's own motif. It stays in the browser: an object URL to the
     * chosen file, shown on every mockup of the page. Nothing is uploaded.
     */
    Alpine.store('motif', {
        url: null,
        name: '',
        error: '',
        // What just happened, in a sentence; read out by screen readers.
        announcement: '',
        // The button the visitor last opened the file dialog with.
        trigger: null,

        choose(file) {
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

            this.release();
            this.url = URL.createObjectURL(file);
            this.name = file.name;
            this.error = '';
            this.announcement = `Ihr Motiv „${file.name}“ ist jetzt auf allen Ansichten zu sehen.`;
        },

        clear() {
            this.release();
            this.error = '';
            this.announcement = 'Ihr Motiv wurde entfernt. Die Ansichten zeigen wieder Beispiele.';
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
