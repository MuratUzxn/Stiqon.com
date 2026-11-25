<?php

namespace Stiqon\Shop\Search;

/**
 * Finds the entries of the search index that fit what was typed, best first.
 *
 * An entry fits when every word of the query occurs in it – at the start of a
 * word or inside one, in its name or in what else it is found by. A word found
 * in the name counts for more, the start of a word for more than its middle.
 * Between equals, the kind of entry decides (an area before a product before
 * one of its variants), then the order of the index.
 *
 * A variant – "Runde Sticker" on kraft paper – only fits when its own name is
 * asked for: "kraft" finds it, "sticker" finds the product instead of each of
 * its variants.
 *
 * The page's script ranks the same way; both follow the cases in
 * tests/fixtures/search-cases.json.
 */
final class Matcher
{
    public const MAX_WORDS = 8;

    /**
     * @param  array<int, array{t: string, k: string, w: int, m?: bool}>  $entries  see Entry::searchable()
     * @return list<int>  positions in $entries, best first
     */
    public static function rank(array $entries, string $query, ?int $limit = null): array
    {
        $words = Text::words($query, self::MAX_WORDS);

        if ($words === []) {
            return [];
        }

        $scores = [];

        foreach ($entries as $position => $entry) {
            $score = 0;
            $named = false;

            foreach ($words as $word) {
                $inName = self::score($word, $entry['t'], 8, 4);
                $found = $inName ?: self::score($word, $entry['k'], 2, 1);

                if ($found === 0) {
                    continue 2;
                }

                $named = $named || $inName > 0;
                $score += $found;
            }

            if (! empty($entry['m']) && ! $named) {
                continue;
            }

            $scores[$position] = $score * 10 + $entry['w'];
        }

        // Best first; the order of the index breaks ties.
        uksort($scores, static fn (int $a, int $b): int => [$scores[$b], $a] <=> [$scores[$a], $b]);

        return array_slice(array_keys($scores), 0, $limit);
    }

    private static function score(string $word, string $text, int $atWordStart, int $inside): int
    {
        if ($text === '') {
            return 0;
        }

        if (str_starts_with($text, $word) || str_contains($text, ' '.$word)) {
            return $atWordStart;
        }

        return str_contains($text, $word) ? $inside : 0;
    }
}
