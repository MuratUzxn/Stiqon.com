<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Letter;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Mockup\Uid;

/**
 * A letterhead is drawn as one sheet with the company's logo, in A4 or A5,
 * alone or on a stack. The logo is where the visitor's motif goes.
 */
final class LetterTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_every_format(): void
    {
        $this->assertGreaterThanOrEqual(8, count(Letter::motifs()));

        foreach (Letter::motifs() as $motif) {
            foreach (array_keys(Letter::form()['values']) as $format) {
                foreach ([false, true] as $stack) {
                    $svg = stiqon_mockup('letter', compact('format', 'motif', 'stack'));

                    $this->assertWellFormed($svg, "{$motif} on {$format}");
                    $this->assertStringContainsString('data-mockup="letter"', $svg);
                    $this->assertStringContainsString('data-motif-image', $svg);
                    $this->assertStringContainsString('data-motif-sample', $svg);
                }
            }
        }
    }

    public function test_the_logo_is_printed_whole_on_the_paper(): void
    {
        $svg = stiqon_mockup('letter', ['motif' => 'wellen']);

        $this->assertSame(2, substr_count($svg, 'preserveAspectRatio="xMidYMid meet"'), 'Sample and visitor\'s motif both stay whole.');
        // Drawn without a background of its own: the waves' pale sky is left out.
        $this->assertStringNotContainsString('#e9f0f3', $svg);
    }

    public function test_motifs_that_are_only_an_outline_on_a_sticker_are_not_offered(): void
    {
        $this->assertSame(array_values(array_diff(Sticker::motifs(), ['sprechblase', 'stern'])), Letter::motifs());
    }

    public function test_a_stack_draws_sheets_underneath(): void
    {
        $single = stiqon_mockup('letter', ['stack' => false]);
        $stack = stiqon_mockup('letter', ['stack' => true]);

        $this->assertWellFormed($stack, 'stack');
        $this->assertSame(substr_count($single, '<rect') + 2, substr_count($stack, '<rect'));
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertStringContainsString('aria-label="Briefbogen DIN A4"', stiqon_mockup('letter'));
        $this->assertStringContainsString('aria-label="Briefbogen DIN A5, Stapel"', stiqon_mockup('letter', ['format' => 'a5', 'stack' => true]));
        $this->assertStringContainsString('aria-label="Briefpapier mit Logo"', stiqon_mockup('letter', ['label' => 'Briefpapier mit Logo']));
        $this->assertSame('Briefbogen', Letter::label());
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
            'format' => [static fn () => new Letter(format: 'a3'), 'Unknown letter format [a3].'],
            'motif' => [static fn () => new Letter(motif: 'gibt-es-nicht'), 'Unknown letter motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Letter(motif: '../mockup/flyer'), 'Unknown letter motif'],
            'flyer motif' => [static fn () => new Letter(motif: 'fest'), 'Unknown letter motif [fest].'],
            'outline motif' => [static fn () => new Letter(motif: 'stern'), 'Unknown letter motif [stern].'],
            'format from a spec' => [static fn () => Letter::fromArray(['format' => 'quadrat']), 'Unknown letter format [quadrat].'],
        ];
    }

    public function test_without_values_the_first_format_and_motif_are_drawn(): void
    {
        $letter = Letter::fromArray([]);

        $this->assertSame(array_key_first(Letter::form()['values']), $letter->format);
        $this->assertSame(Letter::motifs()[0], $letter->motif);
        $this->assertFalse($letter->stack);
        $this->assertNull($letter->label);
    }

    public function test_values_of_other_kinds_are_ignored_and_switches_are_cast(): void
    {
        $letter = Letter::fromArray(['format' => 'a5', 'shape' => 'dreieck', 'material' => 'gold', 'ruling' => 'grid', 'open' => true, 'gloss' => 'ja', 'stack' => '1']);

        $this->assertSame('a5', $letter->format);
        $this->assertTrue($letter->stack);
        $this->assertFalse(Letter::fromArray(['stack' => 0])->stack);
        $this->assertWellFormed($letter->render(), 'foreign keys');
    }

    public function test_ids_stay_unique_and_references_resolve_when_letters_share_a_page(): void
    {
        $drawings = [
            stiqon_mockup('letter', ['motif' => 'siegel']),
            stiqon_mockup('letter', ['motif' => 'siegel', 'format' => 'a5', 'stack' => true]),
            stiqon_mockup('letter', ['motif' => 'siegel', 'stack' => true]),
        ];

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'le', 3);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Letter::keys();
        $form = Letter::form();

        $this->assertSame(['format', 'motif', 'stack'], array_keys($keys));
        $this->assertSame('format', $form['key']);
        $this->assertSame('Format', $form['label']);
        $this->assertSame(['a4' => 'DIN A4', 'a5' => 'DIN A5'], $form['values']);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Letter::motifs(), $keys['motif']);
        $this->assertNull($keys['stack']);
    }

    public function test_names_say_a_product_is_a_letterhead_and_its_format(): void
    {
        $this->assertSame('letter', Guess::type('Briefbogen'));
        $this->assertSame('letter', Guess::type('Briefpapier'));
        $this->assertSame('letter', Guess::type('Briefbogen DIN A4, 90 g'));
        $this->assertSame('letter', Guess::type('Mit eigenem Logo', 'Briefpapier'));

        $this->assertSame(['format' => 'a4'], Guess::product('letter', 'Briefbogen DIN A4'));
        $this->assertSame(['format' => 'a5'], Guess::product('letter', 'Briefpapier A5'));
        $this->assertSame([], Guess::product('letter', 'Briefbogen'));
        $this->assertSame([], Guess::option('letter', '90 g Offsetpapier'));
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        foreach (['Briefbogen', 'Briefbogen DIN A5', 'Briefpapier A4'] as $name) {
            foreach (['90 g', '120 g Recyclingpapier', '500 Stück'] as $option) {
                $resolved = Spec::resolve(['name' => $name], [['name' => $option]]);

                $this->assertSame('letter', $resolved['type']);
                $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), "{$name} / {$option}");
            }
        }
    }

    public function test_text_from_outside_cannot_break_out_of_an_attribute(): void
    {
        $hostile = 'A "B" <c> & \'d\'';
        $escaped = 'A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;';

        foreach (['label' => stiqon_mockup('letter', ['label' => $hostile]), 'class' => stiqon_mockup('letter', [], $hostile)] as $what => $markup) {
            $this->assertStringContainsString($escaped, $markup, $what);
            $this->assertStringNotContainsString('<c>', $markup, $what);
        }
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('letter'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('letter', [], 'md:w-1/2'));
    }

    /**
     * Every url(#…) and href="#…" of one drawing points to an id of that drawing.
     */
    private function assertReferencesResolve(string $svg): void
    {
        preg_match_all('/\sid="([^"]+)"/', $svg, $ids);
        preg_match_all('/url\(#([^)]+)\)|href="#([^"]+)"/', $svg, $references);

        $referenced = array_unique(array_filter(array_merge($references[1], $references[2])));

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
