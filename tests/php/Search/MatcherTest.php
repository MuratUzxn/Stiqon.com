<?php

namespace Stiqon\Shop\Tests\Search;

use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Search\Entry;
use Stiqon\Shop\Search\Matcher;
use Stiqon\Shop\Search\Text;

/**
 * The search of the results page. The page's script ranks the instant results
 * the same way; both are held to the same cases (tests/fixtures/search-cases.json,
 * on the script's side tests/e2e/search-logic.spec.js).
 */
final class MatcherTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function cases(): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__, 2).'/fixtures/search-cases.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_text_is_brought_into_its_plain_form(): void
    {
        foreach (self::cases()['fold'] as [$text, $expected]) {
            $this->assertSame($expected, Text::fold($text), $text);
        }

        foreach (self::cases()['foldLoosely'] as [$text, $expected]) {
            $this->assertSame($expected, Text::foldLoosely($text), $text);
        }
    }

    public function test_words_lose_the_endings_of_german_grammar(): void
    {
        foreach (self::cases()['stem'] as [$word, $expected]) {
            $this->assertSame($expected, Text::stem($word), $word);
        }
    }

    public function test_a_query_is_cut_into_at_most_so_many_words(): void
    {
        $this->assertSame(['rund', 'stick', 'a5'], Text::words('  Runder   Sticker, A5! ', 8));
        $this->assertSame(['eins', 'zwei'], Text::words('eins zwei drei', 2));
        $this->assertSame([], Text::words(" \t–– ", 8));
        // "0" is a word, although PHP takes the string for false.
        $this->assertSame(['0'], Text::words('0', 8));
    }

    /**
     * The entries in the cases carry what search compares. If the plugin came
     * to compute those fields differently, the script would be tested against
     * entries the shop no longer serves.
     */
    public function test_the_entries_of_the_cases_are_what_the_plugin_computes(): void
    {
        foreach (self::cases()['entries'] as $position => $entry) {
            $this->assertSame(
                array_diff_key($entry, array_flip(['kind', 'n', 's', 'keywords'])),
                Entry::searchable($entry['kind'], $entry['n'], $entry['s'], $entry['keywords']),
                "entry {$position}, {$entry['n']}: if the plugin is right, run php tests/fixtures/update-search-cases.php",
            );
        }
    }

    public function test_the_queries_find_what_they_should_in_the_right_order(): void
    {
        $cases = self::cases();

        foreach ($cases['queries'] as $case) {
            $this->assertSame($case['expect'], Matcher::rank($cases['entries'], $case['query'], $case['limit']), "\"{$case['query']}\": {$case['why']}");
        }
    }

    public function test_without_a_limit_everything_that_fits_is_returned(): void
    {
        $this->assertSame([0, 2, 5, 7], Matcher::rank(self::cases()['entries'], 'sticker'));
    }

    public function test_a_variant_is_found_by_its_own_name_only(): void
    {
        $product = Entry::searchable('product', 'Runde Sticker', 'Sticker', 'Aufkleber');
        $variant = Entry::searchable('variant', 'Runde Sticker', 'Kraftpapier', 'Material Aufkleber');

        $this->assertSame([0], Matcher::rank([$product, $variant], 'runde sticker'), 'the product stands for its variants');
        $this->assertSame([1], Matcher::rank([$product, $variant], 'kraftpapier'), 'the option names the variant');
        $this->assertSame([1], Matcher::rank([$product, $variant], 'aufkleber kraft'), 'what the product is found by leads to the variant too');
        $this->assertSame([], Matcher::rank([$product, $variant], 'material'), 'the label of an option names no variant');
    }

    public function test_filler_words_of_a_sentence_are_not_searched(): void
    {
        $entry = Entry::searchable('product', 'Ovale Sticker', 'Sticker', 'Die freundliche Form für Dankeschön und Gruß.');

        $this->assertSame('ovale sticker', $entry['t']);
        $this->assertSame('freundliche form dankeschoen gruss dankeschon', $entry['k']);
    }

    public function test_a_name_keeps_its_filler_words(): void
    {
        // "Sticker mit Konturschnitt": in a name every word is part of what it is called.
        $this->assertSame('sticker mit konturschnitt', Entry::searchable('product', 'Sticker mit Konturschnitt')['t']);
    }
}
