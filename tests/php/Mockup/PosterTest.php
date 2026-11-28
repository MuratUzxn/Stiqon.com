<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Flyer;
use Stiqon\Shop\Mockup\Poster;
use Stiqon\Shop\Mockup\Uid;

/**
 * A poster hangs from two binder clips on a string, in four DIN formats, on
 * matt or glossy paper. Clips and string keep their size, so a format shows
 * how large it is.
 */
final class PosterTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_every_format_matt_and_glossy(): void
    {
        $this->assertGreaterThanOrEqual(5, count(Poster::motifs()));

        foreach (Poster::motifs() as $motif) {
            foreach (array_keys(Poster::form()['values']) as $format) {
                foreach ([false, true] as $gloss) {
                    $svg = stiqon_mockup('poster', compact('format', 'motif', 'gloss'));

                    $this->assertWellFormed($svg, "{$motif} as {$format}");
                    $this->assertStringContainsString('data-mockup="poster"', $svg);
                    $this->assertStringContainsString('data-motif-image', $svg);
                    $this->assertStringContainsString('data-motif-sample', $svg);
                }
            }
        }
    }

    public function test_the_motif_fills_the_sheet_on_white_paper(): void
    {
        $poster = new Poster(format: 'a1', motif: 'fest');
        $svg = $poster->render();
        $box = sprintf('x="%s" y="%s" width="%s" height="%s"', $poster->sheet['x'], $poster->sheet['y'], $poster->sheet['width'], $poster->sheet['height']);

        $this->assertEqualsWithDelta(M_SQRT2, $poster->sheet['height'] / $poster->sheet['width'], 0.01, 'A DIN sheet.');
        $this->assertStringContainsString('<svg '.$box.' viewBox="0 0 100 141.4" preserveAspectRatio="xMidYMid slice"', $svg);
        $this->assertStringContainsString('<rect '.$box.' fill="#fff" />', $svg, 'White paper under the visitor\'s motif.');
        $this->assertStringContainsString('<image '.$box.' preserveAspectRatio="xMidYMid slice"', $svg);
        $this->assertStringContainsString('>Sommerfest</text>', $svg);
    }

    public function test_a_larger_format_hangs_larger_from_the_same_clips(): void
    {
        $heights = [];

        foreach (array_keys(Poster::FORMATS) as $format) {
            $poster = new Poster(format: $format);
            $svg = $poster->render();
            $sheet = $poster->sheet;
            $heights[$format] = $sheet['height'];

            // Two clips, each as wide in every format.
            $this->assertSame(1, preg_match('/<path d="([^"]+)" fill="url\(#'.$poster->uid.'-clip\)"/', $svg, $jaws), $format);
            $this->assertSame(2, preg_match_all('/M([\d.]+) [\d.]+V[\d.]+Q[\d.]+ [\d.]+ [\d.]+ [\d.]+H[\d.]+Q([\d.]+) /', $jaws[1], $edges), $format);

            foreach (array_keys($edges[0]) as $jaw) {
                $this->assertEqualsWithDelta(6.4, (float) $edges[2][$jaw] - (float) $edges[1][$jaw], 0.01, "{$format}: jaw {$jaw}");
            }

            $this->assertCount(2, $poster->clips);
            $this->assertGreaterThan($sheet['x'] + 4, $poster->clips[0], "{$format}: the left clip grips the sheet");
            $this->assertLessThan($sheet['x'] + $sheet['width'] - 4, $poster->clips[1], "{$format}: the right clip grips the sheet");
            $this->assertLessThan($sheet['y'], $poster->line, "{$format}: the string runs above the sheet");

            // Inside the card, with room below for the shadow.
            $this->assertGreaterThanOrEqual(10.0, $sheet['x'], $format);
            $this->assertLessThanOrEqual(110.0, $sheet['x'] + $sheet['width'], $format);
            $this->assertLessThanOrEqual(108.0, $sheet['y'] + $sheet['height'], $format);

            foreach ($poster->pins as [$x, $y]) {
                $this->assertGreaterThanOrEqual(9.0, $x, $format);
                $this->assertLessThanOrEqual(111.0, $x, $format);
                $this->assertGreaterThanOrEqual(9.0, $y, $format);
            }
        }

        $this->assertSame(array_values($heights), array_values(array_unique($heights)));
        $sorted = $heights;
        sort($sorted);
        $this->assertSame(array_values($heights), $sorted, 'From A3 up to A0, each format is larger.');
        $this->assertGreaterThan(84.0, $heights['a0'], 'A0 nearly fills the card.');
        $this->assertLessThan(0.75 * $heights['a0'], $heights['a3'], 'A3 is clearly smaller.');
        $this->assertGreaterThan(0.5 * $heights['a0'], $heights['a3'], 'A3 is still a poster.');
    }

    public function test_gloss_shows_as_a_band_of_light(): void
    {
        $this->assertStringContainsString('fill="url(#po1-gloss)"', stiqon_mockup('poster', ['gloss' => true]));

        Uid::reset();
        $this->assertStringNotContainsString('fill="url(#po1-gloss)"', stiqon_mockup('poster', ['gloss' => false]));
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertSame('Plakat', Poster::label());
        $this->assertStringContainsString('aria-label="Plakat DIN A3"', stiqon_mockup('poster'));
        $this->assertStringContainsString('aria-label="Plakat DIN A2"', stiqon_mockup('poster', ['format' => 'a2']));
        $this->assertStringContainsString('aria-label="Plakat DIN A1"', stiqon_mockup('poster', ['format' => 'a1']));
        $this->assertStringContainsString('aria-label="Plakat DIN A0, glänzend"', stiqon_mockup('poster', ['format' => 'a0', 'gloss' => true]));
        $this->assertStringContainsString('aria-label="Plakat für den Flohmarkt"', stiqon_mockup('poster', ['label' => 'Plakat für den Flohmarkt']));
    }

    /**
     * @dataProvider unknownValues
     */
    public function test_unknown_values_are_rejected(callable $build, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $build();
    }

    /**
     * @return array<string, array{callable, string}>
     */
    public static function unknownValues(): array
    {
        return [
            'format' => [static fn () => new Poster(format: 'a4'), 'Unknown poster format [a4].'],
            'format in capitals' => [static fn () => Poster::fromArray(['format' => 'A3']), 'Unknown poster format [A3].'],
            'motif' => [static fn () => new Poster(motif: 'gibt-es-nicht'), 'Unknown poster motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Poster(motif: '../mockup/flyer'), 'Unknown poster motif'],
            'sticker motif' => [static fn () => Poster::fromArray(['motif' => 'siegel']), 'Unknown poster motif [siegel].'],
        ];
    }

    public function test_without_values_the_first_format_and_motif_are_drawn_matt(): void
    {
        $poster = Poster::fromArray([]);

        $this->assertSame(array_key_first(Poster::form()['values']), $poster->format);
        $this->assertSame('a3', $poster->format);
        $this->assertSame(Poster::motifs()[0], $poster->motif);
        $this->assertFalse($poster->gloss);
        $this->assertNull($poster->label);
        $this->assertSame((new Poster())->format, $poster->format);
        $this->assertSame((new Poster())->motif, $poster->motif);
    }

    public function test_values_of_other_kinds_are_ignored_and_switches_are_cast(): void
    {
        $poster = Poster::fromArray(['shape' => 'dreieck', 'material' => 'gold', 'variant' => 'desk', 'stack' => true, 'heavy' => true, 'open' => true, 'gloss' => '1']);

        $this->assertSame('a3', $poster->format);
        $this->assertTrue($poster->gloss);
        $this->assertFalse(Poster::fromArray(['gloss' => 0])->gloss);
        $this->assertFalse(Poster::fromArray(['gloss' => ''])->gloss);
        $this->assertWellFormed($poster->render(), 'foreign keys');
    }

    public function test_ids_stay_unique_and_references_resolve_when_posters_share_a_page(): void
    {
        $drawings = [
            stiqon_mockup('poster', ['gloss' => true]),
            stiqon_mockup('poster', ['format' => 'a0', 'motif' => 'fest', 'gloss' => true]),
            stiqon_mockup('poster', ['format' => 'a1', 'motif' => 'kursplan']),
        ];

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'po', 3);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Poster::keys();
        $form = Poster::form();

        $this->assertSame(['format', 'motif', 'gloss'], array_keys($keys));
        $this->assertSame('format', $form['key']);
        $this->assertSame('Format', $form['label']);
        $this->assertSame(['a3' => 'DIN A3', 'a2' => 'DIN A2', 'a1' => 'DIN A1', 'a0' => 'DIN A0'], $form['values']);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Poster::motifs(), $keys['motif']);
        $this->assertSame(Flyer::motifs(), Poster::motifs());
        $this->assertNull($keys['gloss']);

        $names = Poster::names();
        $this->assertSame(array_keys($form['values']), array_keys($names['form']));
        $this->assertSame(['format'], array_keys($names['options']));
        $this->assertSame(['gloss'], array_keys($names['switches']));
    }

    public function test_names_say_a_product_is_a_poster_and_how_large_and_glossy_it_is(): void
    {
        foreach (['Plakate', 'Poster auf Fotopapier', 'Plakate DIN A1', 'City-Light-Poster'] as $name) {
            $this->assertSame('poster', Guess::type($name), $name);
        }

        $this->assertSame('poster', Guess::type('DIN A2', 'Plakate'));

        $this->assertSame(['format' => 'a3'], Guess::product('poster', 'Plakate DIN A3'));
        $this->assertSame(['format' => 'a2'], Guess::product('poster', 'Poster A2'));
        $this->assertSame(['format' => 'a1'], Guess::product('poster', 'Plakate DIN A1'));
        $this->assertSame(['format' => 'a0'], Guess::product('poster', 'Plakat DIN A0'));
        $this->assertSame(['format' => 'a0'], Guess::product('poster', 'City-Light-Poster'));
        $this->assertSame([], Guess::product('poster', 'Plakate'));
        $this->assertSame([], Guess::product('poster', 'Poster auf Fotopapier'));

        $this->assertSame(['format' => 'a3'], Guess::option('poster', 'DIN A3'));
        $this->assertSame(['format' => 'a2'], Guess::option('poster', 'DIN A2'));
        $this->assertSame(['format' => 'a1'], Guess::option('poster', 'DIN A1'));
        $this->assertSame(['format' => 'a0'], Guess::option('poster', 'DIN A0'));
        $this->assertSame(['gloss' => true], Guess::option('poster', 'Fotopapier'));
        $this->assertSame(['gloss' => true], Guess::option('poster', '200 g Bilderdruck glänzend'));
        $this->assertSame(['gloss' => false], Guess::option('poster', '135 g Bilderdruck matt'));
        $this->assertSame([], Guess::option('poster', '250 Stück'));
        $this->assertSame([], Guess::option('poster', 'Auflage 10'));
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        $resolved = Spec::resolve(['name' => 'Poster auf Fotopapier'], [['name' => 'DIN A1'], ['name' => 'Fotopapier glänzend']]);

        $this->assertSame('poster', $resolved['type']);
        $this->assertSame('a1', $resolved['spec']['format']);
        $this->assertTrue($resolved['spec']['gloss']);
        $this->assertContains($resolved['spec']['motif'], Poster::motifs());
        $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), 'resolved');

        foreach (['Plakate', 'Plakate DIN A1', 'City-Light-Poster'] as $name) {
            $resolved = Spec::resolve(['name' => $name], [['name' => 'Bilderdruck matt']]);

            $this->assertSame('poster', $resolved['type'], $name);
            $this->assertFalse($resolved['spec']['gloss'], $name);
            $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), $name);
        }
    }

    public function test_text_from_outside_cannot_break_out_of_an_attribute(): void
    {
        $hostile = 'A "B" <c> & \'d\'';
        $escaped = 'A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;';

        foreach (['label' => stiqon_mockup('poster', ['label' => $hostile]), 'class' => stiqon_mockup('poster', ['format' => 'a0'], $hostile)] as $what => $markup) {
            $this->assertStringContainsString($escaped, $markup, $what);
            $this->assertStringNotContainsString('<c>', $markup, $what);
        }
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b[^"]*\blift\b/', stiqon_mockup('poster'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('poster', [], 'w-40'));
        $this->assertStringContainsString('viewBox="0 0 120 120"', stiqon_mockup('poster'));
    }

    /**
     * Every url(#…) and href="#…" of one drawing points to an id of that drawing.
     */
    private function assertReferencesResolve(string $svg): void
    {
        preg_match_all('/\sid="([^"]+)"/', $svg, $ids);
        preg_match_all('/url\(#([^)]+)\)|href="#([^"]+)"/', $svg, $references);

        $referenced = array_unique(array_filter(array_merge($references[1], $references[2])));

        $this->assertNotEmpty($referenced);
        $this->assertSame([], array_values(array_diff($referenced, $ids[1])), 'References point nowhere.');
    }

    /**
     * No id repeats on a page, and every id starts with the uid of its drawing.
     */
    private function assertIdsAreUnique(string $page, string $prefix, int $drawings): void
    {
        preg_match_all('/\sid="([^"]+)"/', $page, $ids);

        $this->assertGreaterThanOrEqual($drawings, count($ids[1]));
        $this->assertSame([], array_keys(array_filter(array_count_values($ids[1]), static fn (int $count): bool => $count > 1)), 'Ids repeat.');

        foreach ($ids[1] as $id) {
            $this->assertMatchesRegularExpression('/\A'.$prefix.'\d+-/', $id);
        }
    }

    private function assertWellFormed(string $svg, string $what): void
    {
        $previous = libxml_use_internal_errors(true);
        // The markup is HTML, checked here with an XML parser: Alpine's attributes carry a
        // colon without declaring a namespace, and two attributes come without a value.
        $xml = preg_replace('/\sx-[a-z]+:[a-z-]+=/', ' data-x=', $svg);
        $xml = preg_replace('/\s(x-cloak|data-motif-image|data-motif-sample)(?=[\s\/>])/', ' $1=""', $xml);
        $document = simplexml_load_string($xml);
        $errors = array_map(static fn ($error): string => trim($error->message), libxml_get_errors());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($document, "Not well-formed: {$what} – ".implode('; ', $errors));
        $this->assertSame('svg', $document->getName(), $what);
    }
}
