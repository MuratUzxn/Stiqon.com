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
        // The quick view: the card that is open, and what it shows.
        key: null,
        title: '',
        subtitle: '',
        price: '',
        summary: '',
        url: '',
        position: '',
        atStart: false,
        atEnd: false,

        init() {
            // The browser's Back and Forward buttons open and close the quick view.
            this.sync = this.sync.bind(this);
            window.addEventListener('popstate', this.sync);
            window.addEventListener('pageshow', this.sync);
        },

        destroy() {
            window.removeEventListener('popstate', this.sync);
            window.removeEventListener('pageshow', this.sync);
        },

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

            // The filters are part of this page's own address, not another page (see app.js).
            window.dispatchEvent(new Event('stiqon:address'));
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

        /*
         | The quick view
         |
         | A click on a card opens it large over the page instead of leaving the
         | page. While it is open the address is the product's own, so a reload or
         | a shared link shows the product page; the Back button closes it.
         */

        /** The cards the filters currently show, in the order of the page. */
        cards() {
            return [...this.$root.querySelectorAll('[data-card]:not([hidden])')];
        },

        card(key) {
            return this.cards().find((card) => card.dataset.key === key) ?? null;
        },

        /** A click inside the grid of cards. */
        open(event) {
            const link = event.target.closest('a');
            const card = event.target.closest('[data-card]');

            // Small screens get the product page itself, and so does a click that asks for a new tab.
            if (!link || !card || !window.matchMedia('(min-width: 48rem)').matches
                || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            event.preventDefault();

            window.history.pushState({ quickView: card.dataset.key }, '', link.href);
            this.show(card);
        },

        show(card) {
            const cards = this.cards();
            const index = cards.indexOf(card);
            const drawing = card.querySelector('svg[data-mockup]');

            this.key = card.dataset.key;
            this.title = card.querySelector('h3')?.textContent.trim() ?? '';
            this.subtitle = card.querySelector('[data-subtitle]')?.textContent.trim() ?? '';
            this.price = card.querySelector('[data-price]')?.textContent.trim() ?? '';
            this.summary = card.dataset.summary ?? '';
            this.url = card.querySelector('a')?.href ?? '';
            this.position = `${index + 1} von ${cards.length}`;
            this.atStart = index <= 0;
            this.atEnd = index >= cards.length - 1;

            // The drawing is a copy of the card's. Its ids get a prefix of their own:
            // two elements with one id would make every reference point to the first.
            this.$refs.stage.innerHTML = drawing
                ? drawing.outerHTML.replace(/\b((?:st|fl)\d+-)/g, 'schnellansicht-$1')
                : '';

            if (!this.$refs.viewer.open) {
                this.$refs.viewer.showModal();
            }
        },

        step(direction) {
            const cards = this.cards();
            const card = cards[cards.findIndex((candidate) => candidate.dataset.key === this.key) + direction];

            if (card) {
                window.history.replaceState({ quickView: card.dataset.key }, '', card.querySelector('a').href);
                this.show(card);
            }
        },

        previous() {
            this.step(-1);
        },

        next() {
            this.step(1);
        },

        /** The close button, and a click beside the sheet. */
        dismiss() {
            this.$refs.viewer.close();
        },

        backdrop(event) {
            // Only the dialog element itself lies outside the sheet: its backdrop.
            if (event.target === this.$refs.viewer) {
                this.dismiss();
            }
        },

        /** The dialog was closed – by its button, the Escape key or a click beside it. */
        closed() {
            const card = this.key ? this.$root.querySelector(`[data-card][data-key="${CSS.escape(this.key)}"] a`) : null;

            this.key = null;
            this.$refs.stage.innerHTML = '';

            // Leave the product's address the way it was entered.
            if (window.history.state?.quickView) {
                window.history.back();
            }

            card?.focus({ preventScroll: true });
        },

        /** Brings the dialog in line with where the browser's history is. */
        sync() {
            const key = window.history.state?.quickView;
            const card = key ? this.card(key) : null;

            if (card) {
                this.show(card);
            } else if (key) {
                // The address is a product's, but its card is not among those shown
                // (any more): the product page itself is the right thing to show.
                window.location.reload();
            } else if (this.$refs.viewer.open) {
                this.$refs.viewer.close();
            }
        },
    }));
}
