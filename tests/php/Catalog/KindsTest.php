<?php

namespace Stiqon\Shop\Tests\Catalog;

use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Kind;
use Stiqon\Shop\Mockup\Kinds;

/**
 * Which kind of drawing a product gets, read from its name and its categories.
 * German builds words from words, so the order in Mockup\Kinds decides – these
 * cases hold it in place.
 */
final class KindsTest extends TestCase
{
    public function test_every_kind_listed_has_its_drawing(): void
    {
        $this->assertSame(
            ['sticker', 'calendar', 'folder', 'card', 'leaflet', 'flyer', 'envelope', 'letter', 'notepad', 'brochure', 'standee', 'poster', 'rollup', 'banner', 'box', 'bag', 'shirt', 'tote', 'mug', 'bottle', 'coaster'],
            Kinds::types(),
        );

        foreach (Kinds::types() as $type) {
            $kind = Kinds::of($type);
            $form = $kind::form();

            $this->assertTrue(is_subclass_of($kind, Kind::class), $type);
            $this->assertArrayHasKey($form['key'], $kind::keys(), "{$type}: its form is not one of its keys.");
            $this->assertSame(array_keys($form['values']), array_map('strval', $kind::keys()[$form['key']]), "{$type}: its form lists other values than its keys.");
            $this->assertSame($kind::motifs(), $kind::keys()['motif'] ?? null, "{$type}: its motifs are not its keys' motifs.");
            $this->assertNotSame('Form', $form['label'], "{$type}: the label \"Form\" belongs to stickers in the administration.");
            $this->assertStringContainsString('data-mockup="'.$type.'"', Kinds::draw($type, []), "{$type} does not draw itself by default.");
        }
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function names(): iterable
    {
        // Every product of the example assortment.
        $assortment = require dirname(__DIR__, 3).'/tools/dev/example-assortment.php';

        foreach ($assortment['products'] as $product) {
            yield $product['name'] => [$product['name'], $product['drawing']['type']];
        }

        // Words that contain other kinds' words.
        yield 'a stand for posters' => ['Plakatständer', 'standee'];
        yield 'a calendar of postcards' => ['Postkartenkalender', 'calendar'];
        yield 'a paper carrier bag' => ['Papiertragetasche', 'bag'];
        yield 'a cotton carrier bag' => ['Baumwolltragetasche', 'tote'];
        yield 'a label for bottles' => ['Flaschenetiketten', 'sticker'];
        yield 'a folded card' => ['Klappkarte, gerillt und gefalzt', 'card'];
        yield 'a folder with a card pocket' => ['Präsentationsmappe mit Visitenkartentasche', 'folder'];
        yield 'a folded flyer' => ['Falzflyer DIN lang', 'leaflet'];
        yield 'a roll-up is no banner' => ['Roll-Up Banner Premium', 'rollup'];
        yield 'a wall planner' => ['Wandplaner', 'calendar'];
        yield 'an envelope' => ['Kuvertierhüllen DIN lang', 'envelope'];
        yield 'a block for notes' => ['Haftnotizen', 'notepad'];
        yield 'a shipping box' => ['Faltschachteln', 'box'];
        yield 'a hoodie' => ['Sweatshirts', 'shirt'];
        yield 'an enamel mug' => ['Emaillebecher', 'mug'];
        yield 'a beer mat' => ['Glasuntersetzer', 'coaster'];
        // Cotton makes shirts as well as bags.
        yield 'a shirt of cotton' => ['T-Shirts aus Baumwolle', 'shirt'];
        yield 'a hoodie of cotton' => ['Baumwoll-Hoodie mit Kängurubeutel', 'shirt'];
        yield 'a bag of cloth' => ['Stofftaschen', 'tote'];
        yield 'a bag of jute' => ['Jutebeutel', 'tote'];
        yield 'a gym bag' => ['Turnbeutel', 'tote'];
        yield 'a gift box' => ['Geschenkboxen', 'box'];
    }

    /**
     * @dataProvider names
     */
    public function test_a_name_says_what_kind_of_product_it_is(string $name, string $type): void
    {
        $this->assertSame($type, Guess::type($name));
    }

    public function test_the_products_own_name_comes_before_its_categories(): void
    {
        // Mugs filed under "Werbeartikel", labels filed under "Sticker" …
        $this->assertSame('mug', Guess::type('Tassen', 'Werbeartikel'));
        $this->assertSame('sticker', Guess::type('Rund, 5 cm', 'Sticker'));
        // … and envelopes filed under "Briefpapier" stay envelopes.
        $this->assertSame('envelope', Guess::type('Briefumschläge DIN lang', 'Briefpapier'));
        // A name that says nothing leaves it to the category.
        $this->assertSame('letter', Guess::type('Geschäftsausstattung', 'Briefpapier'));
        $this->assertNull(Guess::type('Geschenkidee', 'Werbetechnik'));
        // Boxer shorts are no box.
        $this->assertNull(Guess::type('Boxershorts'));
    }

    public function test_options_of_the_example_assortment_change_the_drawing(): void
    {
        $spec = static fn (string $product, string ...$options): array => Spec::resolve(
            ['name' => $product],
            array_map(static fn (string $option): array => ['name' => $option], $options),
        )['spec'];

        $this->assertFalse($spec('Visitenkarten', '300 g Bilderdruck matt')['gloss']);
        $this->assertTrue($spec('Visitenkarten', '300 g Bilderdruck glänzend')['gloss']);
        $this->assertTrue($spec('Faltblatt mit Wickelfalz', '250 g Bilderdruck')['heavy']);
        $this->assertSame('a1', $spec('Plakate', 'DIN A1')['format']);
        $this->assertSame('black', $spec('T-Shirts', 'Schwarz', 'L')['color']);
        $this->assertSame('white', $spec('Versandkartons', 'Weiß')['color']);
        $this->assertSame('kraft', $spec('Papiertragetaschen', 'Braun')['color']);
        $this->assertTrue($spec('Briefumschläge DIN lang', 'mit Fenster')['window']);
        $this->assertFalse($spec('Briefumschläge DIN lang', 'ohne Fenster')['window']);
        $this->assertSame('100x200', $spec('Roll-ups', '100 × 200 cm')['format']);
        $this->assertSame('square', $spec('Bierdeckel', 'Eckig 93 × 93 mm')['shape']);
    }
}
