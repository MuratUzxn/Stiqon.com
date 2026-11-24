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

            this.clear();
            this.url = URL.createObjectURL(file);
            this.name = file.name;
        },

        clear() {
            if (this.url) {
                URL.revokeObjectURL(this.url);
            }

            this.url = null;
            this.name = '';
            this.error = '';
        },

        /** Shows a message for a few seconds; the current motif stays as it is. */
        fail(message) {
            this.error = message;
            window.setTimeout(() => {
                if (this.error === message) {
                    this.error = '';
                }
            }, 6000);
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
        pick() {
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

        clear() {
            this.$store.motif.clear();
        },
    }));
});

window.Alpine = Alpine;
Alpine.start();
