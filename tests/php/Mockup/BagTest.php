<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Bag;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Mockup\Uid;

/**
 * A paper carrier bag, standing open: small or large, brown kraft or white
 * paper, two twisted handles and a logo printed on the front.
 */
final class BagTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_every_size_and_colour(): void
    {
        $this->assertGreaterThanOrEqual(12, count(Bag::motifs()));

        foreach (Bag::motifs() as $motif) {
            foreach (array_keys(Bag::form()['values']) as $size) {
                foreach (Bag::COLORS as $color) {
                    $svg = stiqon_mockup('bag', compact('size', 'motif', 'color'));

                    $this->assertWellFormed($svg, "{$motif} on {$size}/{$color}");
                    $this->assertStringContainsString('data-mockup="bag"', $svg);
                    $this->assertStringContainsString('data-motif-image', $svg);
                    $this->assertStringContainsString('data-motif-sample', $svg);
                }
            }
        }
    }

    public function test_the_logo_is_printed_on_the_front_a_little_above_the_middle(): void
    {
        foreach (array_keys(Bag::SIZES) as $size) {
            $bag = new Bag(size: $size, motif: 'siegel');
            $panel = $bag->panel;
            $print = $bag->printBox;

            $this->assertStringContainsString(sprintf('<svg x="%s" y="%s" width="%s" height="%s" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet"', $print['x'], $print['y'], $print['width'], $print['height']), $bag->render(), $size);
            $this->assertSame($print['width'], $print['height'], $size);
            $this->assertEqualsWithDelta($panel['x'] + $panel['width'] / 2, $print['x'] + $print['width'] / 2, 0.02, "{$size}: centred");
            $this->assertGreaterThan($panel['y'] + 0.4 * $panel['height'], $print['y'] + $print['height'] / 2, $size);
            $this->assertLessThan($panel['y'] + 0.5 * $panel['height'], $print['y'] + $print['height'] / 2, $size);
            $this->assertGreaterThan($panel['y'] + 5, $print['y'], "{$size}: below the folded top");
            $this->assertGreaterThan(0.45 * $panel['width'], $print['width'], "{$size}: large enough to see");
        }
    }

    public function test_the_handles_are_glued_inside_the_bag(): void
    {
        foreach (array_keys(Bag::SIZES) as $size) {
            $bag = new Bag(size: $size);
            $svg = $bag->render();
            $panel = $bag->panel;
            [$dx, $dy] = $bag->recede;
            [$back, $front] = array_map([$this, 'loop'], $bag->handles);
            $patch = $bag->patch;

            // The front handle's ends are hidden by the front, drawn over them.
            $this->assertGreaterThan($panel['y'], $front['ends'], $size);
            $this->assertLessThan(
                strpos($svg, '<rect x="'.$panel['x'].'" y="'.$panel['y'].'" width="'.$panel['width'].'" height="'.$panel['height'].'"'),
                strpos($svg, '<path d="'.$bag->handles[1].'"'),
                "{$size}: the front comes after the front handle",
            );

            // The back handle's ends show in the opening, under the strip of paper glued over them.
            $this->assertGreaterThan($panel['y'] + $dy, $back['ends'], $size);
            $this->assertLessThan($panel['y'], $back['ends'], $size);
            $this->assertGreaterThan($patch['y'], $back['ends'], $size);
            $this->assertLessThan($patch['y'] + $patch['height'], $back['ends'], $size);
            $this->assertLessThan($back['from'], $patch['x'], $size);
            $this->assertGreaterThan($back['to'], $patch['x'] + $patch['width'], $size);
            $this->assertLessThan(strpos($svg, '<rect x="'.$patch['x'].'" y="'.$patch['y'].'"'), strpos($svg, '<path d="'.$bag->handles[0].'"'), $size);
            $this->assertLessThan(strpos($svg, '<path d="'.$bag->handles[1].'"'), strpos($svg, '<rect x="'.$patch['x'].'" y="'.$patch['y'].'"'), $size);

            // Both rise above the bag; the back one lies behind, to the right and higher.
            $this->assertLessThan($panel['y'] - 10, $front['top'], $size);
            $this->assertEqualsWithDelta($front['from'] + $dx, $back['from'], 0.02, $size);
            $this->assertEqualsWithDelta($front['top'] + $dy, $back['top'], 0.02, $size);
        }
    }

    public function test_the_large_bag_is_larger_and_both_fit_the_card(): void
    {
        $small = new Bag(size: 'small');
        $large = new Bag(size: 'large');

        $this->assertGreaterThan($small->panel['width'], $large->panel['width']);
        $this->assertGreaterThan($small->panel['height'], $large->panel['height']);
        $this->assertGreaterThan(0.75, $small->panel['height'] / $large->panel['height'], 'The small bag is still well visible.');

        foreach ([$small, $large] as $bag) {
            $panel = $bag->panel;
            [$dx, $dy] = $bag->recede;
            $top = min(array_column(array_map([$this, 'loop'], $bag->handles), 'top'));

            // Proportions of the real bags: 24 × 32 cm and 32 × 41 cm.
            $this->assertEqualsWithDelta(Bag::SIZES[$bag->size]['height'] / Bag::SIZES[$bag->size]['width'], $panel['height'] / $panel['width'], 0.001);
            $this->assertGreaterThan(1.25, $panel['height'] / $panel['width'], $bag->size);
            $this->assertGreaterThan(0.0, $dx, $bag->size);
            $this->assertLessThan(0.0, $dy, $bag->size);

            // Inside the card, with room below for the shadow.
            $this->assertGreaterThanOrEqual(10.0, $panel['x'], $bag->size);
            $this->assertLessThanOrEqual(110.0, $panel['x'] + $panel['width'] + $dx, $bag->size);
            $this->assertGreaterThanOrEqual(9.0, $top, $bag->size);
            $this->assertLessThanOrEqual(106.0, $panel['y'] + $panel['height'], $bag->size);
        }
    }

    public function test_kraft_shows_through_the_logo_and_white_paper_takes_it_whole(): void
    {
        // The sample "siegel" brings a cream background of its own.
        $background = '<rect width="100" height="100" fill="#f1ebdd" />';

        $this->assertTrue((new Bag(color: 'kraft'))->bareMotif());
        $this->assertFalse((new Bag(color: 'white'))->bareMotif());
        $this->assertStringNotContainsString($background, stiqon_mockup('bag', ['color' => 'kraft', 'motif' => 'siegel']));
        $this->assertStringContainsString($background, stiqon_mockup('bag', ['color' => 'white', 'motif' => 'siegel']));

        $this->assertStringContainsString('-fibre)', stiqon_mockup('bag', ['color' => 'kraft']));
        $this->assertStringNotContainsString('-fibre', stiqon_mockup('bag', ['color' => 'white']));
        $this->assertStringContainsString('fill="#c9a57c"', stiqon_mockup('bag', ['color' => 'kraft']));
        $this->assertStringNotContainsString('fill="#c9a57c"', stiqon_mockup('bag', ['color' => 'white']));
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertSame('Papiertragetasche', Bag::label());
        $this->assertStringContainsString('aria-label="Kleine Papiertragetasche, braun"', stiqon_mockup('bag'));
        $this->assertStringContainsString('aria-label="Große Papiertragetasche, braun"', stiqon_mockup('bag', ['size' => 'large']));
        $this->assertStringContainsString('aria-label="Kleine Papiertragetasche, weiß"', stiqon_mockup('bag', ['color' => 'white']));
        $this->assertStringContainsString('aria-label="Tüten fürs Café"', stiqon_mockup('bag', ['label' => 'Tüten fürs Café']));
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
            'size' => [static fn () => new Bag(size: 'medium'), 'Unknown bag size [medium].'],
            'size in German' => [static fn () => Bag::fromArray(['size' => 'klein']), 'Unknown bag size [klein].'],
            'size of a box' => [static fn () => Bag::fromArray(['size' => 's']), 'Unknown bag size [s].'],
            'motif' => [static fn () => new Bag(motif: 'gibt-es-nicht'), 'Unknown bag motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Bag(motif: '../mockup/flyer'), 'Unknown bag motif'],
            'flyer motif' => [static fn () => Bag::fromArray(['motif' => 'karte']), 'Unknown bag motif [karte].'],
            'color' => [static fn () => new Bag(color: 'black'), 'Unknown bag color [black].'],
            'color in capitals' => [static fn () => Bag::fromArray(['color' => 'Kraft']), 'Unknown bag color [Kraft].'],
        ];
    }

    public function test_without_values_the_first_size_motif_and_colour_are_drawn(): void
    {
        $bag = Bag::fromArray([]);

        $this->assertSame(array_key_first(Bag::form()['values']), $bag->size);
        $this->assertSame('small', $bag->size);
        $this->assertSame(Bag::motifs()[0], $bag->motif);
        $this->assertSame(Bag::COLORS[0], $bag->color);
        $this->assertNull($bag->label);
        $this->assertSame((new Bag())->size, $bag->size);
        $this->assertSame((new Bag())->motif, $bag->motif);
        $this->assertSame((new Bag())->color, $bag->color);
    }

    public function test_values_of_other_kinds_are_ignored(): void
    {
        $bag = Bag::fromArray(['shape' => 'dreieck', 'format' => 'a4', 'handles' => 'short', 'material' => 'holo', 'peel' => true, 'heavy' => true, 'size' => 'large', 'color' => 'white']);

        $this->assertSame('large', $bag->size);
        $this->assertSame('white', $bag->color);
        $this->assertSame(Bag::motifs()[0], $bag->motif);
        $this->assertWellFormed($bag->render(), 'foreign keys');
    }

    public function test_ids_stay_unique_and_references_resolve_when_bags_share_a_page(): void
    {
        $drawings = [
            stiqon_mockup('bag', ['motif' => 'siegel']),
            stiqon_mockup('bag', ['size' => 'large', 'color' => 'white', 'motif' => 'siegel']),
            stiqon_mockup('bag', ['size' => 'large', 'motif' => 'wellen']),
        ];

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'bg', 3);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Bag::keys();
        $form = Bag::form();

        $this->assertSame(['size', 'motif', 'color'], array_keys($keys));
        $this->assertSame('size', $form['key']);
        $this->assertSame('Größe', $form['label']);
        $this->assertSame(['small' => 'Klein', 'large' => 'Groß'], $form['values']);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Bag::motifs(), $keys['motif']);
        $this->assertSame(Sticker::motifs(), Bag::motifs());
        $this->assertSame(['kraft', 'white'], $keys['color']);

        $names = Bag::names();
        $this->assertSame(array_keys($form['values']), array_keys($names['form']));
        $this->assertSame(['size', 'color'], array_keys($names['options']));
        $this->assertSame(array_keys($form['values']), array_keys($names['options']['size']));
        $this->assertEqualsCanonicalizing($keys['color'], array_keys($names['options']['color']));
        $this->assertSame([], $names['switches']);
    }

    public function test_names_say_a_product_is_a_paper_bag_how_large_and_what_colour(): void
    {
        foreach (['Papiertragetaschen', 'Papiertüten', 'Tragetaschen', 'Papiertragetasche mit Kordel'] as $name) {
            $this->assertSame('bag', Guess::type($name), $name);
        }

        // German builds words from words: a paper bag is not a cotton one, a shipping bag is an envelope.
        $this->assertNotSame('bag', Guess::type('Baumwolltaschen'));
        $this->assertNotSame('bag', Guess::type('Versandtaschen'));

        $this->assertSame(['size' => 'small'], Guess::product('bag', 'Papiertragetaschen klein'));
        $this->assertSame(['size' => 'large'], Guess::product('bag', 'Papiertüten groß'));
        $this->assertSame(['size' => 'small'], Guess::product('bag', 'Tragetaschen S'));
        $this->assertSame(['size' => 'large'], Guess::product('bag', 'Tragetaschen L'));
        $this->assertSame([], Guess::product('bag', 'Papiertüten'));

        $this->assertSame(['size' => 'small'], Guess::option('bag', 'Klein (24 × 10 × 32 cm)'));
        $this->assertSame(['size' => 'large'], Guess::option('bag', 'Groß (32 × 12 × 41 cm)'));
        $this->assertSame(['color' => 'kraft'], Guess::option('bag', 'Braun'));
        $this->assertSame(['color' => 'kraft'], Guess::option('bag', 'Kraftpapier'));
        $this->assertSame(['color' => 'white'], Guess::option('bag', 'Weiß'));
        $this->assertSame(['color' => 'white'], Guess::option('bag', 'Weiß mit braunen Kordeln'));
        $this->assertSame([], Guess::option('bag', '250 Stück'));
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        $resolved = Spec::resolve(['name' => 'Papiertragetaschen'], [['name' => 'Groß (32 × 12 × 41 cm)'], ['name' => 'Weiß']]);

        $this->assertSame('bag', $resolved['type']);
        $this->assertSame('large', $resolved['spec']['size']);
        $this->assertSame('white', $resolved['spec']['color']);
        $this->assertContains($resolved['spec']['motif'], Bag::motifs());
        $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), 'resolved');

        foreach (['Papiertüten', 'Tragetaschen klein', 'Papiertragetaschen groß'] as $name) {
            $resolved = Spec::resolve(['name' => $name], [['name' => 'Kraftpapier braun'], ['name' => '500 Stück']]);

            $this->assertSame('bag', $resolved['type'], $name);
            $this->assertSame('kraft', $resolved['spec']['color'], $name);
            $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), $name);
        }
    }

    public function test_text_from_outside_cannot_break_out_of_an_attribute(): void
    {
        $hostile = 'A "B" <c> & \'d\'';
        $escaped = 'A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;';

        foreach (['label' => stiqon_mockup('bag', ['label' => $hostile]), 'class' => stiqon_mockup('bag', ['size' => 'large'], $hostile)] as $what => $markup) {
            $this->assertStringContainsString($escaped, $markup, $what);
            $this->assertStringNotContainsString('<c>', $markup, $what);
        }
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b[^"]*\blift\b/', stiqon_mockup('bag'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('bag', [], 'md:w-1/2'));
        $this->assertStringContainsString('viewBox="0 0 120 120"', stiqon_mockup('bag'));
    }

    /**
     * Where a handle's cord starts and ends and how high it rises: a cubic
     * curve from one end to the other.
     *
     * @return array{from: float, to: float, ends: float, top: float}
     */
    private function loop(string $path): array
    {
        $number = '(-?[\d.]+)';
        $this->assertSame(1, preg_match("/\\AM{$number} {$number}C{$number} {$number} {$number} {$number} {$number} {$number}\\z/", $path, $m), $path);

        [, $x0, $y0, , $y1, , $y2, $x3, $y3] = array_map('floatval', $m);

        return ['from' => $x0, 'to' => $x3, 'ends' => $y0, 'top' => 0.125 * $y0 + 0.375 * $y1 + 0.375 * $y2 + 0.125 * $y3];
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
