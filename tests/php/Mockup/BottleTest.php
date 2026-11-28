<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Bottle;
use Stiqon\Shop\Mockup\Kind;
use Stiqon\Shop\Mockup\Kinds;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Mockup\Uid;

/**
 * A double-walled drink bottle standing upright: 500 or 750 ml, brushed steel
 * or black or white powder coat, a logo on its round body.
 */
final class BottleTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_every_size_and_colour(): void
    {
        $this->assertGreaterThanOrEqual(10, count(Bottle::motifs()));

        foreach (Bottle::motifs() as $motif) {
            foreach (array_keys(Bottle::form()['values']) as $size) {
                foreach (Bottle::COLORS as $color) {
                    $svg = stiqon_mockup('bottle', ['size' => (string) $size, 'motif' => $motif, 'color' => $color]);

                    $this->assertWellFormed($svg, "{$motif} on {$size} ml/{$color}");
                    $this->assertStringContainsString('data-mockup="bottle"', $svg);
                    $this->assertStringContainsString('data-motif-image', $svg);
                    $this->assertStringContainsString('data-motif-sample', $svg);
                }
            }
        }
    }

    public function test_every_value_changes_the_drawing(): void
    {
        $plain = stiqon_mockup('bottle');

        foreach (['size' => ['750'], 'color' => ['black', 'white'], 'motif' => array_slice(Bottle::motifs(), 1)] as $key => $values) {
            foreach ($values as $value) {
                Uid::reset();
                $this->assertNotSame($plain, stiqon_mockup('bottle', [$key => $value]), "{$key} {$value}");
            }
        }

        Uid::reset();
        $this->assertSame($plain, stiqon_mockup('bottle'), 'The same values draw the same bottle.');
    }

    public function test_motifs_that_are_only_an_outline_on_a_sticker_are_not_offered(): void
    {
        // A contour motif's shape is the sticker's outline. Printed bare on the
        // body, "stern" left a small "Top!" and "sprechblase" a greeting without
        // its bubble: a bottle that looked unprinted.
        $this->assertSame(array_values(array_diff(Sticker::motifs(), ['sprechblase', 'stern'])), Bottle::motifs());

        // A product that stored one of them still gets a bottle with a logo on it.
        foreach (Sticker::CONTOUR_MOTIFS as $motif) {
            $resolved = Spec::resolve(['name' => 'Trinkflaschen', 'stored' => ['type' => 'bottle', 'motif' => $motif]]);

            $this->assertContains($resolved['spec']['motif'], Bottle::motifs(), $motif);
            $this->assertWellFormed(Kinds::draw($resolved['type'], $resolved['spec']), $motif);
        }
    }

    public function test_the_logo_is_printed_on_the_body_and_shaded_with_it(): void
    {
        foreach (array_keys(Bottle::SIZES) as $size) {
            $bottle = new Bottle(size: (string) $size, motif: 'siegel');
            $svg = $bottle->render();
            $print = $bottle->printBox;
            $uid = $bottle->uid;

            // Clipped to the body: nothing of it reaches over the shoulder or under the foot.
            $this->assertStringContainsString('<clipPath id="'.$uid.'-body">', $svg, (string) $size);
            $this->assertStringContainsString('<path d="'.$bottle->outline.'" />', $svg, (string) $size);
            $this->assertStringContainsString('<g x-data="motifLayer" clip-path="url(#'.$uid.'-body)">', $svg, (string) $size);

            // The sample and a visitor's logo both stay whole, the visitor's without a backing of its own.
            $this->assertStringContainsString(sprintf('<svg x="%s" y="%s" width="%s" height="%s" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet"', $print['x'], $print['y'], $print['width'], $print['height']), $svg, (string) $size);
            $this->assertMatchesRegularExpression('/<image [^>]*preserveAspectRatio="xMidYMid meet" x-bind:href="url" data-motif-image/', $svg, (string) $size);
            $this->assertSame(0, preg_match('/<g x-show="active" style="display:none">\s*<rect/', $svg), "{$size}: the visitor's logo gets no backing");

            // Light and shade of the round body lie over the print, and let clicks through.
            $layer = strpos($svg, 'x-data="motifLayer"');
            $shade = strpos($svg, '<g clip-path="url(#'.$uid.'-body)" pointer-events="none">');
            $this->assertNotFalse($shade, (string) $size);
            $this->assertGreaterThan($layer, $shade, "{$size}: the shade lies over the print");
            $this->assertStringContainsString('fill="url(#'.$uid.'-round)"', substr($svg, $shade), (string) $size);

            // A square logo in the middle of the straight body, clear of its sides, shoulder and foot.
            $this->assertSame($print['width'], $print['height'], (string) $size);
            $this->assertEqualsWithDelta(60.0, $print['x'] + $print['width'] / 2, 0.02, "{$size}: centred across");
            $this->assertGreaterThan(60 - $bottle->width / 2, $print['x'], (string) $size);
            $this->assertLessThan(60 + $bottle->width / 2, $print['x'] + $print['width'], (string) $size);
            $this->assertGreaterThan($bottle->shoulder, $print['y'], (string) $size);
            $this->assertLessThan($bottle->foot - 5, $print['y'] + $print['height'], (string) $size);
            $this->assertGreaterThan(0.6 * $bottle->width, $print['width'], "{$size}: the logo is large enough to see");
        }
    }

    public function test_the_larger_bottle_is_taller_and_both_stand_inside_the_box(): void
    {
        $small = new Bottle(size: '500');
        $large = new Bottle(size: '750');

        foreach ([$small, $large] as $bottle) {
            // A shadow where it stands, and room for the one it casts.
            $this->assertMatchesRegularExpression('/<ellipse cx="60" cy="[\d.]+" rx="[\d.]+" ry="[\d.]+" fill="url\(#'.$bottle->uid.'-contact\)" \/>/', $bottle->render(), $bottle->size);
            $this->assertGreaterThanOrEqual(6.0, $bottle->top, $bottle->size);
            $this->assertLessThanOrEqual(108.0, $bottle->floor, $bottle->size);
            $this->assertGreaterThanOrEqual(10.0, 60 - $bottle->width / 2, $bottle->size);
            $this->assertEqualsWithDelta(56.5, ($bottle->top + $bottle->floor) / 2, 0.01, "{$bottle->size}: centred a little above the middle");

            // Upright and narrow, but as large as the other products in a mixed grid:
            // even the smaller one spans nearly three quarters of the box.
            $this->assertGreaterThan(2.5 * $bottle->width, $bottle->height, $bottle->size);
            $this->assertGreaterThan(0.72 * 120, $bottle->height, $bottle->size);

            // Cap, mouth, shoulder, body and foot from the top down.
            $this->assertLessThan($bottle->cap['y'], $bottle->top, $bottle->size);
            $this->assertLessThan($bottle->ring()['y'] + $bottle->ring()['height'], $bottle->cap['y'] + $bottle->cap['height'], $bottle->size);
            $this->assertLessThan($bottle->shoulder, $bottle->ring()['y'] + $bottle->ring()['height'], $bottle->size);
            $this->assertLessThan($bottle->foot, $bottle->shoulder, $bottle->size);
            $this->assertLessThan($bottle->floor, $bottle->foot, $bottle->size);
        }

        $this->assertGreaterThan(1.12 * $small->height, $large->height, '750 ml stands visibly taller.');
        $this->assertGreaterThan($small->width, $large->width, '750 ml is a little wider.');
        $this->assertGreaterThan(1.15 * ($small->foot - $small->shoulder), $large->foot - $large->shoulder, 'Its body is longer.');
        $this->assertSame($small->cap['width'], $large->cap['width'], 'One range, one cap.');
        $this->assertSame($small->cap['height'], $large->cap['height'], 'One range, one cap.');
    }

    public function test_a_screw_cap_with_a_carry_loop_sits_on_top(): void
    {
        $bottle = new Bottle();
        $svg = $bottle->render();
        $cap = $bottle->cap;

        // The loop rises from the cap's top face, both its feet on it.
        $this->assertSame(1, preg_match('/\AM([\d.]+) ([\d.]+)C([\d.]+) ([\d.]+) ([\d.]+) ([\d.]+) ([\d.]+) ([\d.]+)\z/', $bottle->loop, $loop), $bottle->loop);
        $this->assertEqualsWithDelta($cap['y'], (float) $loop[2], 0.01);
        $this->assertEqualsWithDelta($cap['y'], (float) $loop[8], 0.01);
        $this->assertGreaterThan($cap['x'], (float) $loop[1]);
        $this->assertLessThan($cap['x'] + $cap['width'], (float) $loop[7]);
        $this->assertEqualsWithDelta(60.0, ((float) $loop[1] + (float) $loop[7]) / 2, 0.01);
        $this->assertStringContainsString('<path d="'.$bottle->loop.'" fill="none"', $svg);
        $this->assertSame(1, substr_count($svg, '<ellipse cx="60" cy="'.$cap['y'].'"'), 'The top face of the cap');

        // Narrower than the bottle, ribbed for the grip, on a band of bare steel.
        $this->assertLessThan($bottle->width, $cap['width']);
        $this->assertGreaterThanOrEqual(10, substr_count($svg, 'stroke="#000" stroke-opacity="0.2" />'), 'Grooves of the grip');
        $this->assertStringContainsString('fill="url(#'.$bottle->uid.'-steel)"', $svg);
        $this->assertLessThan($cap['width'], $bottle->ring()['width']);

        // The cap and its loop are drawn after the body: they sit in front of the shoulder.
        $this->assertLessThan(strpos($svg, '<path d="'.$bottle->loop.'"'), strpos($svg, '<path d="'.$bottle->outline.'" fill='));
    }

    public function test_each_colour_shows_its_material_and_takes_the_print_in_an_ink_that_shows(): void
    {
        // The sample "siegel" brings a cream background of its own. On a bottle it
        // is left out and the seal printed straight onto it: in dark ink on steel
        // and on white powder coat, in light ink on black.
        $background = '<rect width="100" height="100" fill="#f1ebdd" />';
        $ink = ['silver' => 'dark', 'black' => 'light', 'white' => 'dark'];
        $seal = ['dark' => '#1d1d1b', 'light' => '#f1ebdd'];
        // A colour each coat is drawn in, and no other coat.
        $coat = ['silver' => '#f6f7f8', 'black' => '#343434', 'white' => '#fbfbf9'];

        foreach (Bottle::COLORS as $color) {
            $bottle = new Bottle(color: $color, motif: 'siegel');
            $svg = $bottle->render();

            $this->assertSame(['bare' => true, 'ink' => $ink[$color]], $bottle->motifVars(), $color);
            $this->assertStringNotContainsString($background, $svg, $color);
            $this->assertStringContainsString('fill="'.$seal[$ink[$color]].'" style="font-family:var(--font-accent);font-style:italic">Fein', $svg, $color);
            $this->assertStringContainsString('stop-color="'.$coat[$color].'"', $svg, $color);

            foreach (array_diff_key($coat, [$color => true]) as $other) {
                $this->assertStringNotContainsString('stop-color="'.$other.'"', $svg, $color);
            }
        }

        // Brushed steel shows fine upright streaks over the whole body; powder coat is smooth.
        $streaks = '/<g clip-path="url\(#bt\d+-body\)">(\s*<path d="M[\d.]+ [\d.]+V[\d.]+" stroke="#(?:fff|000)"[^>]*\/>)+\s*<\/g>/';
        $this->assertMatchesRegularExpression($streaks, stiqon_mockup('bottle', ['color' => 'silver']));
        $this->assertDoesNotMatchRegularExpression($streaks, stiqon_mockup('bottle', ['color' => 'black']));
        $this->assertDoesNotMatchRegularExpression($streaks, stiqon_mockup('bottle', ['color' => 'white']));
    }

    public function test_no_sample_is_printed_in_an_ink_its_bottle_swallows(): void
    {
        foreach (Bottle::motifs() as $motif) {
            $onBlack = $this->sample(stiqon_mockup('bottle', ['color' => 'black', 'motif' => $motif]));

            foreach (['#0f0f0e', '#141412', '#1d1d1b', '#1d3557', '#1f3d2e', '#2f4329', '#7a2e2e'] as $dark) {
                $this->assertStringNotContainsString('"'.$dark.'"', $onBlack, "{$motif} on a black bottle");
            }

            foreach (['silver', 'white'] as $color) {
                $print = $this->sample(stiqon_mockup('bottle', ['color' => $color, 'motif' => $motif]));

                foreach (['#fff', '#ffffff'] as $white) {
                    $this->assertStringNotContainsString('"'.$white.'"', $print, "{$motif} on a {$color} bottle");
                }
            }
        }
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertSame('Trinkflasche', Bottle::label());
        $this->assertSame('Trinkflasche 500 ml, Silber', (new Bottle())->describe());
        $this->assertStringContainsString('aria-label="Trinkflasche 500 ml, Silber"', stiqon_mockup('bottle'));
        $this->assertStringContainsString('aria-label="Trinkflasche 750 ml, Schwarz"', stiqon_mockup('bottle', ['size' => '750', 'color' => 'black']));
        $this->assertStringContainsString('aria-label="Trinkflasche 500 ml, Weiß"', stiqon_mockup('bottle', ['color' => 'white']));
        $this->assertStringContainsString('aria-label="Isolierflasche fürs Büro"', stiqon_mockup('bottle', ['label' => 'Isolierflasche fürs Büro']));
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
            'size' => [static fn () => new Bottle(size: '1000'), 'Unknown bottle size [1000].'],
            'size by its name' => [static fn () => Bottle::fromArray(['size' => '500 ml']), 'Unknown bottle size [500 ml].'],
            'size in litres' => [static fn () => Bottle::fromArray(['size' => '0,5']), 'Unknown bottle size [0,5].'],
            'size padded' => [static fn () => Bottle::fromArray(['size' => '0500']), 'Unknown bottle size [0500].'],
            'motif' => [static fn () => new Bottle(motif: 'gibt-es-nicht'), 'Unknown bottle motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Bottle(motif: '../mockup/flyer'), 'Unknown bottle motif'],
            'flyer motif' => [static fn () => Bottle::fromArray(['motif' => 'prozent']), 'Unknown bottle motif [prozent].'],
            'outline motif' => [static fn () => new Bottle(motif: 'stern'), 'Unknown bottle motif [stern].'],
            'outline motif from a spec' => [static fn () => Bottle::fromArray(['motif' => 'sprechblase']), 'Unknown bottle motif [sprechblase].'],
            'color' => [static fn () => new Bottle(color: 'rot'), 'Unknown bottle color [rot].'],
            'color in German' => [static fn () => Bottle::fromArray(['color' => 'silber']), 'Unknown bottle color [silber].'],
            'color of cotton' => [static fn () => Bottle::fromArray(['color' => 'natural']), 'Unknown bottle color [natural].'],
        ];
    }

    public function test_without_values_the_first_size_motif_and_colour_are_drawn(): void
    {
        $bottle = Bottle::fromArray([]);

        $this->assertSame((string) array_key_first(Bottle::form()['values']), $bottle->size);
        $this->assertSame('500', $bottle->size);
        $this->assertSame(Bottle::motifs()[0], $bottle->motif);
        $this->assertSame(Bottle::COLORS[0], $bottle->color);
        $this->assertNull($bottle->label);
        $this->assertSame((new Bottle())->size, $bottle->size);
        $this->assertSame((new Bottle())->motif, $bottle->motif);
        $this->assertSame((new Bottle())->color, $bottle->color);
    }

    public function test_values_of_other_kinds_are_ignored(): void
    {
        $bottle = Bottle::fromArray(['shape' => 'round', 'format' => 'a6', 'handles' => 'long', 'material' => 'kraft', 'style' => 'classic', 'stack' => true, 'gloss' => true, 'size' => '750', 'color' => 'black']);

        $this->assertSame('750', $bottle->size);
        $this->assertSame('black', $bottle->color);
        $this->assertSame(Bottle::motifs()[0], $bottle->motif);
        $this->assertWellFormed($bottle->render(), 'foreign keys');
    }

    public function test_ids_stay_unique_and_references_resolve_when_bottles_share_a_page(): void
    {
        // The seal brings an id of its own, and so do the checkers in light ink.
        $drawings = [
            stiqon_mockup('bottle', ['motif' => 'siegel']),
            stiqon_mockup('bottle', ['size' => '750', 'color' => 'black', 'motif' => 'siegel']),
            stiqon_mockup('bottle', ['color' => 'black', 'motif' => 'raster']),
        ];

        $this->assertStringContainsString('<mask id="bt3-hallo">', $drawings[2]);

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'bt', 3);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Bottle::keys();
        $form = Bottle::form();

        $this->assertSame(['size', 'motif', 'color'], array_keys($keys));
        $this->assertSame('size', $form['key']);
        $this->assertSame('Füllmenge', $form['label']);
        // As array keys the sizes are numbers; stored and compared, they are strings.
        $this->assertSame([500 => '500 ml', 750 => '750 ml'], $form['values']);
        $this->assertSame(['500', '750'], $keys['size']);
        $this->assertSame(array_map('strval', array_keys($form['values'])), $keys[$form['key']]);
        $this->assertSame(Bottle::motifs(), $keys['motif']);
        $this->assertSame(['silver', 'black', 'white'], $keys['color']);

        $names = Bottle::names();
        $this->assertSame(array_keys($form['values']), array_keys($names['form']));
        $this->assertSame(['size', 'color'], array_keys($names['options']));
        $this->assertSame(array_keys($form['values']), array_keys($names['options']['size']));
        $this->assertEqualsCanonicalizing($keys['color'], array_keys($names['options']['color']));
        $this->assertSame([], $names['switches']);
    }

    public function test_the_kind_is_listed_and_draws_itself(): void
    {
        $this->assertTrue(Kinds::has('bottle'));
        $this->assertSame(Bottle::class, Kinds::of('bottle'));
        $this->assertTrue(is_subclass_of(Bottle::class, Kind::class));
        $this->assertSame('Trinkflasche', Kinds::labels()['bottle']);
        $this->assertNotSame('Form', Bottle::form()['label']);
        $this->assertStringContainsString('data-mockup="bottle"', Kinds::draw('bottle', []));
        $this->assertArrayHasKey(Bottle::form()['key'], Bottle::keys());
    }

    public function test_names_say_a_product_is_a_bottle_its_size_and_its_colour(): void
    {
        foreach (['Trinkflaschen', 'Edelstahlflasche', 'Isolierflaschen', 'Trinkflasche 750 ml', 'Thermosflasche'] as $name) {
            $this->assertSame('bottle', Guess::type($name), $name);
        }

        // A label for bottles is a sticker.
        $this->assertSame('sticker', Guess::type('Flaschenetiketten'));

        $this->assertSame(['size' => '500'], Guess::product('bottle', 'Trinkflasche 500 ml'));
        $this->assertSame(['size' => '750'], Guess::product('bottle', 'Trinkflasche 750ml'));
        $this->assertSame(['size' => '500'], Guess::product('bottle', 'Isolierflasche 0,5 l'));
        $this->assertSame(['size' => '500'], Guess::product('bottle', 'Isolierflasche 0.5 Liter'));
        $this->assertSame(['size' => '750'], Guess::product('bottle', 'Edelstahlflasche 0,75 l'));
        $this->assertSame([], Guess::product('bottle', 'Trinkflaschen'));
        // A larger bottle is not drawn as a small one because its volume ends alike.
        $this->assertSame([], Guess::product('bottle', 'Trinkflasche 1500 ml'));
        $this->assertSame([], Guess::product('bottle', 'Kanister 10,5 l'));

        $this->assertSame(['color' => 'silver'], Guess::option('bottle', 'Silber'));
        $this->assertSame(['color' => 'silver'], Guess::option('bottle', 'Edelstahl gebürstet'));
        $this->assertSame(['color' => 'black'], Guess::option('bottle', 'Schwarz'));
        $this->assertSame(['color' => 'white'], Guess::option('bottle', 'Weiß'));
        $this->assertSame(['color' => 'white'], Guess::option('bottle', 'Weiss'));
        $this->assertSame(['size' => '500'], Guess::option('bottle', '500 ml'));
        $this->assertSame(['size' => '750'], Guess::option('bottle', '750 ml'));
        $this->assertSame([], Guess::option('bottle', '25 Stück'));

        // Every bottle is of steel: its colour, when the name gives one, wins.
        $this->assertSame(['color' => 'silver'], Guess::named('bottle', 'Edelstahlflasche'));
        $this->assertSame(['color' => 'black'], Guess::named('bottle', 'Edelstahlflasche, schwarz'));
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        $resolved = Spec::resolve(['name' => 'Trinkflaschen'], [['name' => 'Schwarz'], ['name' => '750 ml'], ['name' => '25 Stück']]);

        $this->assertSame('bottle', $resolved['type']);
        $this->assertSame('750', $resolved['spec']['size']);
        $this->assertSame('black', $resolved['spec']['color']);
        $this->assertContains($resolved['spec']['motif'], Bottle::motifs());

        $svg = Kinds::draw($resolved['type'], $resolved['spec']);
        $this->assertWellFormed($svg, 'resolved');
        $this->assertStringContainsString('aria-label="Trinkflasche 750 ml, Schwarz"', $svg);

        foreach (['Edelstahlflasche' => 'silver', 'Isolierflaschen' => 'silver'] as $name => $color) {
            $resolved = Spec::resolve(['name' => $name], [['name' => '500 ml']]);

            $this->assertSame('bottle', $resolved['type'], $name);
            $this->assertSame('500', $resolved['spec']['size'], $name);
            $this->assertWellFormed(Kinds::draw($resolved['type'], $resolved['spec']), $name);
        }

        // A size stored on the product, as the administration stores it.
        $resolved = Spec::resolve(['name' => 'Isolierflaschen', 'stored' => ['size' => '750', 'color' => 'white']]);
        $this->assertSame(['size' => '750', 'color' => 'white'], array_intersect_key($resolved['spec'], ['size' => true, 'color' => true]));
        // Values the drawing does not know are left out: as a number the size is not one of its strings.
        $resolved = Spec::resolve(['name' => 'Isolierflaschen', 'stored' => ['size' => 750, 'color' => 'gold']]);
        $this->assertArrayNotHasKey('size', $resolved['spec']);
        $this->assertArrayNotHasKey('color', $resolved['spec']);
    }

    public function test_every_bottle_of_the_example_assortment_is_drawn_as_offered(): void
    {
        $assortment = require dirname(__DIR__, 3).'/tools/dev/example-assortment.php';
        $terms = array_map(static fn (array $attribute): array => $attribute[1], $assortment['attributes']);
        $bottles = array_values(array_filter($assortment['products'], static fn (array $product): bool => $product['drawing']['type'] === 'bottle'));
        $colors = ['silber' => 'silver', 'schwarz' => 'black', 'weiss' => 'white'];
        $sizes = ['500-ml' => '500', '750-ml' => '750'];

        $this->assertNotEmpty($bottles);

        foreach ($bottles as $product) {
            $this->assertSame('bottle', Guess::type($product['name']), $product['name']);

            foreach ($product['offers']['farbe'] as $color) {
                foreach ($product['offers']['format'] as $size) {
                    $options = [['name' => $terms['farbe'][$color]], ['name' => $terms['format'][$size]], ['name' => $terms['auflage'][$product['offers']['auflage'][0]]]];
                    $resolved = Spec::resolve(['name' => $product['name'], 'stored' => $product['drawing']], $options);
                    $what = "{$product['name']}, {$color}, {$size}";

                    $this->assertSame('bottle', $resolved['type'], $what);
                    $this->assertSame($colors[$color], $resolved['spec']['color'], $what);
                    $this->assertSame($sizes[$size], $resolved['spec']['size'], $what);
                    $this->assertWellFormed(Kinds::draw($resolved['type'], $resolved['spec']), $what);
                }
            }
        }
    }

    public function test_text_from_outside_cannot_break_out_of_an_attribute(): void
    {
        $hostile = 'A "B" <c> & \'d\'';
        $escaped = 'A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;';

        foreach (['label' => stiqon_mockup('bottle', ['label' => $hostile]), 'class' => stiqon_mockup('bottle', ['color' => 'black'], $hostile)] as $what => $markup) {
            $this->assertStringContainsString($escaped, $markup, $what);
            $this->assertStringNotContainsString('<c>', $markup, $what);
        }
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b[^"]*\blift\b/', stiqon_mockup('bottle'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('bottle', [], 'w-[220px]'));
        $this->assertStringContainsString('viewBox="0 0 120 120"', stiqon_mockup('bottle'));
    }

    public function test_the_drawing_is_plain_svg(): void
    {
        foreach (Bottle::COLORS as $color) {
            $svg = stiqon_mockup('bottle', ['color' => $color, 'motif' => 'neu']);
            // The drawing's own markup, without the sample motif printed on it.
            $own = preg_replace('/<svg[^>]*data-motif-sample[^>]*>.*?<\/svg>/s', '', $svg);

            foreach (['<script', '<style', '<text', 'class="', 'href="http', 'href="//'] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, substr($own, strpos($own, '>') + 1), "{$color}: {$forbidden}");
            }
        }
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
