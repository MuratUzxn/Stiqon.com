/**
 * Shop behaviour.
 *
 * Alpine is bundled with this script. Components are registered here and in
 * the files this one loads on demand; templates only ever reference their
 * properties and methods (x-show="open", x-on:click="toggle") and carry no
 * logic of their own, so behaviour has one home and can be read and tested in
 * one place.
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
                // Whatever was kept before is not what the visitor sees now. It must
                // not come back on the next page or in another tab.
                await remove().catch(() => {});
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

/**
 * The quick view of a gallery puts the product's address into the browser's
 * history without leaving the gallery (see gallery.js). To the browser those
 * two addresses are one page from then on: after a reload on the product's
 * address, the Back button changes the address to the gallery's – and leaves
 * the product page on screen. Whenever the Back or Forward button arrives at an
 * address that is not this page's and is not a quick view, the page is loaded.
 *
 * This page's address is its path and query, not the path alone: a site without
 * "pretty" addresses tells its pages apart by the query (/?page_id=12,
 * /?product=runde-sticker). An anchor within the page is still the page.
 */
const address = () => window.location.pathname + window.location.search;

let ownAddress = address();

// A gallery rewrites its own address for its filters, and says so.
window.addEventListener('stiqon:address', () => {
    ownAddress = address();
});

window.addEventListener('popstate', () => {
    if (address() !== ownAddress && !window.history.state?.quickView) {
        window.location.reload();
    }
});

/**
 * The header says how many articles are in the cart – a number the server
 * writes with every page.
 */
const cartLink = document.querySelector('[data-cart-count]');

function showCartCount(count) {
    const badge = cartLink.querySelector('span');

    cartLink.dataset.cartCount = String(count);
    cartLink.setAttribute('aria-label', count === 0 ? 'Warenkorb, leer' : `Warenkorb, ${count} Artikel`);

    if (badge) {
        badge.textContent = String(count);
        badge.hidden = count === 0;
    }
}

/** WooCommerce's fingerprint of the cart as the browser has it now; empty for an empty cart. */
function cartHashNow() {
    const kept = document.cookie.split('; ').find((entry) => entry.startsWith('woocommerce_cart_hash='));

    return kept ? decodeURIComponent(kept.slice(kept.indexOf('=') + 1)) : '';
}

/**
 * A page can be older than the cart: the Back button shows it as the browser
 * kept it, and a cache in front of the shop may hand out a page written for
 * someone else. The page carries the fingerprint of the cart it was written
 * with. Where the browser's differs, the number is put right – asked for again,
 * or simply none for a cart that has been emptied.
 */
/**
 * Loads the page anew – once for a given state of the cart, so that a page that
 * cannot be brought in step is not loaded again and again.
 */
function reloadOnce(hash) {
    try {
        if (window.sessionStorage.getItem('stiqon-cart-reloaded') === hash) {
            return;
        }

        window.sessionStorage.setItem('stiqon-cart-reloaded', hash);
    } catch {
        return;
    }

    window.location.reload();
}

/** Whether the page was shown again by the Back or Forward button. */
function cameBack() {
    const [entry] = performance.getEntriesByType('navigation');

    return entry?.type === 'back_forward';
}

async function checkCartCount(fromHistory = cameBack()) {
    const now = cartHashNow();

    if (now === (cartLink.dataset.cartHash ?? '')) {
        return;
    }

    cartLink.dataset.cartHash = now;

    // The cart page is as old as its number: shown again by the Back button after
    // something was taken out, it would still list it. It is loaded anew instead –
    // only then: WooCommerce counts the totals into its checksum, so on an ordinary
    // visit the page's and the browser's may differ although the page is fresh.
    if (fromHistory && document.body.classList.contains('woocommerce-cart')) {
        reloadOnce(now);

        return;
    }

    if (now === '') {
        showCartCount(0);

        return;
    }

    try {
        const response = await fetch(cartLink.dataset.cartSource, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });

        if (response.ok) {
            showCartCount(Number((await response.json()).items_count) || 0);
        }
    } catch {
        // The number stays as the page had it.
    }
}

if (cartLink) {
    checkCartCount();

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            checkCartCount(true);
        }
    });
}

// On the cart page WooCommerce's script takes articles out and puts them back without loading
// a new page; it announces that with an event of jQuery's own. The number then follows the page.
if (cartLink && window.jQuery && document.body.classList.contains('woocommerce-cart')) {
    window.jQuery(document.body).on('updated_wc_div wc_cart_emptied', () => {
        const numbers = [...document.querySelectorAll('.woocommerce-cart-form .cart_item .qty')];

        showCartCount(numbers.reduce((sum, field) => sum + (Number(field.value) || 0), 0));
        cartLink.dataset.cartHash = cartHashNow();
    });
}

/**
 * Behaviour that belongs to one kind of page lives in a file of its own and is
 * fetched only where the page asks for it: an element with x-data="…" names
 * the component it needs. The home page does not pay for the product page.
 */
const features = {
    galleryFilter: () => import('./gallery.js'),
    productOptions: () => import('./product.js'),
};

await Promise.all(
    Object.entries(features)
        .filter(([component]) => document.querySelector(`[x-data="${component}"]`))
        .map(([, load]) => load().then((feature) => feature.default(Alpine))),
);

window.Alpine = Alpine;
Alpine.start();

/**
 * Search opens over the page, at a click on a search control or with Ctrl + K
 * (⌘ K on a Mac). What it needs is fetched when a visitor first asks for it.
 * A search control is a link to the results page: without this script, or if
 * the fetch fails, that is where it leads.
 */
const searchControls = document.querySelectorAll('[data-search-open]');

async function openSearch() {
    try {
        const search = await import('./search.js');

        return search.open(Alpine);
    } catch {
        return false;
    }
}

if (searchControls.length > 0) {
    const shortcut = /mac|iphone|ipad/i.test(navigator.userAgentData?.platform ?? navigator.platform) ? '⌘K' : null;

    searchControls.forEach((control) => {
        // With this script the link opens a dialog, and there are keys for it.
        control.setAttribute('aria-haspopup', 'dialog');
        control.setAttribute('aria-keyshortcuts', 'Control+K Meta+K');

        if (shortcut !== null) {
            control.querySelectorAll('[data-search-shortcut]').forEach((hint) => {
                hint.textContent = shortcut;
            });
        }

        control.addEventListener('click', async (event) => {
            // With a modifier key the link opens where the visitor wants it: a new tab, a new window.
            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            event.preventDefault();

            if (!(await openSearch())) {
                window.location.assign(control.href);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && !event.altKey && !event.shiftKey && event.key?.toLowerCase() === 'k') {
            event.preventDefault();
            openSearch();
        }
    });
}

// What stands in for the script where there is none – the line on a product
// page that its price is the one of the choice shown – leaves the page once the
// script runs.
document.querySelectorAll('[data-without-script]').forEach((element) => element.remove());
