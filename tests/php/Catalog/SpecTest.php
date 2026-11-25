<?php

namespace Stiqon\Shop\Tests\Catalog;

use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Flyer;
use Stiqon\Shop\Mockup\Sticker;

/**
 * Products come from the owner or from a supplier's plugin and say nothing about
 * how to draw them. The drawing is worked out from their names, and from what is
 * stored once somebody sets it by hand.
 */
final class SpecTest extends TestCase
{
    /**
     * @dataProvider namedProducts
     *
     * @param  list<string>  $options
     * @param  array<string, string|bool>  $expected
     */
    public function test_names_alone_say_how_to_draw_a_product(string $name, array $options, string $type, array $expected): void
    {
        $resolved = Spec::resolve(['name' => $name], array_map(static fn (string $option): array => ['name' => $option], $options));

        $this->assertNotNull($resolved);
        $this->assertSame($type, $resolved['type']);

        foreach ($expected as $key => $value) {
            $this->assertSame($value, $resolved['spec'][$key] ?? null, "{$name}: {$key}");
        }
    }

    /**
     * @return array<string, array{string, list<string>, string, array<string, string|bool>}>
     */
    public static function namedProducts(): array
    {
        return [
            'round' => ['Runde Sticker', ['Weißes Vinyl', '5 cm', '100 Stück'], 'sticker', ['shape' => 'circle', 'material' => 'vinyl']],
            'square on holographic film' => ['Quadratische Sticker', ['Holografische Folie'], 'sticker', ['shape' => 'square', 'material' => 'holo']],
            'kraft paper does not shine' => ['Ovale Aufkleber', ['Kraftpapier'], 'sticker', ['shape' => 'oval', 'material' => 'kraft', 'finish' => 'matte']],
            'a finish named after the material' => ['Runde Sticker', ['Transparente Folie', 'Matt'], 'sticker', ['material' => 'clear', 'finish' => 'matte']],
            'rounded corners are not round' => ['Rechteckige Sticker mit abgerundeten Ecken', [], 'sticker', ['shape' => 'rect']],
            'contour cut' => ['Sticker mit Konturschnitt', ['Vinyl'], 'sticker', ['shape' => 'contour']],
            'english names' => ['Round stickers', ['Clear film, glossy'], 'sticker', ['shape' => 'circle', 'material' => 'clear', 'finish' => 'gloss']],
            'labels' => ['Etiketten auf Rolle, rund', [], 'sticker', ['shape' => 'circle']],
            'flyer a6' => ['Flyer DIN A6', ['135 g Bilderdruck', '250 Stück'], 'flyer', ['format' => 'a6', 'heavy' => false]],
            'flyer long, heavy and glossy' => ['Flyer DIN lang', ['300 g Bilderdruck glänzend'], 'flyer', ['format' => 'lang', 'heavy' => true, 'gloss' => true]],
            'a quantity is not a paper weight' => ['Flyer A5', ['250 Stück'], 'flyer', ['format' => 'a5']],
            'square flyer' => ['Flugblatt quadratisch', ['250g matt'], 'flyer', ['format' => 'quadrat', 'heavy' => true, 'gloss' => false]],
        ];
    }

    public function test_a_quantity_says_nothing_about_the_paper(): void
    {
        $resolved = Spec::resolve(['name' => 'Flyer A5'], [['name' => '250 Stück']]);

        $this->assertArrayNotHasKey('heavy', $resolved['spec']);
    }

    public function test_the_category_can_say_what_the_name_does_not(): void
    {
        $this->assertNull(Spec::resolve(['name' => 'Rund, 5 cm']));
        $this->assertSame('sticker', Spec::resolve(['name' => 'Rund, 5 cm', 'categories' => ['Sticker']])['type']);
        $this->assertSame('flyer', Spec::resolve(['name' => 'DIN A5', 'categories' => ['Drucksachen', 'Flyer']])['type']);
    }

    public function test_what_is_stored_wins_over_what_the_names_suggest(): void
    {
        $resolved = Spec::resolve(
            ['name' => 'Runde Sticker', 'stored' => ['type' => 'sticker', 'shape' => 'oval', 'motif' => 'danke']],
            [
                ['name' => 'Weißes Vinyl', 'stored' => ['material' => 'kraft', 'finish' => 'gloss']],
                ['name' => 'Glänzend', 'stored' => ['peel' => '1']],
            ],
        );

        $this->assertSame(
            ['shape' => 'oval', 'motif' => 'danke', 'material' => 'kraft', 'finish' => 'gloss', 'peel' => true],
            $resolved['spec'],
        );
    }

    public function test_a_later_option_wins_over_an_earlier_one(): void
    {
        $resolved = Spec::resolve(['name' => 'Runde Sticker'], [['name' => 'Kraftpapier'], ['name' => 'Glänzend']]);

        $this->assertSame('kraft', $resolved['spec']['material']);
        $this->assertSame('gloss', $resolved['spec']['finish']);
    }

    public function test_stored_values_the_drawings_do_not_know_are_ignored(): void
    {
        $resolved = Spec::resolve(
            ['name' => 'Runde Sticker', 'stored' => ['type' => 'tasse', 'shape' => 'dreieck', 'motif' => '../mockup/flyer', 'material' => ['holo'], 'onload' => 'x']],
            [['name' => 'Holografische Folie', 'stored' => ['material' => 'gold', 'finish' => 7, 'peel' => 'vielleicht']]],
        );

        // Everything falls back to what the names say.
        $this->assertSame('sticker', $resolved['type']);
        $this->assertSame('circle', $resolved['spec']['shape']);
        $this->assertSame('holo', $resolved['spec']['material']);
        $this->assertContains($resolved['spec']['motif'], Sticker::motifs());
        $this->assertSame(['shape', 'material', 'motif'], array_keys($resolved['spec']));
    }

    public function test_a_product_without_a_motif_always_gets_the_same_one(): void
    {
        $first = Spec::resolve(['name' => 'Runde Sticker'])['spec']['motif'];

        $this->assertSame($first, Spec::resolve(['name' => 'Runde Sticker'])['spec']['motif']);
        $this->assertSame($first, Spec::resolve(['name' => '  RUNDE STICKER '])['spec']['motif']);
        $this->assertContains(Guess::motif('sticker', 'Sticker mit Konturschnitt', 'contour'), ['sprechblase', 'stern']);
        $this->assertContains(Guess::motif('flyer', 'Flyer DIN A5'), Flyer::motifs());

        $distinct = array_unique(array_map(
            static fn (string $name): string => Guess::motif('sticker', $name),
            ['Runde Sticker', 'Quadratische Sticker', 'Rechteckige Sticker', 'Ovale Sticker', 'Etiketten', 'Aufkleber 5 cm', 'Sticker 7 cm', 'Logo-Sticker'],
        ));

        $this->assertGreaterThan(2, count($distinct), 'Different products should not all show the same sample.');
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        $names = ['Runde Sticker', 'Sticker mit Konturschnitt', 'Etiketten', 'Aufkleber', 'Flyer', 'Flyer DIN lang', 'Handzettel A6', 'Label square'];
        $options = ['Weißes Vinyl', 'Transparente Folie matt', 'Holo', 'Kraftpapier', '135 g', '350 g glänzend', '100 Stück', 'XL'];

        foreach ($names as $name) {
            foreach ($options as $option) {
                $resolved = Spec::resolve(['name' => $name], [['name' => $option]]);
                $svg = $resolved['type'] === 'sticker' ? stiqon_sticker($resolved['spec']) : stiqon_flyer($resolved['spec']);

                $this->assertStringStartsWith('<svg', ltrim($svg), "{$name} / {$option}");
            }
        }
    }
}
