/**
 * Not every combination of a product's options has to exist as a variation –
 * a flyer may come as A6 on 250 g and as A5 on 135 g only.
 *
 * closest() names the choice nearest to a wanted one that does exist: the wanted
 * one itself if a variation fits it; otherwise the first variation that keeps
 * most of it, with the options in `fixed` counting more than all others
 * together.
 *
 * The plugin follows the same rule when it works out the choice a product page
 * opens with (Stiqon\Shop\Catalog\Choice::closest()). Both are held to the
 * cases in tests/fixtures/choice-cases.json.
 *
 * @param {Array<Object<string, string>>} combinations per variation: option => value, '' for "any value"
 * @param {Object<string, string>} wanted a complete choice
 * @param {string[]} fixed options whose value should stay if at all possible
 * @returns {Object<string, string>}
 */
export function closest(combinations, wanted, fixed = []) {
    const names = Object.keys(wanted);
    const heavy = names.length + 1;
    let best = null;
    let most = -1;

    for (const combination of combinations) {
        let kept = 0;

        for (const name of names) {
            const offered = combination[name] ?? '';

            if (offered === '' || offered === wanted[name]) {
                kept += fixed.includes(name) ? heavy : 1;
            }
        }

        if (kept > most) {
            best = combination;
            most = kept;
        }
    }

    if (best === null) {
        return { ...wanted };
    }

    return Object.fromEntries(names.map((name) => [name, (best[name] ?? '') === '' ? wanted[name] : best[name]]));
}
