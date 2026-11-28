<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Kinds;
use Stiqon\Shop\Mockup\Mug;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Mockup\Uid;

/**
 * A mug seen from the side and a little from above: a ceramic coffee mug or
 * an enamel mug, white or black, a print on the side facing the viewer.
 */
final class MugTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_either_style_and_colour(): void
    {
        $this->assertGreaterThanOrEqual(10, count(Mug::motifs()));

        foreach (Mug::motifs() as $motif) {
            foreach (array_keys(Mug::form()['values']) as $style) {
                foreach (Mug::COLORS as $color) {
                    $svg = stiqon_mockup('mug', compact('style', 'motif', 'color'));

                    $this->assertWellFormed($svg, "{$motif} on a {$style}/{$color} mug");
                    $this->assertStringContainsString('data-mockup="mug"', $svg);
                    $this->assertStringContainsString('data-motif-image', $svg);
                    $this->assertStringContainsString('data-motif-sample', $svg);
                }
            }
        }
    }

    public function test_motifs_that_are_only_an_outline_on_a_sticker_are_not_offered(): void
    {
        // Printed bare, "stern" would be its lettering alone and "sprechblase"
        // would lose its bubble: the mug would look unprinted.
        $this->assertSame(array_values(array_diff(Sticker::motifs(), ['sprechblase', 'stern'])), Mug::motifs());

        // Nor does the name of a product pick one.
        foreach (range(1, 60) as $number) {
            $this->assertNotContains(Guess::motif('mug', "Tasse {$number}"), Sticker::CONTOUR_MOTIFS, "Tasse {$number}");
        }
    }

    public function test_the_print_sits_whole_in_the_middle_of_the_wall_and_is_clipped_to_it(): void
    {
        foreach (array_keys(Mug::STYLES) as $style) {
            $mug = new Mug(style: $style, motif: 'siegel');
            $svg = $mug->render();
            $print = $mug->printBox;
            $rim = $mug->rim;
            $base = $mug->base;

            // The print layer is clipped to the wall as it can be seen, so nothing reaches past its edge.
            $this->assertMatchesRegularExpression('/<clipPath id="'.$mug->uid.'-body">\s*<path d="'.preg_quote($mug->wall, '/').'" \/>/', $svg, $style);
            $this->assertStringContainsString('<g x-data="motifLayer" clip-path="url(#'.$mug->uid.'-body)">', $svg, $style);

            // The sample and a visitor's logo are both shown whole.
            $this->assertStringContainsString(sprintf('<svg x="%s" y="%s" width="%s" height="%s" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet"', $print['x'], $print['y'], $print['width'], $print['height']), $svg, $style);
            $this->assertStringContainsString('preserveAspectRatio="xMidYMid meet" x-bind:href="url" data-motif-image', $svg, $style);

            // A square in the middle of the side facing the viewer, below the rim's front edge and above the base's.
            $this->assertSame($print['width'], $print['height'], $style);
            $this->assertEqualsWithDelta($rim['cx'], $print['x'] + $print['width'] / 2, 0.02, "{$style}: centred across");
            $this->assertEqualsWithDelta(Mug::PRINT * $mug->body['width'], $print['width'], 0.02, $style);
            $this->assertGreaterThan($rim['cy'] + $rim['ry'] + 3.0, $print['y'], "{$style}: below the rim");
            $this->assertLessThan($base['cy'] - 3.0, $print['y'] + $print['height'], "{$style}: above the foot");
            $this->assertEqualsWithDelta(($rim['cy'] + $rim['ry'] + $base['cy'] + $base['ry']) / 2, $print['y'] + $print['height'] / 2, 0.02, "{$style}: centred on the wall");
        }
    }

    public function test_light_and_shade_round_the_cylinder_lie_over_the_print(): void
    {
        foreach (Mug::COLORS as $color) {
            $mug = new Mug(color: $color);
            $svg = $mug->render();
            $round = $mug->uid.'-round';
            $print = strpos($svg, 'data-motif-image');
            $shade = strpos($svg, '<path d="'.$mug->wall.'" fill="url(#'.$round.')" />');

            // Laid over the print, and never in the way of a click on it.
            $this->assertNotFalse($shade, $color);
            $this->assertGreaterThan($print, $shade, $color);
            $this->assertMatchesRegularExpression('/<g pointer-events="none">\s*<path d="'.preg_quote($mug->wall, '/').'" fill="url\(#'.$round.'\)"/', $svg, $color);

            // The side turning away from the light darkens towards the right edge.
            $this->assertSame(1, preg_match('/<linearGradient id="'.$round.'"[^>]*>(.*?)<\/linearGradient>/s', $svg, $gradient), $color);
            preg_match_all('/<stop offset="([\d.]+)" stop-color="(#[0-9a-f]+)" stop-opacity="([\d.]+)"/', $gradient[1], $stops, PREG_SET_ORDER);
            $shadeAt = static fn (array $stop): float => ($stop[2] === '#000' ? 1 : -1) * (float) $stop[3];
            $middle = array_values(array_filter($stops, static fn (array $stop): bool => (float) $stop[1] >= 0.4 && (float) $stop[1] <= 0.6));
            $right = array_values(array_filter($stops, static fn (array $stop): bool => (float) $stop[1] >= 0.85 && (float) $stop[1] < 1.0));

            $this->assertNotEmpty($middle, $color);
            $this->assertNotEmpty($right, $color);
            $this->assertGreaterThan(max(array_map($shadeAt, $middle)) + 0.08, max(array_map($shadeAt, $right)), $color);
        }
    }

    public function test_the_rim_shows_a_little_of_the_inside_and_the_handle_is_on_the_right(): void
    {
        foreach (array_keys(Mug::STYLES) as $style) {
            $mug = new Mug(style: $style);
            $svg = $mug->render();
            $rim = $mug->rim;
            $right = $rim['cx'] + $rim['rx'];

            // Seen a little from above: a flat ellipse, the base a little rounder.
            $this->assertEqualsWithDelta(Mug::VIEW, $rim['ry'] / $rim['rx'], 0.01, $style);
            $this->assertGreaterThan($rim['ry'] / $rim['rx'], $mug->base['ry'] / $mug->base['rx'], $style);
            $this->assertGreaterThanOrEqual(0.15, Mug::VIEW);
            $this->assertLessThanOrEqual(0.25, Mug::VIEW);

            // The inside shows through the opening, drawn over the wall's top.
            $this->assertGreaterThan(strpos($svg, 'fill="url(#'.$mug->uid.'-round)"'), strpos($svg, '<path d="'.$mug->opening.'" fill="'), $style);

            // The handle reaches out to the right; its ends lie behind the body, which is drawn over them.
            $points = $this->points($mug->handle);
            $xs = array_column($points, 0);
            $this->assertGreaterThan($right + 0.25 * $mug->body['width'], max($xs), $style);
            $this->assertLessThan($right, $points[0][0], $style);
            $this->assertLessThan($right, end($points)[0], $style);
            $this->assertLessThan(strpos($svg, '<path d="'.$mug->wall.'" fill="#'), strpos($svg, '<path d="'.$mug->handle.'"'), $style);
        }
    }

    public function test_an_enamel_mug_is_slimmer_with_a_dark_rolled_rim_a_few_specks_and_a_thinner_handle(): void
    {
        $classic = new Mug(style: 'classic');
        $enamel = new Mug(style: 'enamel');
        $ceramic = $classic->render();
        $steel = $enamel->render();

        $this->assertLessThan($classic->body['width'] / $classic->body['height'], $enamel->body['width'] / $enamel->body['height'], 'Slimmer.');
        $this->assertLessThan(Mug::STYLES['classic']['grip'], Mug::STYLES['enamel']['grip'], 'A thinner handle.');
        $this->assertLessThan(Mug::STYLES['classic']['wall'], Mug::STYLES['enamel']['wall'], 'A thinner wall.');

        // The rolled rim: a dark bead round the top, on white enamel as on black.
        $bead = sprintf('<ellipse cx="%s" cy="%s" rx="%s" ry="%s" fill="none" stroke="%%s" stroke-width="2" />', $enamel->rim['cx'], $enamel->rim['cy'], $enamel->rim['rx'], $enamel->rim['ry']);
        $this->assertStringContainsString(sprintf($bead, '#262d38'), $steel);
        $this->assertStringContainsString(sprintf($bead, '#0b0b0b'), stiqon_mockup('mug', ['style' => 'enamel', 'color' => 'black']));
        $this->assertStringNotContainsString('stroke-width="2" />', $ceramic);

        // A few faint specks fired into the enamel, under the print; none in glazed ceramic.
        $specks = '/<g clip-path="url\(#mg\d+-body\)" fill="#[0-9a-f]+" fill-opacity="0\.\d+">(.*?)<\/g>/s';
        $this->assertSame(1, preg_match($specks, $steel, $found));
        $this->assertGreaterThanOrEqual(6, substr_count($found[1], '<circle'));
        $this->assertLessThanOrEqual(14, substr_count($found[1], '<circle'));
        $this->assertLessThan(strpos($steel, 'data-motif-sample'), strpos($steel, $found[1]));
        $this->assertSame(0, preg_match($specks, $ceramic));

        // Ceramic rounds into its foot; pressed steel stands on a crisp edge.
        $this->assertStringContainsString('C', substr($classic->wall, strpos($classic->wall, 'L')));
        $this->assertStringNotContainsString('C', $enamel->wall);
    }

    public function test_the_mug_fills_the_card(): void
    {
        foreach (array_keys(Mug::STYLES) as $style) {
            $mug = new Mug(style: $style);
            $grip = Mug::STYLES[$style]['grip'];
            $left = $mug->rim['cx'] - $mug->rim['rx'];
            $right = max(array_column($this->points($mug->handle), 0)) + $grip / 2;
            $top = $mug->rim['cy'] - $mug->rim['ry'];
            $bottom = $mug->base['cy'] + $mug->base['ry'];

            // Inside the card, with room below for the shadow …
            $this->assertGreaterThanOrEqual(10.0, $left, $style);
            $this->assertLessThanOrEqual(110.0, $right, $style);
            $this->assertGreaterThanOrEqual(10.0, $top, $style);
            $this->assertLessThanOrEqual(104.0, $bottom, $style);

            // … centred with its handle, and about as large as the other drawings.
            $this->assertEqualsWithDelta(60.0, ($left + $right) / 2, 1.5, $style);
            $this->assertGreaterThan(0.75 * 120, $bottom - $top, $style);
            $this->assertGreaterThan(0.62 * 120, $right - $left, $style);
        }
    }

    public function test_each_colour_shows_its_glaze_and_takes_the_print_in_an_ink_that_shows(): void
    {
        // The sample "siegel" brings a cream background of its own. On a mug it is
        // left out and the seal printed straight onto the glaze: in dark ink on
        // white, in light ink on black, which swallows dark ink.
        $background = '<rect width="100" height="100" fill="#f1ebdd" />';
        $glaze = ['white' => '#f7f6f3', 'black' => '#1d1d1c'];
        $ink = ['white' => 'dark', 'black' => 'light'];
        $seal = ['dark' => '#1d1d1b', 'light' => '#f1ebdd'];

        foreach (Mug::COLORS as $color) {
            foreach (array_keys(Mug::STYLES) as $style) {
                $mug = new Mug(style: $style, color: $color, motif: 'siegel');
                $svg = $mug->render();

                $this->assertSame(['bare' => true, 'ink' => $ink[$color]], $mug->motifVars(), $color);
                $this->assertStringNotContainsString($background, $svg, $color);
                $this->assertStringContainsString('fill="'.$seal[$ink[$color]].'" style="font-family:var(--font-accent);font-style:italic">Fein', $svg, $color);
                $this->assertStringContainsString('<path d="'.$mug->wall.'" fill="'.$glaze[$color].'" />', $svg, "{$style} in {$color}");
                $this->assertSame(0, preg_match('/<g x-show="active" style="display:none">\s*<rect/', $svg), "{$color}: the visitor's motif gets no backing");

                foreach (array_diff_key($glaze, [$color => true]) as $other) {
                    $this->assertStringNotContainsString('"'.$other.'"', $svg, "{$style} in {$color}");
                }
            }
        }
    }

    public function test_no_sample_is_printed_in_an_ink_its_glaze_swallows(): void
    {
        foreach (Mug::motifs() as $motif) {
            foreach (array_keys(Mug::STYLES) as $style) {
                $onBlack = $this->sample(stiqon_mockup('mug', ['style' => $style, 'color' => 'black', 'motif' => $motif]));
                $onWhite = $this->sample(stiqon_mockup('mug', ['style' => $style, 'color' => 'white', 'motif' => $motif]));

                foreach (['#0f0f0e', '#141412', '#1d1d1b', '#1d3557', '#1f3d2e', '#2f4329', '#7a2e2e'] as $dark) {
                    $this->assertStringNotContainsString('"'.$dark.'"', $onBlack, "{$motif} on a black {$style} mug");
                }

                foreach (['#fff', '#ffffff', '#f4efe6'] as $white) {
                    $this->assertStringNotContainsString('"'.$white.'"', $onWhite, "{$motif} on a white {$style} mug");
                }
            }
        }
    }

    public function test_every_style_and_colour_changes_the_drawing(): void
    {
        foreach (['style' => array_keys(Mug::STYLES), 'color' => Mug::COLORS] as $key => $values) {
            $drawings = [];

            foreach ($values as $value) {
                Uid::reset();
                $drawings[$value] = stiqon_mockup('mug', [$key => $value]);
            }

            $this->assertCount(count($values), array_unique($drawings), "Every {$key} looks different.");
        }
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertSame('Tasse', Mug::label());
        $this->assertStringContainsString('aria-label="Kaffeetasse, Weiß"', stiqon_mockup('mug'));
        $this->assertStringContainsString('aria-label="Emaillebecher, Schwarz"', stiqon_mockup('mug', ['style' => 'enamel', 'color' => 'black']));
        $this->assertStringContainsString('aria-label="Kaffeetasse, Schwarz"', stiqon_mockup('mug', ['color' => 'black']));
        $this->assertStringContainsString('aria-label="Emaillebecher, Weiß"', stiqon_mockup('mug', ['style' => 'enamel']));
        $this->assertStringContainsString('aria-label="Becher für das Büro"', stiqon_mockup('mug', ['label' => 'Becher für das Büro']));
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
            'style' => [static fn () => new Mug(style: 'porzellan'), 'Unknown mug style [porzellan].'],
            'style by its German name' => [static fn () => Mug::fromArray(['style' => 'Emaillebecher']), 'Unknown mug style [Emaillebecher].'],
            'motif' => [static fn () => new Mug(motif: 'gibt-es-nicht'), 'Unknown mug motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Mug(motif: '../mockup/flyer'), 'Unknown mug motif'],
            'flyer motif' => [static fn () => Mug::fromArray(['motif' => 'prozent']), 'Unknown mug motif [prozent].'],
            'outline motif' => [static fn () => new Mug(motif: 'stern'), 'Unknown mug motif [stern].'],
            'outline motif from a spec' => [static fn () => Mug::fromArray(['style' => 'enamel', 'motif' => 'sprechblase']), 'Unknown mug motif [sprechblase].'],
            'color' => [static fn () => new Mug(color: 'rot'), 'Unknown mug color [rot].'],
            'color in German' => [static fn () => Mug::fromArray(['color' => 'weiß']), 'Unknown mug color [weiß].'],
            'color of a shirt' => [static fn () => Mug::fromArray(['color' => 'navy']), 'Unknown mug color [navy].'],
        ];
    }

    public function test_without_values_the_first_style_motif_and_colour_are_drawn(): void
    {
        $mug = Mug::fromArray([]);

        $this->assertSame(array_key_first(Mug::form()['values']), $mug->style);
        $this->assertSame('classic', $mug->style);
        $this->assertSame(Mug::motifs()[0], $mug->motif);
        $this->assertSame(Mug::COLORS[0], $mug->color);
        $this->assertNull($mug->label);
        $this->assertSame((new Mug())->style, $mug->style);
        $this->assertSame((new Mug())->motif, $mug->motif);
        $this->assertSame((new Mug())->color, $mug->color);
    }

    public function test_values_of_other_kinds_are_ignored(): void
    {
        $mug = Mug::fromArray(['size' => 'l', 'shape' => 'circle', 'cut' => 'hoodie', 'handles' => 'short', 'format' => 'a6', 'material' => 'kraft', 'stack' => true, 'gloss' => true, 'style' => 'enamel', 'color' => 'black']);

        $this->assertSame('enamel', $mug->style);
        $this->assertSame('black', $mug->color);
        $this->assertSame(Mug::motifs()[0], $mug->motif);
        $this->assertWellFormed($mug->render(), 'foreign keys');

        Uid::reset();
        $mixed = Mug::fromArray(['style' => 'enamel', 'color' => 'black', 'cut' => 'hoodie', 'window' => true])->render();
        Uid::reset();
        $this->assertSame(Mug::fromArray(['style' => 'enamel', 'color' => 'black'])->render(), $mixed);
    }

    public function test_ids_stay_unique_and_references_resolve_when_mugs_share_a_page(): void
    {
        // "siegel" brings an id of its own, "raster" in light ink one more.
        $drawings = [
            stiqon_mockup('mug', ['motif' => 'siegel']),
            stiqon_mockup('mug', ['style' => 'enamel', 'color' => 'black', 'motif' => 'raster']),
            stiqon_mockup('mug', ['style' => 'enamel', 'motif' => 'siegel']),
        ];

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'mg', 3);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Mug::keys();
        $form = Mug::form();

        $this->assertSame(['style', 'motif', 'color'], array_keys($keys));
        $this->assertSame('style', $form['key']);
        $this->assertSame('Ausführung', $form['label']);
        $this->assertSame(['classic' => 'Kaffeetasse', 'enamel' => 'Emaillebecher'], $form['values']);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Mug::motifs(), $keys['motif']);
        $this->assertSame(array_values(array_diff(Sticker::motifs(), Sticker::CONTOUR_MOTIFS)), Mug::motifs());
        $this->assertSame(['white', 'black'], $keys['color']);

        $names = Mug::names();
        $this->assertSame(['enamel', 'classic'], array_keys($names['form']), 'Enamel is asked first: an "Emaillebecher" is a "Becher" as well.');
        $this->assertEqualsCanonicalizing(array_keys($form['values']), array_keys($names['form']));
        $this->assertSame(['color'], array_keys($names['options']));
        $this->assertSame($keys['color'], array_keys($names['options']['color']));
        $this->assertSame([], $names['switches']);
    }

    public function test_names_say_a_product_is_a_mug_its_style_and_its_colour(): void
    {
        foreach (['Tassen', 'Kaffeebecher', 'Emaillebecher', 'Kaffeetassen', 'Campingbecher aus Emaille'] as $name) {
            $this->assertSame('mug', Guess::type($name), $name);
        }

        // Mugs filed under "Werbeartikel" are mugs.
        $this->assertSame('mug', Guess::type('Tassen', 'Werbeartikel'));

        foreach (['Emaillebecher' => 'enamel', 'Campingbecher' => 'enamel', 'Tassen' => 'classic', 'Kaffeebecher' => 'classic', 'Kaffeetassen' => 'classic'] as $name => $style) {
            $this->assertSame(['style' => $style], Guess::product('mug', $name), $name);
        }

        $this->assertSame(['color' => 'white'], Guess::option('mug', 'Weiß'));
        $this->assertSame(['color' => 'black'], Guess::option('mug', 'Schwarz'));
        $this->assertSame([], Guess::option('mug', '36 Stück'));
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        $cases = [
            ['Tassen', ['Schwarz', '36 Stück'], 'classic', 'black', 'Kaffeetasse, Schwarz'],
            ['Tassen', ['Weiß', '12 Stück'], 'classic', 'white', 'Kaffeetasse, Weiß'],
            ['Kaffeebecher', ['Schwarz'], 'classic', 'black', 'Kaffeetasse, Schwarz'],
            ['Emaillebecher', ['Weiß'], 'enamel', 'white', 'Emaillebecher, Weiß'],
            ['Kaffeetassen', [], 'classic', 'white', 'Kaffeetasse, Weiß'],
        ];

        foreach ($cases as [$name, $options, $style, $color, $label]) {
            $resolved = Spec::resolve(['name' => $name], array_map(static fn (string $option): array => ['name' => $option], $options));

            $this->assertSame('mug', $resolved['type'], $name);
            $this->assertSame($style, $resolved['spec']['style'], $name);
            $this->assertSame($color, $resolved['spec']['color'] ?? Mug::COLORS[0], $name);
            $this->assertContains($resolved['spec']['motif'], Mug::motifs(), $name);

            $svg = Kinds::draw($resolved['type'], $resolved['spec']);
            $this->assertWellFormed($svg, $name);
            $this->assertStringContainsString('aria-label="'.$label.'"', $svg, $name);
        }
    }

    public function test_text_from_outside_cannot_break_out_of_an_attribute(): void
    {
        $hostile = 'A "B" <c> & \'d\'';
        $escaped = 'A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;';

        foreach (['label' => stiqon_mockup('mug', ['label' => $hostile]), 'class' => stiqon_mockup('mug', ['style' => 'enamel'], $hostile)] as $what => $markup) {
            $this->assertStringContainsString($escaped, $markup, $what);
            $this->assertStringNotContainsString('<c>', $markup, $what);
        }
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b[^"]*\blift\b/', stiqon_mockup('mug'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('mug', [], 'w-[220px]'));
        $this->assertStringContainsString('viewBox="0 0 120 120"', stiqon_mockup('mug'));
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
