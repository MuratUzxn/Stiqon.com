/**
 * A design's page: "Als Motiv ausprobieren" makes the design the visitor's
 * motif, exactly as if they had chosen its picture as a file with the motif
 * button – the same checks, the same store, nothing uploaded. The picture comes
 * from this shop's own address. Then the visitor is taken to the area of the
 * design's medium, where every mockup shows it.
 */
export default function (Alpine) {
    Alpine.data('designMotif', () => ({
        busy: false,

        async tryOn(event) {
            if (this.busy) {
                return;
            }

            this.busy = true;
            this.$store.motif.trigger = event.currentTarget;

            const { image, name, target } = this.$root.dataset;
            let kept = false;

            try {
                const response = await fetch(image, { credentials: 'same-origin' });

                if (!response.ok) {
                    throw new Error(`The picture answered ${response.status}.`);
                }

                const blob = await response.blob();

                kept = await this.$store.motif.choose(new File([blob], name, { type: blob.type }));
            } catch {
                this.$store.motif.fail('Das Design lässt sich gerade nicht laden. Bitte versuchen Sie es noch einmal.');
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
