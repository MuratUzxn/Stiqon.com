<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Envelope;
use Stiqon\Shop\Mockup\Kinds;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Mockup\Uid;

/**
 * Envelopes: three formats, with or without a window, the sender's logo
 * printed in the corner. Every combination the shop can ask for has to render,
 * and the names of products and options have to lead to it.
 */
final class EnvelopeTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_logo_renders_on_every_format_with_and_without_a_window(): void
    {
        $this->assertSame(Envelope::motifs(), Sticker::motifs(), 'The logos are the sticker motifs.');

        foreach (Envelope::motifs() as $motif) {
            foreach (array_keys(Envelope::FORMATS) as $format) {
                foreach ([false, true] as $window) {
                    $svg = stiqon_mockup('envelope', compact('format', 'motif', 'window'));

                    $this->assertWellFormed($svg, "{$motif} on {$format}".($window ? ' with a window' : ''));
                    $this->assertStringContainsString('data-motif-sample', $svg);
                    $this->assertStringContainsString('data-motif-image', $svg);
                    $this->assertSame(1, substr_count($svg, 'x-data="motifLayer"'), 'One printed logo.');
                }
            }
        }
    }

    public function test_the_logo_is_printed_whole_on_white_paper(): void
    {
        $svg = stiqon_mockup('envelope', ['motif' => 'monogramm']);

        // "meet": the whole logo, not a crop of it; no white box under the visitor's motif.
        $this->assertStringContainsString('preserveAspectRatio="xMidYMid meet"', $svg);
        $this->assertStringNotContainsString('preserveAspectRatio="xMidYMid slice"', $svg);
        $this->assertStringNotContainsString('<rect width="100" height="100" fill="#1f3d2e"', $svg, 'The monogram comes without its green ground.');

        // Every logo in dark ink, the star as well: its white lettering would vanish on white paper.
        foreach (Envelope::motifs() as $motif) {
            $this->assertSame(['bare' => true, 'ink' => 'dark'], (new Envelope(motif: $motif))->motifVars(), $motif);
        }

        $star = stiqon_mockup('envelope', ['motif' => 'stern']);
        $this->assertStringNotContainsString('fill="#c8553d"', $star, 'The star comes without its red ground.');
        $this->assertStringContainsString('fill="#1d1d1b">Top!', $star);
        $this->assertStringNotContainsString('fill="#ffffff"', $star);
    }

    public function test_the_window_changes_the_drawing_and_keeps_the_address_inside(): void
    {
        $without = new Envelope();
        $with = new Envelope(window: true);

        $this->assertNull($without->windowBox());
        $this->assertStringNotContainsString('-film', $without->render());
        $this->assertStringContainsString('url(#'.$with->uid.'-film)', $with->render());

        foreach (array_keys(Envelope::FORMATS) as $format) {
            $envelope = new Envelope(format: $format, window: true);
            $window = $envelope->windowBox();
            $logo = $envelope->logo();

            $this->assertCount(3, $envelope->address());

            foreach ($envelope->address() as [$x, $y, $length]) {
                $this->assertGreaterThan($window['x'], $x, $format);
                $this->assertLessThan($window['x'] + $window['width'], $x + $length, $format);
                $this->assertGreaterThan($window['y'], $y, $format);
                $this->assertLessThan($window['y'] + $window['height'], $y + 1.5, $format);
            }

            // The logo stays above the window.
            $this->assertLessThan($window['y'], $logo['y'] + $logo['height'], $format);
        }
    }

    public function test_every_drawing_keeps_inside_the_box(): void
    {
        foreach (array_keys(Envelope::FORMATS) as $format) {
            $envelope = new Envelope(format: $format);
            $sheet = $envelope->sheet;
            $turn = deg2rad($envelope->tilt);

            foreach ([[0, 0], [$sheet['width'], 0], [$sheet['width'], $sheet['height']], [0, $sheet['height']]] as [$x, $y]) {
                // The corner turned about the middle of the box, as the template turns the envelope.
                $dx = $sheet['x'] + $x - 60;
                $dy = $sheet['y'] + $y - 60;
                $turnedX = 60 + $dx * cos($turn) - $dy * sin($turn);
                $turnedY = 60 + $dx * sin($turn) + $dy * cos($turn);

                $this->assertGreaterThanOrEqual(10.0, round($turnedX, 2), "{$format}: x");
                $this->assertLessThanOrEqual(110.0, round($turnedX, 2), "{$format}: x");
                $this->assertGreaterThanOrEqual(10.0, round($turnedY, 2), "{$format}: y");
                $this->assertLessThanOrEqual(110.0, round($turnedY, 2), "{$format}: y");
            }
        }

        $c4 = (new Envelope(format: 'c4'))->sheet;
        $this->assertGreaterThan($c4['width'], $c4['height'], 'C4 stands upright.');
    }

    public function test_an_envelope_describes_itself_for_screen_readers(): void
    {
        $this->assertSame('Briefumschlag DIN lang', Envelope::fromArray([])->describe());
        $this->assertSame('Briefumschlag DIN C5', Envelope::fromArray(['format' => 'c5'])->describe());
        $this->assertSame('Briefumschlag DIN C4 mit Fenster', Envelope::fromArray(['format' => 'c4', 'window' => true])->describe());
        $this->assertSame('Kuvertierhülle', Envelope::fromArray(['label' => 'Kuvertierhülle', 'window' => true])->describe());

        $this->assertStringContainsString('aria-label="Briefumschlag DIN lang mit Fenster"', stiqon_mockup('envelope', ['window' => true]));
        $this->assertStringContainsString('aria-label="A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;"', stiqon_mockup('envelope', ['label' => 'A "B" <c> & \'d\'']));
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
            'format' => [static fn () => new Envelope(format: 'c6'), 'Unknown envelope format [c6].'],
            'format from a spec' => [static fn () => Envelope::fromArray(['format' => 'DIN lang']), 'Unknown envelope format [DIN lang].'],
            'motif' => [static fn () => new Envelope(motif: 'gibt-es-nicht'), 'Unknown envelope motif [gibt-es-nicht].'],
            'flyer motif' => [static fn () => new Envelope(motif: 'fest'), 'Unknown envelope motif [fest].'],
            'card motif' => [static fn () => new Envelope(motif: 'atelier'), 'Unknown envelope motif [atelier].'],
            'motif outside the directory' => [static fn () => new Envelope(motif: '../mockup/envelope'), 'Unknown envelope motif [../mockup/envelope].'],
        ];
    }

    public function test_an_empty_spec_draws_the_first_format_with_the_first_motif(): void
    {
        $envelope = Envelope::fromArray([]);

        $this->assertSame(array_key_first(Envelope::form()['values']), $envelope->format);
        $this->assertSame(Envelope::motifs()[0], $envelope->motif);
        $this->assertSame((new Envelope())->motif, $envelope->motif);
        $this->assertFalse($envelope->window);
    }

    public function test_keys_of_other_kinds_are_ignored_and_switches_are_cast(): void
    {
        Uid::reset();
        $own = Envelope::fromArray(['format' => 'c5', 'window' => true])->render();

        Uid::reset();
        $mixed = Envelope::fromArray([
            'format' => 'c5',
            'window' => 'ja',
            'fold' => true,
            'stack' => true,
            'finish' => 'gold',
            'shape' => 'dreieck',
            'type' => 'card',
        ])->render();

        $this->assertSame($own, $mixed);
        $this->assertFalse(Envelope::fromArray(['window' => 0])->window);
        $this->assertFalse(Envelope::fromArray(['window' => ''])->window);
    }

    public function test_ids_stay_unique_and_references_resolve_when_many_envelopes_share_a_page(): void
    {
        $page = '';

        foreach (Envelope::motifs() as $motif) {
            foreach (array_keys(Envelope::FORMATS) as $format) {
                $page .= stiqon_mockup('envelope', compact('motif', 'format') + ['window' => $format !== 'c5']);
            }
        }

        preg_match_all('/\sid="([^"]+)"/', $page, $ids);
        // The seal motif writes along a path: its text refers to it by href.
        preg_match_all('/url\(#([^)]+)\)|href="#([^"]+)"/', $page, $references);

        $referenced = array_unique(array_filter(array_merge($references[1], $references[2])));

        $this->assertGreaterThan(40, count($ids[1]));
        $this->assertSame([], array_keys(array_filter(array_count_values($ids[1]), static fn (int $count): bool => $count > 1)), 'Ids repeat.');
        $this->assertNotEmpty($referenced);
        $this->assertSame([], array_values(array_diff($referenced, $ids[1])), 'References point nowhere.');
    }

    public function test_every_id_belongs_to_its_drawing(): void
    {
        foreach ([['window' => true, 'motif' => 'siegel'], ['format' => 'c4']] as $spec) {
            $envelope = Envelope::fromArray($spec);
            preg_match_all('/\sid="([^"]+)"/', $envelope->render(), $ids);

            $this->assertNotEmpty($ids[1]);

            foreach ($ids[1] as $id) {
                $this->assertStringStartsWith($envelope->uid.'-', $id);
            }
        }
    }

    public function test_the_drawing_itself_brings_no_lettering_styles_or_scripts(): void
    {
        $svg = stiqon_mockup('envelope', ['window' => true, 'motif' => 'siegel']);
        // Lettering belongs to the sample motif only.
        $drawing = preg_replace('/<svg[^>]*data-motif-sample[^>]*>.*?<\/svg>/s', '', $svg);

        $this->assertStringNotContainsString('<text', $drawing);
        $this->assertStringNotContainsString('<style', $svg);
        $this->assertStringNotContainsString('<script', $svg);
        $this->assertSame(1, substr_count($svg, ' class="'), 'Only the root carries classes.');
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b[^"]*\blift\b/', stiqon_mockup('envelope'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('envelope', [], 'w-40'));
    }

    public function test_keys_form_and_motifs_agree(): void
    {
        $keys = Envelope::keys();
        $form = Envelope::form();

        $this->assertSame('Briefumschlag', Envelope::label());
        $this->assertSame('format', $form['key']);
        $this->assertSame('Format', $form['label']);
        $this->assertArrayHasKey($form['key'], $keys);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(['lang' => 'DIN lang', 'c5' => 'DIN C5', 'c4' => 'DIN C4'], $form['values']);
        $this->assertSame(Envelope::motifs(), $keys['motif']);
        $this->assertArrayHasKey('window', $keys);
        $this->assertNull($keys['window']);
    }

    public function test_the_names_only_say_what_the_drawing_knows(): void
    {
        $keys = Envelope::keys();
        $names = Envelope::names();

        $this->assertSame([], array_diff(array_keys($names['form']), $keys['format']));
        $this->assertSame([], $names['options']);

        foreach (array_keys($names['switches']) as $key) {
            $this->assertArrayHasKey($key, $keys);
            $this->assertNull($keys[$key], $key);
        }
    }

    public function test_product_names_say_it_is_an_envelope_and_which_format(): void
    {
        $this->assertSame(Envelope::class, Kinds::of('envelope'));

        foreach ([
            'Briefumschläge DIN lang' => ['format' => 'lang'],
            'Briefumschläge DIN C5' => ['format' => 'c5'],
            'Briefumschläge DIN C4' => ['format' => 'c4'],
            'Versandtaschen C4' => ['format' => 'c4'],
            'Kuvertierhüllen' => [],
        ] as $name => $form) {
            $this->assertSame('envelope', Guess::type($name), $name);
            $this->assertSame($form, Guess::product('envelope', $name), $name);
        }
    }

    public function test_option_names_say_whether_there_is_a_window(): void
    {
        $this->assertSame(['window' => true], Guess::option('envelope', 'mit Fenster'));
        $this->assertSame(['window' => false], Guess::option('envelope', 'ohne Fenster'));
        $this->assertSame(['window' => true], Guess::option('envelope', 'Fenster links'));
        $this->assertSame(['window' => false], Guess::option('envelope', 'Ohne Sichtfenster'));
        $this->assertSame([], Guess::option('envelope', 'haftklebend'));
    }

    public function test_a_product_and_its_options_resolve_to_a_drawing(): void
    {
        $resolved = Spec::resolve(['name' => 'Briefumschläge DIN C5', 'categories' => ['Geschäftsausstattung']], [['name' => 'mit Fenster']]);

        $this->assertSame('envelope', $resolved['type']);
        $this->assertSame('c5', $resolved['spec']['format']);
        $this->assertTrue($resolved['spec']['window']);
        $this->assertContains($resolved['spec']['motif'], Envelope::motifs());
        $this->assertWellFormed(Kinds::draw('envelope', $resolved['spec']), 'resolved product');
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
