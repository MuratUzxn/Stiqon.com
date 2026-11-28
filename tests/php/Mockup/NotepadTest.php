<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Notepad;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Mockup\Uid;

/**
 * A notepad is drawn as a glued pad with the company's logo at the top of the
 * sheet and its ruling below, in A6, A5 or A4.
 */
final class NotepadTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_every_format_and_ruling(): void
    {
        $this->assertGreaterThanOrEqual(8, count(Notepad::motifs()));

        foreach (Notepad::motifs() as $motif) {
            foreach (array_keys(Notepad::form()['values']) as $format) {
                foreach (array_keys(Notepad::RULINGS) as $ruling) {
                    $svg = stiqon_mockup('notepad', compact('format', 'motif', 'ruling'));

                    $this->assertWellFormed($svg, "{$motif} on {$format}, {$ruling}");
                    $this->assertStringContainsString('data-mockup="notepad"', $svg);
                    $this->assertStringContainsString('data-motif-image', $svg);
                    $this->assertStringContainsString('data-motif-sample', $svg);
                }
            }
        }
    }

    public function test_the_logo_is_printed_whole_on_the_paper(): void
    {
        $svg = stiqon_mockup('notepad', ['motif' => 'wellen']);

        $this->assertSame(2, substr_count($svg, 'preserveAspectRatio="xMidYMid meet"'), 'Sample and visitor\'s motif both stay whole.');
        // Drawn without a background of its own: the waves' pale sky is left out.
        $this->assertStringNotContainsString('#e9f0f3', $svg);
    }

    public function test_motifs_that_are_only_an_outline_on_a_sticker_are_not_offered(): void
    {
        $this->assertSame(array_values(array_diff(Sticker::motifs(), ['sprechblase', 'stern'])), Notepad::motifs());
    }

    public function test_the_ruling_is_drawn_as_chosen_and_keeps_its_size_in_millimetres(): void
    {
        $lined = $this->ruling(stiqon_mockup('notepad', ['ruling' => 'lined']));
        $grid = $this->ruling(stiqon_mockup('notepad', ['ruling' => 'grid']));

        $this->assertNotNull($lined);
        $this->assertNotNull($grid);
        $this->assertStringNotContainsString('V', $lined, 'Lines run across only.');
        $this->assertStringContainsString('V', $grid, 'Squares need lines down, too.');
        $this->assertNull($this->ruling(stiqon_mockup('notepad', ['ruling' => 'blank'])));

        // The same 8 mm between lines: fewer of them on the smaller pad.
        $lines = fn (string $format): int => substr_count((string) $this->ruling(stiqon_mockup('notepad', ['format' => $format])), 'M');

        $this->assertLessThan($lines('a5'), $lines('a6'));
        $this->assertLessThan($lines('a4'), $lines('a5'));
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertStringContainsString('aria-label="Notizblock DIN A5, liniert"', stiqon_mockup('notepad'));
        $this->assertStringContainsString('aria-label="Notizblock DIN A6, kariert"', stiqon_mockup('notepad', ['format' => 'a6', 'ruling' => 'grid']));
        $this->assertStringContainsString('aria-label="Notizblock DIN A4, blanko"', stiqon_mockup('notepad', ['format' => 'a4', 'ruling' => 'blank']));
        $this->assertStringContainsString('aria-label="Block mit Logo"', stiqon_mockup('notepad', ['label' => 'Block mit Logo']));
        $this->assertSame('Notizblock', Notepad::label());
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
            'format' => [static fn () => new Notepad(format: 'a7'), 'Unknown notepad format [a7].'],
            'motif' => [static fn () => new Notepad(motif: 'gibt-es-nicht'), 'Unknown notepad motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Notepad(motif: '../mockup/flyer'), 'Unknown notepad motif'],
            'flyer motif' => [static fn () => new Notepad(motif: 'kursplan'), 'Unknown notepad motif [kursplan].'],
            'outline motif' => [static fn () => new Notepad(motif: 'sprechblase'), 'Unknown notepad motif [sprechblase].'],
            'ruling' => [static fn () => new Notepad(ruling: 'dotted'), 'Unknown notepad ruling [dotted].'],
            'ruling by its German name' => [static fn () => Notepad::fromArray(['ruling' => 'kariert']), 'Unknown notepad ruling [kariert].'],
        ];
    }

    public function test_without_values_the_first_format_motif_and_ruling_are_drawn(): void
    {
        $notepad = Notepad::fromArray([]);

        $this->assertSame(array_key_first(Notepad::form()['values']), $notepad->format);
        $this->assertSame(Notepad::motifs()[0], $notepad->motif);
        $this->assertSame(Notepad::keys()['ruling'][0], $notepad->ruling);
        $this->assertNull($notepad->label);
    }

    public function test_values_of_other_kinds_are_ignored(): void
    {
        $notepad = Notepad::fromArray(['format' => 'a6', 'ruling' => 'grid', 'shape' => 'dreieck', 'material' => 'gold', 'stack' => true, 'open' => '1', 'gloss' => true]);

        $this->assertSame('a6', $notepad->format);
        $this->assertSame('grid', $notepad->ruling);
        $this->assertWellFormed($notepad->render(), 'foreign keys');
    }

    public function test_ids_stay_unique_and_references_resolve_when_notepads_share_a_page(): void
    {
        $drawings = [
            stiqon_mockup('notepad', ['motif' => 'siegel']),
            stiqon_mockup('notepad', ['motif' => 'siegel', 'format' => 'a6', 'ruling' => 'grid']),
            stiqon_mockup('notepad', ['format' => 'a4', 'ruling' => 'blank']),
        ];

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'np', 3);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Notepad::keys();
        $form = Notepad::form();

        $this->assertSame(['format', 'motif', 'ruling'], array_keys($keys));
        $this->assertSame('format', $form['key']);
        $this->assertSame('Format', $form['label']);
        $this->assertSame(['a5' => 'DIN A5', 'a6' => 'DIN A6', 'a4' => 'DIN A4'], $form['values']);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Notepad::motifs(), $keys['motif']);
        $this->assertSame(['lined', 'grid', 'blank'], $keys['ruling']);
    }

    public function test_names_say_a_product_is_a_notepad_its_format_and_ruling(): void
    {
        $this->assertSame('notepad', Guess::type('Notizblöcke'));
        $this->assertSame('notepad', Guess::type('Schreibblock'));
        $this->assertSame('notepad', Guess::type('Haftnotizen'));
        $this->assertSame('notepad', Guess::type('Notizblock DIN A5, 50 Blatt'));

        $this->assertSame(['format' => 'a6'], Guess::product('notepad', 'Notizblock DIN A6'));
        $this->assertSame(['format' => 'a4'], Guess::product('notepad', 'Schreibblock A4'));
        $this->assertSame([], Guess::product('notepad', 'Haftnotizen'));

        $this->assertSame(['format' => 'a5'], Guess::option('notepad', 'DIN A5'));
        $this->assertSame(['format' => 'a6'], Guess::option('notepad', 'DIN A6'));
        $this->assertSame(['ruling' => 'grid'], Guess::option('notepad', 'kariert'));
        $this->assertSame(['ruling' => 'grid'], Guess::option('notepad', 'Karo 5 mm'));
        $this->assertSame(['ruling' => 'blank'], Guess::option('notepad', 'blanko'));
        $this->assertSame(['ruling' => 'blank'], Guess::option('notepad', 'Unliniert'));
        $this->assertSame(['ruling' => 'blank'], Guess::option('notepad', 'leer'));
        $this->assertSame(['ruling' => 'lined'], Guess::option('notepad', 'Liniert'));
        $this->assertSame([], Guess::option('notepad', '50 Blatt'));
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        foreach (['Notizblöcke', 'Schreibblock A4', 'Haftnotizen', 'Notizblock DIN A6'] as $name) {
            foreach (['kariert', 'DIN A5', 'unliniert', '50 Blatt', '90 g'] as $option) {
                $resolved = Spec::resolve(['name' => $name], [['name' => $option]]);

                $this->assertSame('notepad', $resolved['type']);
                $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), "{$name} / {$option}");
            }
        }

        $resolved = Spec::resolve(['name' => 'Notizblock A6'], [['name' => 'kariert']]);

        $this->assertSame('a6', $resolved['spec']['format']);
        $this->assertSame('grid', $resolved['spec']['ruling']);
    }

    public function test_text_from_outside_cannot_break_out_of_an_attribute(): void
    {
        $hostile = 'A "B" <c> & \'d\'';
        $escaped = 'A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;';

        foreach (['label' => stiqon_mockup('notepad', ['label' => $hostile]), 'class' => stiqon_mockup('notepad', [], $hostile)] as $what => $markup) {
            $this->assertStringContainsString($escaped, $markup, $what);
            $this->assertStringNotContainsString('<c>', $markup, $what);
        }
    }

    /**
     * The path of the ruling, drawn in its blue-grey, or null for a blank sheet.
     */
    private function ruling(string $svg): ?string
    {
        return preg_match_all('/<path\s+d="([^"]+)"\s+stroke="#457b9d"/', $svg, $paths) === 1 ? $paths[1][0] : null;
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
