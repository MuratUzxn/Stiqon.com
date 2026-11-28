<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Mockup\Tote;
use Stiqon\Shop\Mockup\Uid;

/**
 * A cotton tote lying flat: long or short handles, natural, black or white
 * cotton, a print in the middle.
 */
final class ToteTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_with_either_handles_in_every_colour(): void
    {
        $this->assertGreaterThanOrEqual(12, count(Tote::motifs()));

        foreach (Tote::motifs() as $motif) {
            foreach (array_keys(Tote::form()['values']) as $handles) {
                foreach (Tote::COLORS as $color) {
                    $svg = stiqon_mockup('tote', compact('handles', 'motif', 'color'));

                    $this->assertWellFormed($svg, "{$motif} with {$handles} handles/{$color}");
                    $this->assertStringContainsString('data-mockup="tote"', $svg);
                    $this->assertStringContainsString('data-motif-image', $svg);
                    $this->assertStringContainsString('data-motif-sample', $svg);
                }
            }
        }
    }

    public function test_the_print_sits_in_the_middle_below_the_hem(): void
    {
        foreach (array_keys(Tote::HANDLES) as $handles) {
            $tote = new Tote(handles: $handles, motif: 'siegel');
            $body = $tote->body;
            $print = $tote->printBox;

            $this->assertStringContainsString(sprintf('<svg x="%s" y="%s" width="%s" height="%s" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet"', $print['x'], $print['y'], $print['width'], $print['height']), $tote->render(), $handles);
            $this->assertSame($print['width'], $print['height'], $handles);
            $this->assertEqualsWithDelta($body['x'] + $body['width'] / 2, $print['x'] + $print['width'] / 2, 0.02, "{$handles}: centred across");
            $this->assertEqualsWithDelta($body['y'] + $tote->hem + ($body['height'] - $tote->hem) / 2, $print['y'] + $print['height'] / 2, 0.02, "{$handles}: centred below the hem");
            $this->assertGreaterThan(0.5 * $body['width'], $print['width'], $handles);
            $this->assertGreaterThan($body['y'] + $tote->hem, $print['y'], $handles);
        }
    }

    public function test_long_handles_loop_high_above_a_bag_drawn_smaller(): void
    {
        $long = new Tote(handles: 'long');
        $short = new Tote(handles: 'short');
        $reach = [];

        foreach ([$long, $short] as $tote) {
            $body = $tote->body;
            [$back, $front] = array_map([$this, 'strap'], $tote->straps);
            $reach[$tote->handles] = ($body['y'] - min($back['top'], $front['top'])) / $body['height'];

            // Sewn in under the hem: the straps' ends lie under the bag, which is drawn over them.
            foreach ([$back, $front] as $strap) {
                $this->assertGreaterThan($body['y'], $strap['ends'], $tote->handles);
                $this->assertEqualsWithDelta($tote->feet[0], $strap['from'], 0.02, $tote->handles);
                $this->assertEqualsWithDelta($tote->feet[1], $strap['to'], 0.02, $tote->handles);
            }

            $svg = $tote->render();
            $this->assertLessThan(strpos($svg, '<path d="'.$tote->outline.'" fill="'), strpos($svg, '<path d="'.$tote->straps[1].'"'), $tote->handles);

            // The back strap has slipped a little to the right and up, so both show.
            $this->assertLessThan($front['top'], $back['top'], $tote->handles);
            $this->assertGreaterThan($front['apex'], $back['apex'], $tote->handles);

            // Inside the card, with room below for the shadow.
            $this->assertGreaterThanOrEqual(9.0, min($back['top'], $front['top']), $tote->handles);
            $this->assertGreaterThanOrEqual(9.0, $body['x'], $tote->handles);
            $this->assertLessThanOrEqual(111.0, $body['x'] + $body['width'], $tote->handles);
            $this->assertLessThanOrEqual(105.0, $body['y'] + $body['height'], $tote->handles);
        }

        $this->assertGreaterThan(1.8 * $reach['short'], $reach['long'], 'Long loops reach much higher.');
        $this->assertGreaterThan($long->body['width'], $short->body['width'], 'Beside long loops the bag is smaller.');
        $this->assertEqualsWithDelta(38 / 42, $long->body['width'] / $long->body['height'], 0.01, 'A tote of 38 x 42 cm.');
        $this->assertEqualsWithDelta(38 / 42, $short->body['width'] / $short->body['height'], 0.01, 'A tote of 38 x 42 cm.');
    }

    public function test_the_bag_lies_a_little_askew(): void
    {
        $this->assertLessThanOrEqual(-2.0, Tote::TILT);
        $this->assertGreaterThanOrEqual(-4.0, Tote::TILT);
        $this->assertStringContainsString('<g transform="rotate('.Tote::TILT.' 60 60)">', stiqon_mockup('tote'));
    }

    public function test_each_fabric_shows_its_colour_and_keeps_the_print_visible(): void
    {
        // The sample "siegel" brings a cream background of its own: drawn bare it
        // would be dark ink, which black cotton swallows.
        $background = '<rect width="100" height="100" fill="#f1ebdd" />';
        $cloth = ['natural' => '#efe7d6', 'black' => '#292927', 'white' => '#fbfbf9'];

        foreach (Tote::COLORS as $color) {
            $svg = stiqon_mockup('tote', ['color' => $color, 'motif' => 'siegel']);

            $this->assertStringContainsString($background, $svg, $color);
            $this->assertStringContainsString('fill="'.$cloth[$color].'"', $svg, $color);
            $this->assertSame(0, preg_match('/<g x-show="active" style="display:none">\s*<rect/', $svg), "{$color}: the visitor's motif gets no backing");

            foreach (array_diff_key($cloth, [$color => true]) as $other) {
                $this->assertStringNotContainsString('fill="'.$other.'"', $svg, $color);
            }
        }

        // Unbleached cotton keeps flecks of seed; bleached and dyed cotton does not.
        $this->assertStringContainsString('-flecks)', stiqon_mockup('tote', ['color' => 'natural']));
        $this->assertStringNotContainsString('-flecks)', stiqon_mockup('tote', ['color' => 'black']));
        $this->assertStringNotContainsString('-flecks)', stiqon_mockup('tote', ['color' => 'white']));
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertSame('Baumwolltasche', Tote::label());
        $this->assertStringContainsString('aria-label="Baumwolltasche mit langen Henkeln, natur"', stiqon_mockup('tote'));
        $this->assertStringContainsString('aria-label="Baumwolltasche mit kurzen Henkeln, schwarz"', stiqon_mockup('tote', ['handles' => 'short', 'color' => 'black']));
        $this->assertStringContainsString('aria-label="Baumwolltasche mit langen Henkeln, weiß"', stiqon_mockup('tote', ['color' => 'white']));
        $this->assertStringContainsString('aria-label="Jutebeutel vom Markt"', stiqon_mockup('tote', ['label' => 'Jutebeutel vom Markt']));
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
            'handles' => [static fn () => new Tote(handles: 'mittel'), 'Unknown tote handles [mittel].'],
            'handles by their German name' => [static fn () => Tote::fromArray(['handles' => 'Lange Henkel']), 'Unknown tote handles [Lange Henkel].'],
            'motif' => [static fn () => new Tote(motif: 'gibt-es-nicht'), 'Unknown tote motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Tote(motif: '../mockup/flyer'), 'Unknown tote motif'],
            'flyer motif' => [static fn () => Tote::fromArray(['motif' => 'prozent']), 'Unknown tote motif [prozent].'],
            'color' => [static fn () => new Tote(color: 'rot'), 'Unknown tote color [rot].'],
            'color in German' => [static fn () => Tote::fromArray(['color' => 'natur']), 'Unknown tote color [natur].'],
            'color of paper' => [static fn () => Tote::fromArray(['color' => 'kraft']), 'Unknown tote color [kraft].'],
        ];
    }

    public function test_without_values_the_first_handles_motif_and_colour_are_drawn(): void
    {
        $tote = Tote::fromArray([]);

        $this->assertSame(array_key_first(Tote::form()['values']), $tote->handles);
        $this->assertSame('long', $tote->handles);
        $this->assertSame(Tote::motifs()[0], $tote->motif);
        $this->assertSame(Tote::COLORS[0], $tote->color);
        $this->assertNull($tote->label);
        $this->assertSame((new Tote())->handles, $tote->handles);
        $this->assertSame((new Tote())->motif, $tote->motif);
        $this->assertSame((new Tote())->color, $tote->color);
    }

    public function test_values_of_other_kinds_are_ignored(): void
    {
        $tote = Tote::fromArray(['size' => 'l', 'shape' => 'circle', 'format' => 'a6', 'material' => 'kraft', 'stack' => true, 'gloss' => true, 'handles' => 'short', 'color' => 'black']);

        $this->assertSame('short', $tote->handles);
        $this->assertSame('black', $tote->color);
        $this->assertSame(Tote::motifs()[0], $tote->motif);
        $this->assertWellFormed($tote->render(), 'foreign keys');
    }

    public function test_ids_stay_unique_and_references_resolve_when_totes_share_a_page(): void
    {
        $drawings = [
            stiqon_mockup('tote', ['motif' => 'siegel']),
            stiqon_mockup('tote', ['handles' => 'short', 'color' => 'black', 'motif' => 'siegel']),
            stiqon_mockup('tote', ['color' => 'white', 'motif' => 'wellen']),
        ];

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'tt', 3);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Tote::keys();
        $form = Tote::form();

        $this->assertSame(['handles', 'motif', 'color'], array_keys($keys));
        $this->assertSame('handles', $form['key']);
        $this->assertSame('Henkel', $form['label']);
        $this->assertSame(['long' => 'Lange Henkel', 'short' => 'Kurze Henkel'], $form['values']);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Tote::motifs(), $keys['motif']);
        $this->assertSame(Sticker::motifs(), Tote::motifs());
        $this->assertSame(['natural', 'black', 'white'], $keys['color']);

        $names = Tote::names();
        $this->assertSame(array_keys($form['values']), array_keys($names['form']));
        $this->assertSame(['handles', 'color'], array_keys($names['options']));
        $this->assertSame(array_keys($form['values']), array_keys($names['options']['handles']));
        $this->assertSame($keys['color'], array_keys($names['options']['color']));
        $this->assertSame([], $names['switches']);
    }

    public function test_names_say_a_product_is_a_tote_its_handles_and_its_colour(): void
    {
        foreach (['Baumwolltaschen', 'Stofftaschen', 'Jutebeutel', 'Baumwolltasche mit langen Henkeln', 'Einkaufstaschen aus Baumwolle'] as $name) {
            $this->assertSame('tote', Guess::type($name), $name);
        }

        // A carrier bag of paper is a paper bag, not a cotton one.
        $this->assertSame('bag', Guess::type('Papiertragetaschen'));

        $this->assertSame(['handles' => 'long'], Guess::product('tote', 'Baumwolltaschen mit langen Henkeln'));
        $this->assertSame(['handles' => 'short'], Guess::product('tote', 'Stofftaschen, kurze Henkel'));
        $this->assertSame([], Guess::product('tote', 'Jutebeutel'));

        foreach (['Natur', 'Beige', 'Ecru', 'Creme', 'Naturweiß'] as $option) {
            $this->assertSame(['color' => 'natural'], Guess::option('tote', $option), $option);
        }

        $this->assertSame(['color' => 'black'], Guess::option('tote', 'Schwarz'));
        $this->assertSame(['color' => 'white'], Guess::option('tote', 'Weiß'));
        $this->assertSame(['handles' => 'long'], Guess::option('tote', 'Lange Henkel'));
        $this->assertSame(['handles' => 'short'], Guess::option('tote', 'Kurze Henkel'));
        $this->assertSame([], Guess::option('tote', '100 Stück'));
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        $resolved = Spec::resolve(['name' => 'Baumwolltaschen'], [['name' => 'Kurze Henkel'], ['name' => 'Schwarz']]);

        $this->assertSame('tote', $resolved['type']);
        $this->assertSame('short', $resolved['spec']['handles']);
        $this->assertSame('black', $resolved['spec']['color']);
        $this->assertContains($resolved['spec']['motif'], Tote::motifs());
        $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), 'resolved');

        foreach (['Jutebeutel', 'Stofftaschen mit langen Henkeln', 'Baumwolltaschen, kurze Henkel'] as $name) {
            $resolved = Spec::resolve(['name' => $name], [['name' => 'Natur'], ['name' => '50 Stück']]);

            $this->assertSame('tote', $resolved['type'], $name);
            $this->assertSame('natural', $resolved['spec']['color'], $name);
            $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), $name);
        }
    }

    public function test_text_from_outside_cannot_break_out_of_an_attribute(): void
    {
        $hostile = 'A "B" <c> & \'d\'';
        $escaped = 'A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;';

        foreach (['label' => stiqon_mockup('tote', ['label' => $hostile]), 'class' => stiqon_mockup('tote', ['color' => 'black'], $hostile)] as $what => $markup) {
            $this->assertStringContainsString($escaped, $markup, $what);
            $this->assertStringNotContainsString('<c>', $markup, $what);
        }
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b[^"]*\blift\b/', stiqon_mockup('tote'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('tote', [], 'w-[220px]'));
        $this->assertStringContainsString('viewBox="0 0 120 120"', stiqon_mockup('tote'));
    }

    /**
     * Where a strap's ends lie and how high it reaches: two cubic curves, from
     * one foot up to the top of the loop and down to the other foot.
     *
     * @return array{from: float, to: float, ends: float, apex: float, top: float}
     */
    private function strap(string $path): array
    {
        $number = '(-?[\d.]+)';
        $point = "{$number} {$number}";
        $this->assertSame(1, preg_match("/\\AM{$point}C{$point} {$point} {$point}C{$point} {$point} {$point}\\z/", $path, $m), $path);

        $m = array_map('floatval', $m);

        return ['from' => $m[1], 'to' => $m[13], 'ends' => $m[2], 'apex' => $m[7], 'top' => $m[8]];
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
