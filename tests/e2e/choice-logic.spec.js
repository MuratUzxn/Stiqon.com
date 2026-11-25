import { readFileSync } from 'node:fs';
import { test, expect, fixture } from './fixtures.js';
import { closest } from '../../wp-content/themes/stiqon/assets/src/js/variation-match.js';

/**
 * Which choice a product settles on when not every combination of its options
 * exists as a variation. The product page's script decides that when a visitor
 * chooses, the plugin when a page opens. Both are held to the same cases: here
 * the script's side, in tests/php/Catalog/ChoiceTest.php the plugin's.
 */
const { cases } = JSON.parse(readFileSync(fixture('choice-cases.json'), 'utf8'));

test.describe('choosing options, the script\'s side of the shared cases', () => {
    test.skip(({ isMobile }) => isMobile, 'Logic without a page: once is enough.');

    test('a choice settles on the nearest combination that exists', () => {
        expect(cases.length).toBeGreaterThan(0);

        for (const { combinations, wanted, fixed, expect: expected, why } of cases) {
            expect(closest(combinations, wanted, fixed), why).toEqual(expected);
        }
    });

    test('the wanted choice keeps its order and every option', () => {
        const settled = closest([{ b: '2', a: '1' }], { a: '9', b: '9', c: '9' }, ['a']);

        // "c" is no option of the variation: nothing restricts it.
        expect(settled).toEqual({ a: '1', b: '2', c: '9' });
        expect(Object.keys(settled)).toEqual(['a', 'b', 'c']);
    });
});
