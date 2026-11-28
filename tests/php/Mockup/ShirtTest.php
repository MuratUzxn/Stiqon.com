<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Kinds;
use Stiqon\Shop\Mockup\Shirt;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Mockup\Uid;

/**
 * A T-shirt or a hoodie laid out flat: white, black, navy or grey melange
 * cloth, a print on the chest.
 */
final class ShirtTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_either_cut_and_every_colour(): void
    {
        $this->assertGreaterThanOrEqual(12, count(Shirt::motifs()));

        foreach (Shirt::motifs() as $motif) {
            foreach (array_keys(Shirt::form()['values']) as $cut) {
                foreach (Shirt::COLORS as $color) {
                    $svg = stiqon_mockup('shirt', compact('cut', 'motif', 'color'));

                    $this->assertWellFormed($svg, "{$motif} on a {$cut}/{$color}");
                    $this->assertStringContainsString('data-mockup="shirt"', $svg);
                    $this->assertStringContainsString('data-motif-image', $svg);
                    $this->assertStringContainsString('data-motif-sample', $svg);
                }
            }
        }
    }

    public function test_the_print_sits_whole_and_centred_on_the_chest(): void
    {
        foreach (array_keys(Shirt::CUTS) as $cut) {
            $shirt = new Shirt(cut: $cut, motif: 'siegel');
            $svg = $shirt->render();
            $body = $shirt->body;
            $print = $shirt->printBox;

            // The sample and a visitor's logo are both shown whole, never cropped.
            $this->assertStringContainsString(sprintf('<svg x="%s" y="%s" width="%s" height="%s" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet"', $print['x'], $print['y'], $print['width'], $print['height']), $svg, $cut);
            $this->assertStringContainsString('preserveAspectRatio="xMidYMid meet" x-bind:href="url" data-motif-image', $svg, $cut);

            // A square across the middle of the chest, below the neck and well above the hem.
            $this->assertSame($print['width'], $print['height'], $cut);
            $this->assertEqualsWithDelta($body['x'] + $body['width'] / 2, $print['x'] + $print['width'] / 2, 0.02, "{$cut}: centred across");
            $this->assertGreaterThan($body['y'] + 0.12 * $body['height'], $print['y'], "{$cut}: below the neck");
            $this->assertLessThan($body['y'] + 0.62 * $body['height'], $print['y'] + $print['height'], "{$cut}: on the chest");
            $this->assertGreaterThan(0.4 * $body['width'], $print['width'], "{$cut}: large enough to see");
        }

        // On the T-shirt about a third of its width across the sleeves …
        $tshirt = new Shirt();
        $xs = array_column($this->points($tshirt->outline), 0);
        $this->assertEqualsWithDelta(1 / 3, $tshirt->printBox['width'] / (max($xs) - min($xs)), 0.03);
        $this->assertNull($tshirt->pocket);

        // … on the hoodie between the ends of the drawstrings and the kangaroo pocket, clear of both.
        $hoodie = new Shirt(cut: 'hoodie');
        $print = $hoodie->printBox;
        $this->assertNotNull($hoodie->pocket);
        $this->assertLessThan($hoodie->pocket['y'] - 1.5, $print['y'] + $print['height']);
        $this->assertGreaterThan($print['x'] + $print['width'], $hoodie->pocket['x'] + $hoodie->pocket['width']);
        $this->assertLessThan($print['x'], $hoodie->pocket['x']);

        foreach ($hoodie->parts['strings'] as $string) {
            $end = $this->points($string);
            $this->assertLessThan($print['y'] - 0.8, end($end)[1], 'A drawstring ends above the print.');
        }
    }

    public function test_the_garment_fills_the_card_and_lies_a_little_askew(): void
    {
        foreach (array_keys(Shirt::CUTS) as $cut) {
            $shirt = new Shirt(cut: $cut);
            $points = $this->points($shirt->outline.($shirt->parts['hood'] ?? ''));
            $xs = array_column($points, 0);
            $ys = array_column($points, 1);

            // Inside the card, with room below for the shadow …
            $this->assertGreaterThanOrEqual(10.0, min($xs), $cut);
            $this->assertLessThanOrEqual(110.0, max($xs), $cut);
            $this->assertGreaterThanOrEqual(10.0, min($ys), $cut);
            $this->assertLessThanOrEqual(104.0, max($ys), $cut);

            // … and as much of it as the other drawings take.
            $this->assertGreaterThan(0.75 * 120, max($xs) - min($xs), $cut);
            $this->assertGreaterThan(0.7 * 120, max($ys) - min($ys), $cut);
        }

        $this->assertLessThanOrEqual(-1.0, Shirt::TILT);
        $this->assertGreaterThanOrEqual(-4.0, Shirt::TILT);
        $this->assertStringContainsString('<g transform="rotate('.Shirt::TILT.' 60 60)">', stiqon_mockup('shirt'));
    }

    public function test_a_tshirt_shows_its_crew_neck_and_a_hoodie_its_hood_strings_pocket_and_ribs(): void
    {
        $tshirt = new Shirt(cut: 'tshirt');
        $hoodie = new Shirt(cut: 'hoodie');
        $tee = $tshirt->render();
        $hood = $hoodie->render();

        // Through the T-shirt's crew neck the inside of the back shows, below the back of the collar.
        foreach (['inside', 'backCollar', 'collar', 'collarSeam', 'hems', 'armholes'] as $part) {
            $this->assertStringContainsString('d="'.$tshirt->parts[$part].'"', $tee, $part);
        }

        $this->assertStringContainsString('d="'.$tshirt->parts['collarRib']['d'].'" fill="none"', $tee);
        $this->assertArrayNotHasKey('hood', $tshirt->parts);
        $this->assertArrayNotHasKey('pocket', $tshirt->parts);

        // The hoodie's hood lies behind it, the pocket under the print, the drawstrings over everything.
        foreach (['hood', 'opening', 'eyelets', 'pocket', 'pocketSeams', 'ribSeams', 'armholes'] as $part) {
            $this->assertStringContainsString('d="'.$hoodie->parts[$part].'"', $hood, $part);
        }

        $this->assertCount(2, $hoodie->parts['edges']);
        $this->assertCount(2, $hoodie->parts['strings']);
        $this->assertCount(2, $hoodie->parts['tips']);
        $this->assertLessThan(strpos($hood, 'd="'.$hoodie->outline.'" fill='), strpos($hood, 'd="'.$hoodie->parts['hood'].'"'));
        $this->assertLessThan(strpos($hood, 'data-motif-sample'), strpos($hood, 'd="'.$hoodie->parts['pocket'].'"'));
        $this->assertGreaterThan(strpos($hood, 'data-motif-image'), strpos($hood, 'd="'.$hoodie->parts['strings'][0].'"'));

        // Ribbed cuffs and hem band: a dashed stroke as wide as the band.
        foreach (['cuffs', 'band'] as $band) {
            $this->assertStringContainsString('d="'.$hoodie->parts[$band]['d'].'"', $hood, $band);
            $this->assertStringContainsString('stroke-width="'.$hoodie->parts[$band]['width'].'" stroke-dasharray', $hood, $band);
        }

        $this->assertGreaterThan(5.0, $hoodie->parts['band']['width']);
        $this->assertArrayNotHasKey('inside', $hoodie->parts);
        $this->assertNotSame($tshirt->outline, $hoodie->outline);
    }

    public function test_each_colour_shows_its_cloth_and_takes_the_print_in_an_ink_that_shows(): void
    {
        // The sample "siegel" brings a cream background of its own. On cloth it is
        // left out and the seal printed straight onto the fabric: in dark ink on
        // white and grey, in light ink on black and navy, which swallow dark ink.
        $background = '<rect width="100" height="100" fill="#f1ebdd" />';
        $cloth = ['white' => '#fbfbf9', 'black' => '#1f1f1f', 'navy' => '#1f2a44', 'grey' => '#b8babc'];
        $ink = ['white' => 'dark', 'black' => 'light', 'navy' => 'light', 'grey' => 'dark'];
        $seal = ['dark' => '#1d1d1b', 'light' => '#f1ebdd'];

        $this->assertSame(Shirt::COLORS, array_keys($cloth));

        foreach (Shirt::COLORS as $color) {
            foreach (array_keys(Shirt::CUTS) as $cut) {
                $shirt = new Shirt(cut: $cut, color: $color, motif: 'siegel');
                $svg = $shirt->render();

                $this->assertSame(['bare' => true, 'ink' => $ink[$color]], $shirt->motifVars(), $color);
                $this->assertStringNotContainsString($background, $svg, $color);
                $this->assertStringContainsString('fill="'.$seal[$ink[$color]].'" style="font-family:var(--font-accent);font-style:italic">Fein', $svg, $color);
                $this->assertStringContainsString('d="'.$shirt->outline.'" fill="'.$cloth[$color].'"', $svg, $color);
                $this->assertSame(0, preg_match('/<g x-show="active" style="display:none">\s*<rect/', $svg), "{$color}: the visitor's motif gets no backing");

                foreach (array_diff_key($cloth, [$color => true]) as $other) {
                    $this->assertStringNotContainsString('"'.$other.'"', $svg, "{$cut} in {$color}");
                }
            }
        }

        // Grey melange is flecked with light and dark fibres; the other cloths are plain.
        $this->assertStringContainsString('-fleck)', stiqon_mockup('shirt', ['color' => 'grey']));

        foreach (['white', 'black', 'navy'] as $color) {
            $this->assertStringNotContainsString('-fleck', stiqon_mockup('shirt', ['color' => $color]), $color);
        }
    }

    public function test_no_sample_is_printed_in_an_ink_its_cloth_swallows(): void
    {
        foreach (Shirt::motifs() as $motif) {
            foreach (Shirt::DARK_COLORS as $color) {
                $print = $this->sample(stiqon_mockup('shirt', ['color' => $color, 'motif' => $motif]));

                foreach (['#0f0f0e', '#141412', '#1d1d1b', '#1d3557', '#1f3d2e', '#2f4329', '#7a2e2e'] as $dark) {
                    $this->assertStringNotContainsString('"'.$dark.'"', $print, "{$motif} on {$color} cloth");
                }
            }

            foreach (array_diff(Shirt::COLORS, Shirt::DARK_COLORS) as $color) {
                $print = $this->sample(stiqon_mockup('shirt', ['cut' => 'hoodie', 'color' => $color, 'motif' => $motif]));

                foreach (['#fff', '#ffffff', '#f4efe6'] as $white) {
                    $this->assertStringNotContainsString('"'.$white.'"', $print, "{$motif} on {$color} cloth");
                }
            }
        }
    }

    public function test_every_cut_and_colour_changes_the_drawing(): void
    {
        foreach (['cut' => array_keys(Shirt::CUTS), 'color' => Shirt::COLORS] as $key => $values) {
            $drawings = [];

            foreach ($values as $value) {
                Uid::reset();
                $drawings[$value] = stiqon_mockup('shirt', [$key => $value]);
            }

            $this->assertCount(count($values), array_unique($drawings), "Every {$key} looks different.");
        }
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertSame('Textil', Shirt::label());
        $this->assertStringContainsString('aria-label="T-Shirt, Weiß"', stiqon_mockup('shirt'));
        $this->assertStringContainsString('aria-label="Hoodie, Schwarz"', stiqon_mockup('shirt', ['cut' => 'hoodie', 'color' => 'black']));
        $this->assertStringContainsString('aria-label="T-Shirt, Grau meliert"', stiqon_mockup('shirt', ['color' => 'grey']));
        $this->assertStringContainsString('aria-label="Hoodie, Navy"', stiqon_mockup('shirt', ['cut' => 'hoodie', 'color' => 'navy']));
        $this->assertStringContainsString('aria-label="Vereinsshirt in Weiß"', stiqon_mockup('shirt', ['label' => 'Vereinsshirt in Weiß']));
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
            'cut' => [static fn () => new Shirt(cut: 'polo'), 'Unknown shirt cut [polo].'],
            'cut by its German name' => [static fn () => Shirt::fromArray(['cut' => 'T-Shirt']), 'Unknown shirt cut [T-Shirt].'],
            'motif' => [static fn () => new Shirt(motif: 'gibt-es-nicht'), 'Unknown shirt motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Shirt(motif: '../mockup/flyer'), 'Unknown shirt motif'],
            'flyer motif' => [static fn () => Shirt::fromArray(['motif' => 'prozent']), 'Unknown shirt motif [prozent].'],
            'color' => [static fn () => new Shirt(color: 'rot'), 'Unknown shirt color [rot].'],
            'color in German' => [static fn () => Shirt::fromArray(['color' => 'schwarz']), 'Unknown shirt color [schwarz].'],
            'color of a tote' => [static fn () => Shirt::fromArray(['color' => 'natural']), 'Unknown shirt color [natural].'],
        ];
    }

    public function test_without_values_the_first_cut_motif_and_colour_are_drawn(): void
    {
        $shirt = Shirt::fromArray([]);

        $this->assertSame(array_key_first(Shirt::form()['values']), $shirt->cut);
        $this->assertSame('tshirt', $shirt->cut);
        $this->assertSame(Shirt::motifs()[0], $shirt->motif);
        $this->assertSame(Shirt::COLORS[0], $shirt->color);
        $this->assertNull($shirt->label);
        $this->assertSame((new Shirt())->cut, $shirt->cut);
        $this->assertSame((new Shirt())->motif, $shirt->motif);
        $this->assertSame((new Shirt())->color, $shirt->color);
    }

    public function test_values_of_other_kinds_are_ignored(): void
    {
        $shirt = Shirt::fromArray(['size' => 'l', 'shape' => 'circle', 'handles' => 'long', 'style' => 'enamel', 'format' => 'a6', 'material' => 'kraft', 'stack' => true, 'gloss' => true, 'cut' => 'hoodie', 'color' => 'navy']);

        $this->assertSame('hoodie', $shirt->cut);
        $this->assertSame('navy', $shirt->color);
        $this->assertSame(Shirt::motifs()[0], $shirt->motif);
        $this->assertWellFormed($shirt->render(), 'foreign keys');

        Uid::reset();
        $mixed = Shirt::fromArray(['cut' => 'hoodie', 'color' => 'navy', 'shape' => 'circle', 'gloss' => true])->render();
        Uid::reset();
        $this->assertSame(Shirt::fromArray(['cut' => 'hoodie', 'color' => 'navy'])->render(), $mixed);
    }

    public function test_ids_stay_unique_and_references_resolve_when_shirts_share_a_page(): void
    {
        // "siegel" brings an id of its own, "raster" in light ink one more.
        $drawings = [
            stiqon_mockup('shirt', ['motif' => 'siegel']),
            stiqon_mockup('shirt', ['cut' => 'hoodie', 'color' => 'black', 'motif' => 'raster']),
            stiqon_mockup('shirt', ['cut' => 'hoodie', 'color' => 'grey', 'motif' => 'siegel']),
        ];

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'sh', 3);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Shirt::keys();
        $form = Shirt::form();

        $this->assertSame(['cut', 'motif', 'color'], array_keys($keys));
        $this->assertSame('cut', $form['key']);
        $this->assertSame('Schnitt', $form['label']);
        $this->assertSame(['tshirt' => 'T-Shirt', 'hoodie' => 'Hoodie'], $form['values']);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Shirt::motifs(), $keys['motif']);
        $this->assertSame(Sticker::motifs(), Shirt::motifs());
        $this->assertSame(['white', 'black', 'navy', 'grey'], $keys['color']);

        $names = Shirt::names();
        $this->assertSame(['hoodie', 'tshirt'], array_keys($names['form']), 'The hoodie is asked first.');
        $this->assertEqualsCanonicalizing(array_keys($form['values']), array_keys($names['form']));
        $this->assertSame(['color'], array_keys($names['options']));
        $this->assertSame($keys['color'], array_keys($names['options']['color']));
        $this->assertSame([], $names['switches']);
    }

    public function test_names_say_a_product_is_a_shirt_its_cut_and_its_colour(): void
    {
        foreach (['T-Shirts', 'Hoodies', 'Poloshirts', 'Sweatshirts', 'T-Shirts aus Baumwolle', 'Baumwoll-Hoodie mit Kängurubeutel', 'Kapuzenpullover'] as $name) {
            $this->assertSame('shirt', Guess::type($name), $name);
        }

        foreach (['T-Shirts' => 'tshirt', 'Poloshirts' => 'tshirt', 'T-Shirts aus Baumwolle' => 'tshirt', 'Hoodies' => 'hoodie', 'Sweatshirts' => 'hoodie', 'Baumwoll-Hoodie mit Kängurubeutel' => 'hoodie', 'Kapuzenpullover' => 'hoodie'] as $name => $cut) {
            $this->assertSame(['cut' => $cut], Guess::product('shirt', $name), $name);
        }

        foreach (['Weiß' => 'white', 'Schwarz' => 'black', 'Navy' => 'navy', 'Marineblau' => 'navy', 'Grau meliert' => 'grey', 'Grau' => 'grey'] as $option => $color) {
            $this->assertSame(['color' => $color], Guess::option('shirt', $option), $option);
        }

        // Sizes change nothing in the drawing.
        foreach (['S', 'M', 'L', 'XL', 'XXL', '25 Stück'] as $option) {
            $this->assertSame([], Guess::option('shirt', $option), $option);
        }
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        $cases = [
            ['T-Shirts', ['Schwarz', 'L', '25 Stück'], 'tshirt', 'black', 'T-Shirt, Schwarz'],
            ['T-Shirts', ['Navy', 'S'], 'tshirt', 'navy', 'T-Shirt, Navy'],
            ['Hoodies', ['Grau meliert', 'XL', '10 Stück'], 'hoodie', 'grey', 'Hoodie, Grau meliert'],
            ['Baumwoll-Hoodie mit Kängurubeutel', ['Schwarz', 'XXL'], 'hoodie', 'black', 'Hoodie, Schwarz'],
            ['Poloshirts', ['Weiß', 'M'], 'tshirt', 'white', 'T-Shirt, Weiß'],
        ];

        foreach ($cases as [$name, $options, $cut, $color, $label]) {
            $resolved = Spec::resolve(['name' => $name], array_map(static fn (string $option): array => ['name' => $option], $options));

            $this->assertSame('shirt', $resolved['type'], $name);
            $this->assertSame($cut, $resolved['spec']['cut'], $name);
            $this->assertSame($color, $resolved['spec']['color'], $name);
            $this->assertContains($resolved['spec']['motif'], Shirt::motifs(), $name);

            $svg = Kinds::draw($resolved['type'], $resolved['spec']);
            $this->assertWellFormed($svg, $name);
            $this->assertStringContainsString('aria-label="'.$label.'"', $svg, $name);
        }

        // What is stored on the product and on an option has the last word.
        $resolved = Spec::resolve(['name' => 'Textilien', 'stored' => ['type' => 'shirt', 'cut' => 'hoodie']], [['name' => 'Farbe 1', 'stored' => ['color' => 'navy']]]);
        $this->assertSame(['cut' => 'hoodie', 'color' => 'navy'], array_intersect_key($resolved['spec'], ['cut' => true, 'color' => true]));
    }

    public function test_text_from_outside_cannot_break_out_of_an_attribute(): void
    {
        $hostile = 'A "B" <c> & \'d\'';
        $escaped = 'A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;';

        foreach (['label' => stiqon_mockup('shirt', ['label' => $hostile]), 'class' => stiqon_mockup('shirt', ['cut' => 'hoodie'], $hostile)] as $what => $markup) {
            $this->assertStringContainsString($escaped, $markup, $what);
            $this->assertStringNotContainsString('<c>', $markup, $what);
        }
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b[^"]*\blift\b/', stiqon_mockup('shirt'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('shirt', [], 'w-[220px]'));
        $this->assertStringContainsString('viewBox="0 0 120 120"', stiqon_mockup('shirt'));
    }

    /**
     * The points of path data: every pair of numbers, in order. For a path of
     * lines and cubic curves these hold the shape, its control points included.
     *
     * @return list<array{0: float, 1: float}>
     */
    private function points(string $path): array
    {
        preg_match_all('/-?\d+(?:\.\d+)?/', $path, $numbers);

        return array_map(static fn (array $pair): array => [(float) $pair[0], (float) $pair[1]], array_chunk($numbers[0], 2));
    }

    /** The sample motif as printed: the inner <svg> of the print layer. */
    private function sample(string $svg): string
    {
        $this->assertSame(1, preg_match('/<svg[^>]*data-motif-sample[^>]*>.*?<\/svg>/s', $svg, $sample));

        return $sample[0];
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
