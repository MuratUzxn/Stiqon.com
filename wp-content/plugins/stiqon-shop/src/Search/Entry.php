<?php

namespace Stiqon\Shop\Search;

/**
 * What search compares of one entry of the index.
 *
 * This class knows nothing about WordPress. Index gathers names and texts
 * from the shop and hands them in here.
 */
final class Entry
{
    /** The kinds of entries, and what each weighs between equals. */
    public const WEIGHTS = ['area' => 3, 'product' => 2, 'variant' => 1];

    /** Words of running text that say nothing about a product – folded, and as written without the dots. */
    private const FILLERS = [
        'aber', 'als', 'am', 'an', 'auch', 'auf', 'aus', 'bei', 'bis', 'da', 'das', 'dass', 'dem', 'den', 'der', 'des',
        'die', 'durch', 'ein', 'eine', 'einem', 'einen', 'einer', 'er', 'es', 'fuer', 'fur', 'ihr', 'ihre', 'ihrem',
        'ihren', 'ihres', 'im', 'in', 'ist', 'je', 'jede', 'man', 'mit', 'nach', 'nicht', 'noch', 'nur', 'oder', 'sich',
        'sie', 'sind', 'so', 'ueber', 'uber', 'um', 'und', 'vom', 'von', 'vor', 'was', 'weil', 'wenn', 'wie', 'wir',
        'zu', 'zum', 'zur',
    ];

    /**
     * @param  string  $kind  one of the keys of WEIGHTS
     * @param  string  $name  what the entry is called ("Runde Sticker")
     * @param  string  $subtitle  for a variant, the option that makes it ("Kraftpapier")
     * @param  string  $keywords  whatever else it should be found by
     * @return array{t: string, k: string, w: int, m?: bool}  "t": the name search looks in first, "k": what else
     *                                                        it is found by, "w": weight of its kind, "m": fits
     *                                                        only when a word of the query is found in "t"
     */
    public static function searchable(string $kind, string $name, string $subtitle = '', string $keywords = ''): array
    {
        // A variant is called by its option; the product it belongs to is what else it is found by.
        [$called, $also] = $kind === 'variant' ? [$subtitle, $name.' '.$keywords] : [$name, $keywords];

        $named = self::words(Text::fold($called));
        $more = array_diff(self::words(Text::fold($also)), self::FILLERS);
        // Written without the dots ("grun" for "grün") everything is found a second way.
        $loose = self::words(Text::foldLoosely($called.' '.$also));

        $searchable = [
            't' => implode(' ', $named),
            'k' => implode(' ', array_values(array_unique(array_diff(array_merge($more, $loose), $named, self::FILLERS)))),
            'w' => self::WEIGHTS[$kind],
        ];

        return $kind === 'variant' ? $searchable + ['m' => true] : $searchable;
    }

    /**
     * @return list<string>
     */
    private static function words(string $folded): array
    {
        return array_values(array_filter(explode(' ', $folded), 'strlen'));
    }
}
