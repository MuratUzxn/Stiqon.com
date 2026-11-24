/**
 * Storefront behaviour.
 *
 * Alpine comes bundled with Livewire, so nothing is imported here; components
 * are registered when Alpine announces itself. Templates only ever reference
 * properties and methods of these components (never inline expressions), which
 * keeps them compatible with Alpine's CSP-safe build.
 */

const MOTIF_TYPES = ['image/png', 'image/jpeg', 'image/webp'];
const MOTIF_MAX_BYTES = 25 * 1024 * 1024;

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

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

    /** Mobile navigation. */
    Alpine.data('siteHeader', () => ({
        open: false,

        get closed() {
            return !this.open;
        },

        toggle() {
            this.open = !this.open;
        },

        close() {
            this.open = false;
        },
    }));

    /** The moving card wall only animates while it is on screen. */
    Alpine.data('cardWall', () => ({
        running: false,

        init() {
            if (!('IntersectionObserver' in window)) {
                this.running = true;

                return;
            }

            const observer = new IntersectionObserver((entries) => {
                this.running = entries.some((entry) => entry.isIntersecting);
            });

            observer.observe(this.$el);
        },

        get paused() {
            return !this.running;
        },
    }));
});
