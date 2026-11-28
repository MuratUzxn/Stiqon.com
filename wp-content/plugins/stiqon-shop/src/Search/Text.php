<?php

namespace Stiqon\Shop\Search;

/**
 * Brings text into the plain form in which search compares it: lower case,
 * German umlauts and common accents written out, everything that is not a
 * letter or a digit turned into a space.
 *
 * The page's script does the same to what a visitor types (see
 * assets/src/js/search-match.js in the theme); both follow the cases in
 * tests/fixtures/search-cases.json.
 */
final class Text
{
    /** "ä" as "ae": how German is written without umlauts. */
    public const WRITTEN_OUT = [
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ];

    /** Endings German puts on a word ("runde", "runder", "Stickern"), longest first. */
    public const ENDINGS = ['ern', 'em', 'en', 'er', 'es', 'e', 'n', 's'];

    /** "ä" as "a": how people type who leave the dots away ("grun"). */
    private const DOTS_LEFT_AWAY = ['ä' => 'a', 'ö' => 'o', 'ü' => 'u'];

    /** A vowel followed by the combining diaeresis: how some systems store an umlaut. */
    private const COMPOSED = ["a\u{308}" => 'ä', "o\u{308}" => 'ö', "u\u{308}" => 'ü'];

    public static function fold(string $text): string
    {
        return self::plain(strtr(self::lower($text), self::WRITTEN_OUT));
    }

    /**
     * The same with umlauts reduced to their base letter. Only texts that are
     * searched get this second form – never what a visitor types.
     */
    public static function foldLoosely(string $text): string
    {
        return self::plain(strtr(self::lower($text), self::DOTS_LEFT_AWAY + self::WRITTEN_OUT));
    }

    /**
     * The words of a query in the form they are looked for: folded, and
     * without the ending German grammar put on them.
     *
     * @return list<string>
     */
    public static function words(string $query, int $max): array
    {
        $words = array_values(array_filter(explode(' ', self::fold($query)), 'strlen'));

        return array_map([self::class, 'stem'], array_slice($words, 0, $max));
    }

    /**
     * A folded word without its ending: "runder" and "runden" are both looked
     * for as "rund", which "Runde Sticker" begins with. Short words and
     * anything with a digit in it stay as they are ("a5", "250").
     */
    public static function stem(string $word): string
    {
        if (strlen($word) < 5 || ! ctype_alpha($word)) {
            return $word;
        }

        foreach (self::ENDINGS as $ending) {
            if (str_ends_with($word, $ending) && strlen($word) - strlen($ending) >= 4) {
                return substr($word, 0, -strlen($ending));
            }
        }

        return $word;
    }

    private static function lower(string $text): string
    {
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $text = strtr($text, self::COMPOSED);

        // Any other combining mark goes: an "e" followed by an accent is an "e".
        return preg_replace('/[\x{0300}-\x{036F}]/u', '', $text) ?? $text;
    }

    private static function plain(string $text): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $text));
    }
}
