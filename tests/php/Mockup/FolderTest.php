<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Flyer;
use Stiqon\Shop\Mockup\Folder;
use Stiqon\Shop\Mockup\Uid;

/**
 * A presentation folder is drawn closed, with the motif on its cover, or open,
 * with the motif on its pocket – glossy or matt.
 */
final class FolderTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_every_format_open_and_closed(): void
    {
        $this->assertGreaterThanOrEqual(5, count(Folder::motifs()));

        foreach (Folder::motifs() as $motif) {
            foreach (array_keys(Folder::form()['values']) as $format) {
                foreach ([false, true] as $open) {
                    foreach ([false, true] as $gloss) {
                        $svg = stiqon_mockup('folder', compact('format', 'motif', 'open', 'gloss'));

                        $this->assertWellFormed($svg, "{$motif} as {$format}");
                        $this->assertStringContainsString('data-mockup="folder"', $svg);
                        $this->assertStringContainsString('data-motif-image', $svg);
                        $this->assertStringContainsString('data-motif-sample', $svg);
                    }
                }
            }
        }
    }

    public function test_the_motif_fills_the_cover_and_the_pocket(): void
    {
        foreach ([false, true] as $open) {
            $svg = stiqon_mockup('folder', ['open' => $open]);

            $this->assertSame(2, substr_count($svg, 'preserveAspectRatio="xMidYMid slice"'), 'Sample and visitor\'s motif both fill the print.');
            $this->assertStringContainsString('<g x-data="motifLayer" clip-path="url(#fo', $svg);
        }
    }

    public function test_open_shows_the_inside_and_gloss_a_band_of_light(): void
    {
        $closed = stiqon_mockup('folder');
        $open = stiqon_mockup('folder', ['open' => true]);

        $this->assertNotSame(preg_replace('/fo\d+/', '', $closed), preg_replace('/fo\d+/', '', $open));
        $this->assertStringContainsString('-inside)', $open);
        $this->assertStringNotContainsString('-inside)', $closed);

        foreach ([false, true] as $opened) {
            Uid::reset();
            $this->assertStringContainsString('fill="url(#fo1-gloss)"', stiqon_mockup('folder', ['open' => $opened, 'gloss' => true]));

            Uid::reset();
            $this->assertStringNotContainsString('fill="url(#fo1-gloss)"', stiqon_mockup('folder', ['open' => $opened, 'gloss' => false]));
        }
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertStringContainsString('aria-label="Präsentationsmappe DIN A4"', stiqon_mockup('folder'));
        $this->assertStringContainsString('aria-label="Präsentationsmappe DIN A4, aufgeklappt"', stiqon_mockup('folder', ['open' => true]));
        $this->assertStringContainsString('aria-label="Präsentationsmappe DIN A4, aufgeklappt, glänzend"', stiqon_mockup('folder', ['open' => true, 'gloss' => true]));
        $this->assertStringContainsString('aria-label="Mappe für Angebote"', stiqon_mockup('folder', ['label' => 'Mappe für Angebote']));
        $this->assertSame('Präsentationsmappe', Folder::label());
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
            'format' => [static fn () => new Folder(format: 'a5'), 'Unknown folder format [a5].'],
            'motif' => [static fn () => new Folder(motif: 'gibt-es-nicht'), 'Unknown folder motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Folder(motif: '../mockup/flyer'), 'Unknown folder motif'],
            'sticker motif' => [static fn () => new Folder(motif: 'siegel'), 'Unknown folder motif [siegel].'],
            'format from a spec' => [static fn () => Folder::fromArray(['format' => 'din-a4']), 'Unknown folder format [din-a4].'],
        ];
    }

    public function test_without_values_the_first_format_and_motif_are_drawn_closed_and_matt(): void
    {
        $folder = Folder::fromArray([]);

        $this->assertSame(array_key_first(Folder::form()['values']), $folder->format);
        $this->assertSame(Folder::motifs()[0], $folder->motif);
        $this->assertFalse($folder->open);
        $this->assertFalse($folder->gloss);
        $this->assertNull($folder->label);
    }

    public function test_values_of_other_kinds_are_ignored_and_switches_are_cast(): void
    {
        $folder = Folder::fromArray(['shape' => 'dreieck', 'material' => 'gold', 'ruling' => 'grid', 'stack' => true, 'heavy' => true, 'open' => '1', 'gloss' => 1]);

        $this->assertTrue($folder->open);
        $this->assertTrue($folder->gloss);
        $this->assertFalse(Folder::fromArray(['open' => '', 'gloss' => 0])->gloss);
        $this->assertWellFormed($folder->render(), 'foreign keys');
    }

    public function test_ids_stay_unique_and_references_resolve_when_folders_share_a_page(): void
    {
        $drawings = [
            stiqon_mockup('folder', ['gloss' => true]),
            stiqon_mockup('folder', ['open' => true, 'gloss' => true]),
            stiqon_mockup('folder', ['motif' => 'fest', 'open' => true]),
        ];

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'fo', 3);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Folder::keys();
        $form = Folder::form();

        $this->assertSame(['format', 'motif', 'open', 'gloss'], array_keys($keys));
        $this->assertSame('format', $form['key']);
        $this->assertSame('Format', $form['label']);
        $this->assertSame(['a4' => 'DIN A4'], $form['values']);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Folder::motifs(), $keys['motif']);
        $this->assertSame(Flyer::motifs(), Folder::motifs());
        $this->assertNull($keys['open']);
        $this->assertNull($keys['gloss']);
    }

    public function test_names_say_a_product_is_a_folder_and_how_its_cover_is_finished(): void
    {
        $this->assertSame('folder', Guess::type('Präsentationsmappen'));
        $this->assertSame('folder', Guess::type('Präsentationsmappe mit Visitenkartentasche'));
        $this->assertSame('folder', Guess::type('Mit Tasche', 'Mappen'));

        $this->assertSame(['format' => 'a4'], Guess::product('folder', 'Präsentationsmappen'));
        $this->assertSame(['format' => 'a4'], Guess::product('folder', 'Präsentationsmappe mit Visitenkartentasche'));
        $this->assertSame(['format' => 'a4'], Guess::product('folder', 'Angebotsordner DIN A4'));

        $this->assertSame(['gloss' => false], Guess::option('folder', 'Folienkaschierung matt'));
        $this->assertSame(['gloss' => false], Guess::option('folder', 'ohne Veredelung'));
        $this->assertSame(['gloss' => true], Guess::option('folder', 'Folienkaschierung glänzend'));
        $this->assertSame(['gloss' => true], Guess::option('folder', 'Cellophanierung'));
        $this->assertSame([], Guess::option('folder', '350 g Bilderdruck'));
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        foreach (['Präsentationsmappen', 'Präsentationsmappe mit Visitenkartentasche'] as $name) {
            foreach (['Folienkaschierung matt', 'ohne Veredelung', 'Glanzcellophanierung', '350 g Bilderdruck'] as $option) {
                $resolved = Spec::resolve(['name' => $name], [['name' => $option]]);

                $this->assertSame('folder', $resolved['type']);
                $this->assertSame('a4', $resolved['spec']['format']);
                $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), "{$name} / {$option}");
            }
        }

        $this->assertFalse(Spec::resolve(['name' => 'Präsentationsmappen'], [['name' => 'Folienkaschierung matt']])['spec']['gloss']);
    }

    public function test_text_from_outside_cannot_break_out_of_an_attribute(): void
    {
        $hostile = 'A "B" <c> & \'d\'';
        $escaped = 'A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;';

        foreach (['label' => stiqon_mockup('folder', ['label' => $hostile]), 'class' => stiqon_mockup('folder', ['open' => true], $hostile)] as $what => $markup) {
            $this->assertStringContainsString($escaped, $markup, $what);
            $this->assertStringNotContainsString('<c>', $markup, $what);
        }
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
