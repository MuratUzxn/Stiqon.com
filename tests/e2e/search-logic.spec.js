import { readFileSync } from 'node:fs';
import { test, expect, fixture } from './fixtures.js';
import { fold, rank, stem, words, MAX_WORDS } from '../../wp-content/themes/stiqon/assets/src/js/search-match.js';

/**
 * The instant results are ranked in the browser, the results page on the
 * server. Both are held to the same cases: here the script's side, in
 * tests/php/Search/MatcherTest.php the plugin's.
 */
const cases = JSON.parse(readFileSync(fixture('search-cases.json'), 'utf8'));

test.describe('search, the script\'s side of the shared cases', () => {
    test.skip(({ isMobile }) => isMobile, 'Logic without a page: once is enough.');

    test('text is brought into its plain form', () => {
        for (const [text, expected] of cases.fold) {
            expect(fold(text), JSON.stringify(text)).toBe(expected);
        }
    });

    test('words lose the endings of German grammar', () => {
        for (const [word, expected] of cases.stem) {
            expect(stem(word), word).toBe(expected);
        }
    });

    test('a query is cut into at most so many words', () => {
        expect(words('  Runder   Sticker, A5! ')).toEqual(['rund', 'stick', 'a5']);
        expect(words(' \t–– ')).toEqual([]);
        expect(words('0')).toEqual(['0']);
        expect(words('eins zwei drei vier fuenf sechs sieben acht neun')).toHaveLength(MAX_WORDS);
    });

    test('the queries find what they should in the right order', () => {
        for (const { query, limit, expect: expected, why } of cases.queries) {
            expect(rank(cases.entries, query, limit), `"${query}": ${why}`).toEqual(expected);
        }
    });

    test('without a limit everything that fits is returned', () => {
        expect(rank(cases.entries, 'sticker')).toEqual([0, 2, 5, 7]);
    });
});
