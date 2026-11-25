/**
 * Behaviour of the area pages. Loaded only where a gallery is on the page
 * (see app.js).
 */
export default function register(Alpine) {
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
}
