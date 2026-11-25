/**
 * How search compares text and ranks what it finds – the script's side of
 * Search\Text and Search\Matcher in the plugin "Stiqon Shop-Funktionen", which
 * do the same for the results page. Both follow the cases in
 * tests/fixtures/search-cases.json: change the two together.
 *
 * Nothing in here touches the page, so it can be tested on its own.
 */

/** "ä" as "ae": how German is written without umlauts. */
const WRITTEN_OUT = {
    ä: 'ae', ö: 'oe', ü: 'ue', ß: 'ss',
    à: 'a', á: 'a', â: 'a', ã: 'a', å: 'a',
    è: 'e', é: 'e', ê: 'e', ë: 'e',
    ì: 'i', í: 'i', î: 'i', ï: 'i',
    ò: 'o', ó: 'o', ô: 'o', õ: 'o',
    ù: 'u', ú: 'u', û: 'u',
    ç: 'c', ñ: 'n',
};

/** A vowel followed by the combining diaeresis: how some systems store an umlaut. */
const COMPOSED = { a: 'ä', o: 'ö', u: 'ü' };

/** Endings German puts on a word ("runde", "runder", "Stickern"), longest first. */
const ENDINGS = ['ern', 'em', 'en', 'er', 'es', 'e', 'n', 's'];

export const MAX_WORDS = 8;

/**
 * Text in the plain form in which it is compared: lower case, umlauts and
 * accents written out, everything that is not a letter or a digit a space.
 */
export function fold(text) {
    return text
        .toLowerCase()
        .replace(/([aou])̈/g, (match, vowel) => COMPOSED[vowel])
        .replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]/g, (character) => WRITTEN_OUT[character] ?? ' ')
        .replace(/ +/g, ' ')
        .trim();
}

/**
 * A folded word without its ending: "runder" and "runden" are both looked for
 * as "rund". Short words and anything with a digit in it stay as they are.
 */
export function stem(word) {
    if (word.length < 5 || !/^[a-z]+$/.test(word)) {
        return word;
    }

    for (const ending of ENDINGS) {
        if (word.endsWith(ending) && word.length - ending.length >= 4) {
            return word.slice(0, -ending.length);
        }
    }

    return word;
}

/** The words of a query in the form they are looked for. */
export function words(query) {
    return fold(query).split(' ').filter(Boolean).slice(0, MAX_WORDS).map(stem);
}

function score(word, text, atWordStart, inside) {
    if (!text) {
        return 0;
    }

    if (text.startsWith(word) || text.includes(` ${word}`)) {
        return atWordStart;
    }

    return text.includes(word) ? inside : 0;
}

/**
 * The entries that fit a query, best first, as positions in `entries`.
 *
 * An entry fits when every word occurs in it: in its name ("t") or in what
 * else it is found by ("k"). The name counts for more, the start of a word for
 * more than its middle. Between equals the kind of entry decides ("w"), then
 * the order of the index. An entry marked "m" – a variant of a product – only
 * fits when a word is found in its own name.
 */
export function rank(entries, query, limit = Infinity) {
    const terms = words(query);

    if (terms.length === 0) {
        return [];
    }

    const fitting = [];

    entries.forEach((entry, position) => {
        let total = 0;
        let named = false;

        for (const term of terms) {
            const inName = score(term, entry.t, 8, 4);
            const found = inName || score(term, entry.k, 2, 1);

            if (found === 0) {
                return;
            }

            named ||= inName > 0;
            total += found;
        }

        if (entry.m && !named) {
            return;
        }

        fitting.push({ position, score: total * 10 + entry.w });
    });

    return fitting
        .sort((a, b) => b.score - a.score || a.position - b.position)
        .slice(0, limit)
        .map((entry) => entry.position);
}
