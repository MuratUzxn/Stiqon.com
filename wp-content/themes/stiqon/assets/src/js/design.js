/**
 * A design's page: "Als Motiv ausprobieren" makes the design the visitor's
 * motif, exactly as if they had chosen its picture as a file with the motif
 * button – the same checks, the same store, nothing uploaded. The picture comes
 * from this shop's own address. Then the visitor is taken to the area of the
 * design's medium, where every mockup shows it.
 */
const FAILED = 'Das Design lässt sich gerade nicht laden. Bitte versuchen Sie es noch einmal.';

// This page has no mockups: what the store would say about "this page" does not hold here.
const UNKEPT = 'Dieses Fenster erlaubt nicht, das Design als Motiv für die nächste Seite zu behalten. Ein privates Fenster oder gesperrte Website-Daten verhindern das.';

export default function (Alpine) {
    Alpine.data('designMotif', () => ({
        busy: false,

        init() {
            // Back from the area, the browser may bring this page out of its memory
            // as it was left – busy, on its way there – and the button would do nothing.
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) {
                    this.busy = false;
                }
            });
        },

        async tryOn(event) {
            if (this.busy) {
                return;
            }

            this.busy = true;
            this.$store.motif.trigger = event.currentTarget;

            const { image, name, type, target } = this.$root.dataset;
            let kept = false;

            try {
                const response = await fetch(image, { credentials: 'same-origin' });

                if (!response.ok) {
                    throw new Error(`The picture answered ${response.status}.`);
                }

                const blob = await response.blob();
                // The server names the type; where it does not, the shop knows it from the media library.
                const file = new File([blob], name, { type: blob.type || type });

                // Not the visitor's file: "choose another format" would ask them for something they did not do.
                if (!this.$store.motif.accepts(file.type)) {
                    throw new Error(`The picture is ${file.type || 'of no known type'}.`);
                }

                kept = await this.$store.motif.choose(file, { unkept: UNKEPT });
            } catch {
                this.$store.motif.fail(FAILED);
            }

            // Only a motif the next page can find is worth the way there. Where the
            // browser does not keep it, it stays on this page, and the bar says why.
            if (kept) {
                window.location.assign(target);

                return;
            }

            this.busy = false;
        },
    }));
}
