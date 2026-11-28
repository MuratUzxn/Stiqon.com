/**
 * Keeps the visitor's motif in the browser, so that it is still there on the
 * next page. It is stored in IndexedDB, as a copy no larger than the shop ever
 * shows it. Nothing in here talks to a server.
 *
 * Every function returns a promise. Where the browser refuses to store
 * anything (private windows, blocked site data), the promise rejects, and the
 * caller carries on with the motif in memory only.
 */

const DATABASE = 'stiqon';
const STORE = 'motif';
const KEY = 'current';

/**
 * A note in localStorage that a motif is stored. Other tabs hear about a change
 * through it, and the next page reads it before anything is painted (see the
 * script the theme prints into the head).
 */
export const HINT = 'stiqon:motif';

/** No view of the shop is wider than this many pixels, even on dense screens. */
const LONGEST_SIDE = 1600;
const KEEP_AS_IS_BELOW = 2 * 1024 * 1024;

function open() {
    return new Promise((resolve, reject) => {
        let request;

        try {
            request = window.indexedDB.open(DATABASE, 1);
        } catch (error) {
            reject(error);

            return;
        }

        request.onupgradeneeded = () => request.result.createObjectStore(STORE);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
        request.onblocked = () => reject(new Error('The database is in use by an older page.'));
    });
}

function run(mode, work) {
    return open().then((database) => new Promise((resolve, reject) => {
        const transaction = database.transaction(STORE, mode);
        const request = work(transaction.objectStore(STORE));

        transaction.oncomplete = () => {
            database.close();
            resolve(request.result);
        };

        transaction.onerror = transaction.onabort = () => {
            database.close();
            reject(transaction.error);
        };
    }));
}

/** The stored motif – { name, savedAt, blob } – or null. */
export function read() {
    return run('readonly', (store) => store.get(KEY)).then((record) => record ?? null);
}

export function write(record) {
    return run('readwrite', (store) => store.put(record, KEY));
}

export function remove() {
    return run('readwrite', (store) => store.delete(KEY));
}

/** Leaves the note for other tabs and the next page, or takes it away (null). */
export function hint(value) {
    try {
        if (value === null) {
            window.localStorage.removeItem(HINT);
        } else {
            window.localStorage.setItem(HINT, String(value));
        }
    } catch {
        // Without the note the motif simply appears a moment later on the next page.
    }
}

/**
 * A copy of an image file that is no larger than the shop needs. Small files
 * are kept as they are. Rejects when the browser cannot read the file as an
 * image – a file's name and type are no proof that it is one.
 */
export async function preview(file) {
    const bitmap = await createImageBitmap(file);
    const longest = Math.max(bitmap.width, bitmap.height);

    if (longest <= LONGEST_SIDE && file.size <= KEEP_AS_IS_BELOW) {
        bitmap.close();

        return file;
    }

    const scale = Math.min(1, LONGEST_SIDE / longest);
    const canvas = document.createElement('canvas');

    canvas.width = Math.max(1, Math.round(bitmap.width * scale));
    canvas.height = Math.max(1, Math.round(bitmap.height * scale));
    canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close();

    // WebP keeps transparency; a browser that cannot write it answers with PNG.
    return new Promise((resolve, reject) => {
        canvas.toBlob((blob) => (blob ? resolve(blob) : reject(new Error('The image could not be encoded.'))), 'image/webp', 0.9);
    });
}
