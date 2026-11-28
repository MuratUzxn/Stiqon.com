<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Coaster;
use Stiqon\Shop\Mockup\Kind;
use Stiqon\Shop\Mockup\Kinds;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Mockup\Uid;

/**
 * A beer mat of thick board lying a little askew, its cut edge showing: round
 * or square, printed all over, alone or on a small stack.
 */
final class CoasterTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_either_shape_alone_and_stacked(): void
    {
        $this->assertGreaterThanOrEqual(12, count(Coaster::motifs()));

        foreach (Coaster::motifs() as $motif) {
            foreach (array_keys(Coaster::form()['values']) as $shape) {
                foreach ([false, true] as $stack) {
                    $svg = stiqon_mockup('coaster', compact('shape', 'motif', 'stack'));
                    $what = "{$motif} on a {$shape} mat".($stack ? ', stacked' : '');

                    $this->assertWellFormed($svg, $what);
                    $this->assertStringContainsString('data-mockup="coaster"', $svg, $what);
                    $this->assertStringContainsString('data-motif-image', $svg, $what);
                    $this->assertStringContainsString('data-motif-sample', $svg, $what);
                }
            }
        }
    }

    public function test_every_value_and_the_stack_change_the_drawing(): void
    {
        $plain = stiqon_mockup('coaster');

        foreach (['shape' => ['square'], 'stack' => [true], 'motif' => array_slice(Coaster::motifs(), 1)] as $key => $values) {
            foreach ($values as $value) {
                Uid::reset();
                $this->assertNotSame($plain, stiqon_mockup('coaster', [$key => $value]), $key.' '.var_export($value, true));
            }
        }

        Uid::reset();
        $this->assertSame($plain, stiqon_mockup('coaster', ['stack' => false]), 'Without a stack the mat lies alone.');
    }

    public function test_the_print_covers_the_whole_top_up_to_its_edge(): void
    {
        foreach (array_keys(Coaster::SHAPES) as $shape) {
            $coaster = new Coaster(shape: $shape, motif: 'siegel');
            $svg = $coaster->render();
            $uid = $coaster->uid;
            $size = $coaster->size;

            // The print lies on the top, turned and squashed with it, and is cut to its outline.
            $this->assertStringContainsString('<clipPath id="'.$uid.'-face">'."\n".'            <rect width="'.$size.'" height="'.$size.'" rx="'.$coaster->radius.'" />', $svg, $shape);
            $this->assertMatchesRegularExpression('/<g transform="'.preg_quote($coaster->matrix(), '/').'">\s*<rect width="'.$size.'" height="'.$size.'" rx="'.$coaster->radius.'" fill="#ffffff" \/>\s*<g x-data="motifLayer" clip-path="url\(#'.$uid.'-face\)">/', $svg, $shape);

            // Sample and visitor's motif both fill the top, the visitor's on white board.
            $this->assertStringContainsString('<svg x="0" y="0" width="'.$size.'" height="'.$size.'" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid slice"', $svg, $shape);
            $this->assertMatchesRegularExpression('/<g x-show="active" style="display:none">\s*<rect x="0" y="0" width="'.$size.'" height="'.$size.'" fill="#fff" \/>\s*<image [^>]*preserveAspectRatio="xMidYMid slice" x-bind:href="url" data-motif-image/', $svg, $shape);

            // Printed with its own ground, all over: the seal's cream background is there.
            $this->assertSame(['bare' => false], $coaster->motifVars(), $shape);
            $this->assertStringContainsString('<rect width="100" height="100" fill="#f1ebdd" />', $svg, $shape);

            // The light on the board lies over the print and lets clicks through.
            $this->assertGreaterThan(strpos($svg, 'x-data="motifLayer"'), strpos($svg, 'fill="url(#'.$uid.'-light)" pointer-events="none"'), $shape);
        }
    }

    public function test_the_mat_lies_askew_and_its_edge_shows_below_the_top(): void
    {
        foreach (array_keys(Coaster::SHAPES) as $shape) {
            $coaster = new Coaster(shape: $shape);
            [$a, $b, $c, $d] = $coaster->face;

            // Turned in its plane a little, then seen from above at an angle.
            $this->assertLessThanOrEqual(-5.0, Coaster::TURN);
            $this->assertGreaterThanOrEqual(-15.0, Coaster::TURN);
            $this->assertEqualsWithDelta(cos(deg2rad(Coaster::TURN)), $a, 0.0001, $shape);
            $this->assertEqualsWithDelta(-sin(deg2rad(Coaster::TURN)), $c, 0.0001, $shape);
            $this->assertEqualsWithDelta(Coaster::VIEW, $a * $d - $b * $c, 0.0001, "{$shape}: squashed in height only");
            $this->assertGreaterThan(0.7, Coaster::VIEW, "{$shape}: nearly from above, so the print reads");
            $this->assertLessThan(0.9, Coaster::VIEW, "{$shape}: from far enough aside for the edge to show");

            // The edge runs straight down from where the top is widest, and the top's own
            // outline passes through those points.
            [[$leftX, $leftY], [$rightX, $rightY]] = $coaster->widest;
            $this->assertSame("{$leftX},{$leftY} {$rightX},{$rightY} {$rightX},".round($rightY + Coaster::EDGE, 2)." {$leftX},".round($leftY + Coaster::EDGE, 2), $coaster->side(0, 0.0, 1.0), $shape);
            $points = $this->points($coaster->outline());
            $this->assertEqualsWithDelta($leftX, min(array_column($points, 0)), 0.6, "{$shape}: widest to the left");
            $this->assertEqualsWithDelta($rightX, max(array_column($points, 0)), 0.6, "{$shape}: widest to the right");

            // One edge further down, the outline is the same, moved straight down.
            foreach (array_map(null, $points, $this->points($coaster->outline(0, 1.0))) as [$top, $bottom]) {
                $this->assertEqualsWithDelta($top[0], $bottom[0], 0.011, $shape);
                $this->assertEqualsWithDelta($top[1] + Coaster::EDGE, $bottom[1], 0.011, $shape);
            }

            // The edge is board: lit above, a shade darker below, with fibres in it; the top's
            // rim catches the light.
            $svg = $coaster->render();
            $this->assertSame(2, substr_count($svg, 'fill="url(#'.$coaster->uid.'-edge)"'), $shape);
            $this->assertSame(2, substr_count($svg, 'fill="url(#'.$coaster->uid.'-lit)"'), $shape);
            $this->assertSame(2, substr_count($svg, 'fill="url(#'.$coaster->uid.'-fibres)"'), $shape);
            $this->assertStringContainsString('stroke="#fff" stroke-opacity="0.6" stroke-width="0.7" clip-path="url(#'.$coaster->uid.'-face)"', $svg, $shape);
        }
    }

    public function test_round_and_square_mats_are_drawn_true_to_each_other(): void
    {
        $round = new Coaster(shape: 'round');
        $square = new Coaster(shape: 'square');

        // A disc of 107 mm, a square of 93 mm with rounded corners.
        $this->assertSame($round->size / 2, $round->radius);
        $this->assertStringNotContainsString('L', $round->outline());
        $this->assertSame(4, substr_count($round->outline(), 'A'));
        $this->assertLessThan($square->size / 4, $square->radius);
        $this->assertSame(4, substr_count($square->outline(), 'L'));
        $this->assertEqualsWithDelta(93 / 107, $square->size / $round->size, 0.01);

        foreach ([$round, $square] as $coaster) {
            $extent = $coaster->extent;

            // Inside the card and nearly filling it, with room below for the shadow.
            $this->assertGreaterThanOrEqual(10.0, $extent['x'], $coaster->shape);
            $this->assertLessThanOrEqual(110.0, $extent['x'] + $extent['width'], $coaster->shape);
            $this->assertGreaterThanOrEqual(10.0, $extent['y'], $coaster->shape);
            $this->assertLessThanOrEqual(104.0, $extent['y'] + $extent['height'], $coaster->shape);
            $this->assertGreaterThanOrEqual(0.75 * 120, $extent['width'], $coaster->shape);
            $this->assertLessThanOrEqual(0.9 * 120, $extent['width'], $coaster->shape);
            $this->assertEqualsWithDelta(57.0, $extent['y'] + $extent['height'] / 2, 0.01, "{$coaster->shape}: centred a little above the middle");
        }
    }

    public function test_a_stack_shows_the_edges_of_the_mats_underneath(): void
    {
        foreach (array_keys(Coaster::SHAPES) as $shape) {
            $alone = new Coaster(shape: $shape);
            $stack = new Coaster(shape: $shape, stack: true);
            $svg = $stack->render();

            $this->assertSame([0], $alone->levels(), $shape);
            $this->assertSame(range(count(Coaster::STACK) - 1, 0), $stack->levels(), $shape);
            $this->assertGreaterThanOrEqual(3, count($stack->levels()) - 1, "{$shape}: a few mats underneath");

            // Each mat its own edge; the mats underneath are drawn first, the printed one last.
            $this->assertSame(2 * count($stack->levels()), substr_count($svg, 'fill="url(#'.$stack->uid.'-edge)"'), $shape);
            $this->assertLessThan(strpos($svg, 'x-data="motifLayer"'), strrpos($svg, 'fill="url(#'.$stack->uid.'-fibres)"'), $shape);
            $this->assertStringContainsString('<path d="'.$stack->outline(1).'" fill="#cfc6b4" />', $svg, $shape);
            $this->assertStringNotContainsString('fill="#cfc6b4"', $alone->render(), $shape);

            // Stacked by hand: not all mats lie flush.
            $this->assertGreaterThan(1, count(array_unique(Coaster::STACK)));
            $this->assertSame(0.0, Coaster::STACK[0], 'The top mat lies where a single one would.');

            // The stack is taller by the edges underneath, and the whole stays centred.
            $this->assertEqualsWithDelta($alone->extent['height'] + (count($stack->levels()) - 1) * Coaster::EDGE, $stack->extent['height'], 0.02, $shape);
            $this->assertLessThan($alone->extent['y'], $stack->extent['y'], "{$shape}: the top moves up");
            $this->assertEqualsWithDelta(57.0, $stack->extent['y'] + $stack->extent['height'] / 2, 0.01, $shape);
            $this->assertLessThanOrEqual(104.0, $stack->extent['y'] + $stack->extent['height'], $shape);
        }
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertSame('Bierdeckel', Coaster::label());
        $this->assertSame('Bierdeckel, rund', (new Coaster())->describe());
        $this->assertStringContainsString('aria-label="Bierdeckel, rund"', stiqon_mockup('coaster'));
        $this->assertStringContainsString('aria-label="Bierdeckel, eckig"', stiqon_mockup('coaster', ['shape' => 'square']));
        $this->assertStringContainsString('aria-label="Bierdeckel, eckig, Stapel"', stiqon_mockup('coaster', ['shape' => 'square', 'stack' => true]));
        $this->assertStringContainsString('aria-label="Bierdeckel, rund, Stapel"', stiqon_mockup('coaster', ['stack' => true]));
        $this->assertStringContainsString('aria-label="Untersetzer für die Bar"', stiqon_mockup('coaster', ['label' => 'Untersetzer für die Bar']));
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
            'shape' => [static fn () => new Coaster(shape: 'oval'), 'Unknown coaster shape [oval].'],
            'shape in German' => [static fn () => Coaster::fromArray(['shape' => 'Rund']), 'Unknown coaster shape [Rund].'],
            'shape of a sticker' => [static fn () => Coaster::fromArray(['shape' => 'circle']), 'Unknown coaster shape [circle].'],
            'motif' => [static fn () => new Coaster(motif: 'gibt-es-nicht'), 'Unknown coaster motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Coaster(motif: '../mockup/flyer'), 'Unknown coaster motif'],
            'flyer motif' => [static fn () => Coaster::fromArray(['motif' => 'fest']), 'Unknown coaster motif [fest].'],
        ];
    }

    public function test_without_values_the_first_shape_and_motif_are_drawn_alone(): void
    {
        $coaster = Coaster::fromArray([]);

        $this->assertSame(array_key_first(Coaster::form()['values']), $coaster->shape);
        $this->assertSame('round', $coaster->shape);
        $this->assertSame(Coaster::motifs()[0], $coaster->motif);
        $this->assertFalse($coaster->stack);
        $this->assertNull($coaster->label);
        $this->assertSame((new Coaster())->shape, $coaster->shape);
        $this->assertSame((new Coaster())->motif, $coaster->motif);
        $this->assertSame((new Coaster())->stack, $coaster->stack);
    }

    public function test_the_stack_is_a_switch(): void
    {
        $this->assertTrue(Coaster::fromArray(['stack' => 1])->stack);
        $this->assertTrue(Coaster::fromArray(['stack' => '1'])->stack);
        $this->assertFalse(Coaster::fromArray(['stack' => 0])->stack);
        $this->assertFalse(Coaster::fromArray(['stack' => ''])->stack);
        $this->assertFalse(Coaster::fromArray(['stack' => null])->stack);
        $this->assertNull(Coaster::keys()['stack']);
    }

    public function test_values_of_other_kinds_are_ignored(): void
    {
        $coaster = Coaster::fromArray(['size' => '750', 'color' => 'black', 'format' => 'a6', 'handles' => 'long', 'material' => 'kraft', 'gloss' => true, 'peel' => true, 'shape' => 'square', 'stack' => true]);

        $this->assertSame('square', $coaster->shape);
        $this->assertTrue($coaster->stack);
        $this->assertSame(Coaster::motifs()[0], $coaster->motif);
        $this->assertWellFormed($coaster->render(), 'foreign keys');
    }

    public function test_ids_stay_unique_and_references_resolve_when_mats_share_a_page(): void
    {
        // The seal brings an id of its own.
        $drawings = [
            stiqon_mockup('coaster', ['motif' => 'siegel']),
            stiqon_mockup('coaster', ['shape' => 'square', 'stack' => true, 'motif' => 'siegel']),
            stiqon_mockup('coaster', ['stack' => true, 'motif' => 'raster']),
        ];

        $this->assertStringContainsString('id="co1-ring"', $drawings[0]);

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'co', 3);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Coaster::keys();
        $form = Coaster::form();

        $this->assertSame(['shape', 'motif', 'stack'], array_keys($keys));
        $this->assertSame('shape', $form['key']);
        $this->assertSame('Zuschnitt', $form['label']);
        $this->assertSame(['round' => 'Rund', 'square' => 'Eckig'], $form['values']);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Coaster::motifs(), $keys['motif']);
        $this->assertSame(Sticker::motifs(), Coaster::motifs());

        $names = Coaster::names();
        $this->assertSame(array_keys($form['values']), array_keys($names['form']));
        $this->assertSame(['shape'], array_keys($names['options']));
        $this->assertSame(array_keys($form['values']), array_keys($names['options']['shape']));
        $this->assertSame([], $names['switches']);
    }

    public function test_the_kind_is_listed_and_draws_itself(): void
    {
        $this->assertTrue(Kinds::has('coaster'));
        $this->assertSame(Coaster::class, Kinds::of('coaster'));
        $this->assertTrue(is_subclass_of(Coaster::class, Kind::class));
        $this->assertSame('Bierdeckel', Kinds::labels()['coaster']);
        $this->assertNotSame('Form', Coaster::form()['label']);
        $this->assertStringContainsString('data-mockup="coaster"', Kinds::draw('coaster', []));
        $this->assertArrayHasKey(Coaster::form()['key'], Coaster::keys());
    }

    public function test_names_say_a_product_is_a_beer_mat_and_its_shape(): void
    {
        foreach (['Bierdeckel', 'Untersetzer', 'Glasuntersetzer', 'Runde Bierdeckel', 'Getränkeuntersetzer'] as $name) {
            $this->assertSame('coaster', Guess::type($name), $name);
        }

        $this->assertSame(['shape' => 'round'], Guess::product('coaster', 'Runde Bierdeckel'));
        $this->assertSame(['shape' => 'round'], Guess::product('coaster', 'Bierdeckel, kreisrund'));
        $this->assertSame(['shape' => 'square'], Guess::product('coaster', 'Bierdeckel eckig'));
        $this->assertSame(['shape' => 'square'], Guess::product('coaster', 'Quadratische Untersetzer'));
        $this->assertSame(['shape' => 'square'], Guess::product('coaster', 'Viereckige Glasuntersetzer'));
        $this->assertSame([], Guess::product('coaster', 'Bierdeckel'));
        // A rounded corner is no round mat.
        $this->assertSame([], Guess::product('coaster', 'Untersetzer mit abgerundeten Ecken'));

        $this->assertSame(['shape' => 'round'], Guess::option('coaster', 'Rund 107 mm'));
        $this->assertSame(['shape' => 'square'], Guess::option('coaster', 'Eckig 93 × 93 mm'));
        $this->assertSame([], Guess::option('coaster', '500 Stück'));
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        foreach (['Rund 107 mm' => 'round', 'Eckig 93 × 93 mm' => 'square'] as $option => $shape) {
            $resolved = Spec::resolve(['name' => 'Bierdeckel'], [['name' => $option], ['name' => '1000 Stück']]);

            $this->assertSame('coaster', $resolved['type'], $option);
            $this->assertSame($shape, $resolved['spec']['shape'], $option);
            $this->assertContains($resolved['spec']['motif'], Coaster::motifs(), $option);

            $svg = Kinds::draw($resolved['type'], $resolved['spec'] + ['stack' => true]);
            $this->assertWellFormed($svg, $option);
            $this->assertStringContainsString('aria-label="Bierdeckel, '.['round' => 'rund', 'square' => 'eckig'][$shape].', Stapel"', $svg, $option);
        }

        foreach (['Untersetzer', 'Glasuntersetzer'] as $name) {
            $resolved = Spec::resolve(['name' => $name]);

            $this->assertSame('coaster', $resolved['type'], $name);
            $this->assertWellFormed(Kinds::draw($resolved['type'], $resolved['spec']), $name);
        }

        // A shape stored on the product, as the administration stores it; the stack is a switch.
        $resolved = Spec::resolve(['name' => 'Bierdeckel', 'stored' => ['shape' => 'square', 'stack' => '1']]);
        $this->assertSame('square', $resolved['spec']['shape']);
        $this->assertTrue($resolved['spec']['stack']);
    }

    public function test_every_beer_mat_of_the_example_assortment_is_drawn_as_offered(): void
    {
        $assortment = require dirname(__DIR__, 3).'/tools/dev/example-assortment.php';
        $terms = array_map(static fn (array $attribute): array => $attribute[1], $assortment['attributes']);
        $mats = array_values(array_filter($assortment['products'], static fn (array $product): bool => $product['drawing']['type'] === 'coaster'));
        $shapes = ['rund-107' => 'round', 'eckig-93' => 'square'];

        $this->assertNotEmpty($mats);

        foreach ($mats as $product) {
            $this->assertSame('coaster', Guess::type($product['name']), $product['name']);

            foreach ($product['offers']['format'] as $format) {
                $options = [['name' => $terms['format'][$format]], ['name' => $terms['auflage'][$product['offers']['auflage'][0]]]];
                $resolved = Spec::resolve(['name' => $product['name'], 'stored' => $product['drawing']], $options);
                $what = "{$product['name']}, {$format}";

                $this->assertSame('coaster', $resolved['type'], $what);
                $this->assertSame($shapes[$format], $resolved['spec']['shape'], $what);
                $this->assertWellFormed(Kinds::draw($resolved['type'], $resolved['spec']), $what);
            }
        }
    }

    public function test_text_from_outside_cannot_break_out_of_an_attribute(): void
    {
        $hostile = 'A "B" <c> & \'d\'';
        $escaped = 'A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;';

        foreach (['label' => stiqon_mockup('coaster', ['label' => $hostile]), 'class' => stiqon_mockup('coaster', ['shape' => 'square'], $hostile)] as $what => $markup) {
            $this->assertStringContainsString($escaped, $markup, $what);
            $this->assertStringNotContainsString('<c>', $markup, $what);
        }
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b[^"]*\blift\b/', stiqon_mockup('coaster'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('coaster', [], 'w-[220px]'));
        $this->assertStringContainsString('viewBox="0 0 120 120"', stiqon_mockup('coaster'));
    }

    public function test_the_drawing_is_plain_svg(): void
    {
        foreach (array_keys(Coaster::SHAPES) as $shape) {
            $svg = stiqon_mockup('coaster', ['shape' => $shape, 'stack' => true, 'motif' => 'neu']);
            // The drawing's own markup, without the sample motif printed on it.
            $own = preg_replace('/<svg[^>]*data-motif-sample[^>]*>.*?<\/svg>/s', '', $svg);

            foreach (['<script', '<style', '<text', 'class="', 'href="http', 'href="//'] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, substr($own, strpos($own, '>') + 1), "{$shape}: {$forbidden}");
            }
        }
    }

    /**
     * The points a path runs through, its arcs' radii left out.
     *
     * @return list<array{0: float, 1: float}>
     */
    private function points(string $path): array
    {
        $this->assertGreaterThanOrEqual(5, preg_match_all('/(?:[ML]|A[\d.]+ [\d.]+ 0 0 1 )(-?[\d.]+) (-?[\d.]+)/', $path, $matches, PREG_SET_ORDER), $path);

        return array_map(static fn (array $match): array => [(float) $match[1], (float) $match[2]], $matches);
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
