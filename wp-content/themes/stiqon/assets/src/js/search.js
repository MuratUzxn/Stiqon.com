/**
 * The search that opens over the page (template-parts/search-panel.php).
 *
 * This file is fetched when a visitor first asks for the search (see app.js).
 * It fetches what there is to find – a list of areas, products and variants
 * from the plugin "Stiqon Shop-Funktionen" – and from then on answers every
 * keystroke in the browser. Enter leads to the results page, which the server
 * renders from the same list with the same rules.
 */
import { rank } from './search-match.js';

/** How many rows are listed: for what was typed, and before anything is. */
const HITS = 8;
const SUGGESTIONS = 5;

/** What there is to find. Kept out of the component: a list this size need not be watched for changes. */
let entries = [];

let panel = null;

const component = () => ({
    query: '',
    // The rows on screen: entries with what a row needs besides (its id, whether the keyboard is on it).
    hits: [],
    // How many entries fit in all; the list shows the first of them.
    total: 0,
    // Which row the arrow keys are on. -1: none, Enter goes to the results page.
    cursor: -1,
    // 'loading', 'ready', or 'unavailable' when the entries could not be fetched.
    state: 'loading',
    // What just changed, in a few words; read out by screen readers.
    announcement: '',

    init() {
        this.load();
    },

    async load() {
        try {
            // For someone who is logged in the page holds a token; with it the request says who it is from.
            const { index, nonce } = this.$root.dataset;
            let response = await fetch(index, { headers: { Accept: 'application/json', ...(nonce ? { 'X-WP-Nonce': nonce } : {}) } });

            // The token of a page that has been open for a day is no longer taken. Then ask as anyone would.
            if (response.status === 403 && nonce) {
                response = await fetch(index, { headers: { Accept: 'application/json' } });
            }

            if (!response.ok) {
                throw new Error(`status ${response.status}`);
            }

            entries = (await response.json()).entries;
            this.state = 'ready';
        } catch {
            // The field still works: Enter sends the query to the results page.
            this.state = 'unavailable';
        }

        this.search();
    },

    get typed() {
        return this.query.trim() !== '';
    },

    get listed() {
        return this.hits.length > 0;
    },

    get nothing() {
        return this.state === 'ready' && this.typed && this.hits.length === 0;
    },

    get unavailable() {
        return this.state === 'unavailable';
    },

    /** Whether the way to the results page is offered: once something is typed that may be found there. */
    get onward() {
        return this.typed && !this.nothing;
    },

    get heading() {
        return this.typed ? 'Treffer' : 'Vorschläge';
    },

    /** The id of the row the keyboard is on, for aria-activedescendant. */
    get active() {
        return this.hits[this.cursor]?.id ?? null;
    },

    show() {
        if (!this.$root.open) {
            this.$root.showModal();
        }

        this.$refs.field.focus();
        this.$refs.field.select();
    },

    dismiss() {
        this.$root.close();
    },

    /** A click beside the sheet lands on the dialog itself. */
    backdrop(event) {
        if (event.target === this.$root) {
            this.$root.close();
        }
    },

    search() {
        // Before anything is typed: the areas and the first products, as a way in.
        const found = this.typed
            ? rank(entries, this.query)
            : entries.map((entry, position) => (entry.w > 1 ? position : -1)).filter((position) => position >= 0);
        const shown = found.slice(0, this.typed ? HITS : SUGGESTIONS);

        this.total = found.length;
        this.cursor = -1;
        this.hits = shown.map((position, index) => ({ ...entries[position], id: `suche-treffer-${index}`, index, selected: false }));

        if (this.state !== 'ready' || !this.typed) {
            this.announcement = '';
        } else if (this.total === 0) {
            this.announcement = 'Keine Treffer';
        } else if (this.total > shown.length) {
            this.announcement = `${this.total} Treffer, die ersten ${shown.length} sind aufgelistet`;
        } else {
            this.announcement = `${this.total} Treffer`;
        }
    },

    next() {
        this.move(this.cursor + 1 < this.hits.length ? this.cursor + 1 : 0);
    },

    previous() {
        this.move(this.cursor > 0 ? this.cursor - 1 : this.hits.length - 1);
    },

    move(to) {
        if (!this.hits[to]) {
            return;
        }

        this.hits.forEach((hit, index) => {
            hit.selected = index === to;
        });
        this.cursor = to;

        this.$nextTick(() => document.getElementById(this.hits[to]?.id)?.scrollIntoView({ block: 'nearest' }));
    },

    /** The pointer chooses a row the way the arrow keys do, so only ever one row is marked. */
    point(event) {
        const index = Number(event.currentTarget.dataset.index);

        if (index !== this.cursor) {
            this.move(index);
        }
    },

    /** Enter: to the chosen row. With none chosen the form goes its own way, to the results page. */
    go(event) {
        const hit = this.hits[this.cursor];

        // While a word is still being composed (input methods), Enter belongs to that.
        if (!hit || event.isComposing) {
            return;
        }

        event.preventDefault();
        window.location.assign(hit.u);
    },
});

/**
 * Opens the search, putting it into the page the first time.
 *
 * @returns {boolean} false when the page has no search to open
 */
export function open(Alpine) {
    if (panel === null) {
        const template = document.getElementById('suche-vorlage');

        if (!template) {
            return false;
        }

        Alpine.data('searchPanel', component);

        // Alpine sets up what is added to a page when it notices; here it is told, so the search is ready at once.
        Alpine.mutateDom(() => document.body.append(template.content.cloneNode(true)));
        panel = document.getElementById('suche');
        Alpine.initTree(panel);
    }

    Alpine.$data(panel).show();

    return true;
}
