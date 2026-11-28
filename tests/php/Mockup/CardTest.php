<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Card;
use Stiqon\Shop\Mockup\Kinds;
use Stiqon\Shop\Mockup\Uid;

/**
 * Printed cards: four formats, each flat, on a stack or folded, with or without
 * shine and with one of four finishes. Every combination the shop can ask for
 * has to render, and the names of products and options have to lead to it.
 */
final class CardTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_every_format_flat_and_folded(): void
    {
        $this->assertGreaterThanOrEqual(4, count(Card::motifs()));
        $this->assertLessThanOrEqual(6, count(Card::motifs()));

        foreach (Card::motifs() as $motif) {
            foreach (array_keys(Card::FORMATS) as $format) {
                foreach ([false, true] as $fold) {
                    $svg = stiqon_mockup('card', compact('format', 'motif', 'fold'));

                    $this->assertWellFormed($svg, "{$motif} as {$format}".($fold ? ', folded' : ''));
                    $this->assertStringContainsString('data-motif-sample', $svg);
                    $this->assertStringContainsString('data-motif-image', $svg);
                    $this->assertSame(1, substr_count($svg, 'x-data="motifLayer"'), 'One printed side, printed once.');
                }
            }
        }
    }

    public function test_every_finish_and_switch_renders_in_every_format(): void
    {
        foreach (array_keys(Card::FORMATS) as $format) {
            foreach (Card::FINISHES as $finish) {
                foreach ([[], ['fold' => true], ['stack' => true], ['gloss' => true], ['fold' => true, 'stack' => true, 'gloss' => true]] as $switches) {
                    $spec = compact('format', 'finish') + $switches;
                    $svg = stiqon_mockup('card', $spec);

                    $this->assertWellFormed($svg, json_encode($spec));
                    $this->assertStringContainsString('data-motif-image', $svg);
                }
            }
        }
    }

    public function test_the_switches_and_finishes_change_the_drawing(): void
    {
        $plain = Card::fromArray([])->render();
        $gloss = Card::fromArray(['gloss' => true]);
        $fold = Card::fromArray(['fold' => true]);
        $gold = Card::fromArray(['finish' => 'gold']);
        $silver = Card::fromArray(['finish' => 'silver']);
        $soft = Card::fromArray(['finish' => 'softtouch']);

        $this->assertStringContainsString('url(#'.$gloss->uid.'-gloss)', $gloss->render());
        $this->assertStringContainsString('url(#'.$fold->uid.'-inside)', $fold->render());
        $this->assertStringContainsString('transform="matrix(', $fold->render());
        $this->assertSame(1, substr_count($plain, 'rotate('));
        $this->assertSame(3, substr_count(Card::fromArray(['stack' => true])->render(), 'rotate('), 'Two cards under the top one.');
        $this->assertStringContainsString('url(#'.$gold->uid.'-foil)', $gold->render());
        $this->assertStringContainsString('#f7e3a1', $gold->render());
        $this->assertStringContainsString('url(#'.$silver->uid.'-foil)', $silver->render());
        $this->assertStringContainsString('#f1f3f4', $silver->render());
        $this->assertStringContainsString('url(#'.$soft->uid.'-velvet)', $soft->render());
        $this->assertStringContainsString('url(#'.$soft->uid.'-grain)', $soft->render());

        foreach (['-gloss', '-inside', '-foil', '-velvet', '-grain'] as $part) {
            $this->assertStringNotContainsString($part, $plain);
        }
    }

    public function test_soft_touch_film_never_shines(): void
    {
        $card = new Card(gloss: true, finish: 'softtouch');

        $this->assertFalse($card->shines());
        $this->assertStringNotContainsString('-gloss', $card->render());
        $this->assertTrue((new Card(gloss: true, finish: 'gold'))->shines());
    }

    public function test_a_folded_card_stands_without_a_stack(): void
    {
        $card = new Card(fold: true, stack: true);

        $this->assertFalse($card->stack);
        $this->assertNotNull($card->tent);
        $this->assertStringNotContainsString('Stapel', $card->describe());
        $this->assertTrue((new Card(stack: true))->stack);
        $this->assertNull((new Card(stack: true))->tent);
    }

    public function test_every_drawing_keeps_inside_the_box(): void
    {
        foreach (array_keys(Card::FORMATS) as $format) {
            foreach ([false, true] as $stack) {
                $card = new Card(format: $format, stack: $stack);
                $w = $card->face['width'];
                $h = $card->face['height'];
                $turn = deg2rad($card->tilt);

                foreach ([[0, 0], [$w, 0], [$w, $h], [0, $h]] as [$x, $y]) {
                    // The corner as Card::transform() places it: centred, then turned about the middle.
                    $dx = $x - $w / 2;
                    $dy = $y - $h / 2;
                    $this->assertInBox(60 + $dx * cos($turn) - $dy * sin($turn), 60 + $dx * sin($turn) + $dy * cos($turn), "{$format} flat");
                }
            }

            $tent = (new Card(format: $format, fold: true))->tent;

            foreach (array_merge($tent['ridge'], $tent['front'], $tent['back']) as [$x, $y]) {
                $this->assertInBox($x, $y, "{$format} folded");
            }

            // The tent spans most of the box, as the other drawings do.
            $this->assertGreaterThan(95, $tent['back'][1][0] - $tent['front'][0][0], "{$format} folded");
        }
    }

    public function test_a_card_describes_itself_for_screen_readers(): void
    {
        $this->assertSame('Visitenkarte', Card::fromArray([])->describe());
        $this->assertSame('Postkarte DIN A6', Card::fromArray(['format' => 'postkarte'])->describe());
        $this->assertSame('Quadratische Karte', Card::fromArray(['format' => 'quadrat'])->describe());
        $this->assertSame('Karte DIN lang', Card::fromArray(['format' => 'lang'])->describe());
        $this->assertSame('Klappkarte, Postkarte DIN A6', Card::fromArray(['format' => 'postkarte', 'fold' => true])->describe());
        $this->assertSame('Visitenkarte, Stapel', Card::fromArray(['stack' => true])->describe());
        $this->assertSame('Visitenkarte mit Goldprägung', Card::fromArray(['finish' => 'gold'])->describe());
        $this->assertSame('Visitenkarte mit Silberprägung', Card::fromArray(['finish' => 'silver'])->describe());
        $this->assertSame('Karte DIN lang mit Soft-Touch-Folie, Stapel', Card::fromArray(['format' => 'lang', 'finish' => 'softtouch', 'stack' => true])->describe());
        $this->assertSame('Unsere Karte', Card::fromArray(['label' => 'Unsere Karte', 'finish' => 'gold'])->describe());

        $this->assertStringContainsString('aria-label="Klappkarte, Quadratische Karte"', stiqon_mockup('card', ['format' => 'quadrat', 'fold' => true]));
        $this->assertStringContainsString('aria-label="Ihr Logo &amp; mehr"', stiqon_mockup('card', ['label' => 'Ihr Logo & mehr']));
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
            'format' => [static fn () => new Card(format: 'a3'), 'Unknown card format [a3].'],
            'format from a spec' => [static fn () => Card::fromArray(['format' => 'Visitenkarte']), 'Unknown card format [Visitenkarte].'],
            'motif' => [static fn () => new Card(motif: 'gibt-es-nicht'), 'Unknown card motif [gibt-es-nicht].'],
            'sticker motif' => [static fn () => new Card(motif: 'siegel'), 'Unknown card motif [siegel].'],
            'flyer motif' => [static fn () => new Card(motif: 'fest'), 'Unknown card motif [fest].'],
            'motif outside the directory' => [static fn () => new Card(motif: '../mockup/card'), 'Unknown card motif [../mockup/card].'],
            'finish' => [static fn () => new Card(finish: 'matt'), 'Unknown card finish [matt].'],
            'finish from a spec' => [static fn () => Card::fromArray(['finish' => 'kupfer']), 'Unknown card finish [kupfer].'],
        ];
    }

    public function test_an_empty_spec_draws_the_first_format_with_the_first_motif(): void
    {
        $card = Card::fromArray([]);

        $this->assertSame(array_key_first(Card::form()['values']), $card->format);
        $this->assertSame(Card::motifs()[0], $card->motif);
        $this->assertSame((new Card())->motif, $card->motif);
        $this->assertSame('plain', $card->finish);
        $this->assertFalse($card->fold || $card->stack || $card->gloss);
    }

    public function test_keys_of_other_kinds_are_ignored_and_switches_are_cast(): void
    {
        Uid::reset();
        $own = Card::fromArray(['format' => 'quadrat', 'gloss' => true])->render();

        Uid::reset();
        $mixed = Card::fromArray([
            'format' => 'quadrat',
            'gloss' => 'ja',
            'shape' => 'dreieck',
            'material' => 'gold',
            'window' => true,
            'heavy' => true,
            'type' => 'mug',
        ])->render();

        $this->assertSame($own, $mixed);
        $this->assertFalse(Card::fromArray(['fold' => 0, 'stack' => ''])->stack);
        $this->assertTrue(Card::fromArray(['fold' => '1'])->fold);
    }

    public function test_ids_stay_unique_and_references_resolve_when_many_cards_share_a_page(): void
    {
        $page = stiqon_mockup('card', ['finish' => 'gold', 'gloss' => true])
            .stiqon_mockup('card', ['finish' => 'gold', 'gloss' => true, 'fold' => true])
            .stiqon_mockup('card', ['finish' => 'softtouch', 'stack' => true]);

        foreach (Card::motifs() as $motif) {
            foreach (array_keys(Card::FORMATS) as $format) {
                $page .= stiqon_mockup('card', compact('motif', 'format') + ['finish' => 'silver', 'gloss' => true, 'fold' => $format === 'quadrat']);
            }
        }

        $this->assertIdsResolve($page, 40);
    }

    public function test_every_id_belongs_to_its_drawing(): void
    {
        foreach ([['finish' => 'gold', 'gloss' => true], ['finish' => 'softtouch', 'fold' => true], ['stack' => true, 'finish' => 'silver']] as $spec) {
            $card = Card::fromArray($spec);
            preg_match_all('/\sid="([^"]+)"/', $card->render(), $ids);

            $this->assertNotEmpty($ids[1]);

            foreach ($ids[1] as $id) {
                $this->assertStringStartsWith($card->uid.'-', $id);
            }
        }
    }

    public function test_the_drawing_itself_brings_no_lettering_styles_or_scripts(): void
    {
        $svg = stiqon_mockup('card', ['finish' => 'gold', 'gloss' => true, 'fold' => true, 'motif' => 'kanzlei']);
        // Lettering belongs to the sample motif only.
        $drawing = preg_replace('/<svg[^>]*data-motif-sample[^>]*>.*?<\/svg>/s', '', $svg);

        $this->assertStringNotContainsString('<text', $drawing);
        $this->assertStringNotContainsString('<style', $svg);
        $this->assertStringNotContainsString('<script', $svg);
        $this->assertSame(1, substr_count($svg, ' class="'), 'Only the root carries classes.');
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b[^"]*\blift\b/', stiqon_mockup('card'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('card', [], 'md:w-[66%]'));
    }

    public function test_keys_form_and_motifs_agree(): void
    {
        $keys = Card::keys();
        $form = Card::form();

        $this->assertSame('Karte', Card::label());
        $this->assertSame('format', $form['key']);
        $this->assertSame('Format', $form['label']);
        $this->assertArrayHasKey($form['key'], $keys);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(['visitenkarte', 'postkarte', 'quadrat', 'lang'], array_keys($form['values']));
        $this->assertSame('Visitenkarte 85 × 55 mm', $form['values']['visitenkarte']);
        $this->assertSame(Card::motifs(), $keys['motif']);
        $this->assertSame(['plain', 'softtouch', 'gold', 'silver'], $keys['finish']);

        foreach (['fold', 'stack', 'gloss'] as $switch) {
            $this->assertArrayHasKey($switch, $keys);
            $this->assertNull($keys[$switch]);
        }
    }

    public function test_the_names_only_say_what_the_drawing_knows(): void
    {
        $keys = Card::keys();
        $names = Card::names();

        $this->assertSame([], array_diff(array_keys($names['form']), $keys['format']));

        foreach ($names['options'] as $key => $patterns) {
            $this->assertIsArray($keys[$key] ?? null, $key);
            $this->assertSame([], array_diff(array_keys($patterns), $keys[$key]), $key);
        }

        foreach (array_keys($names['switches']) as $key) {
            $this->assertArrayHasKey($key, $keys);
            $this->assertNull($keys[$key], $key);
        }
    }

    public function test_product_names_say_it_is_a_card_and_which_format(): void
    {
        $this->assertSame(Card::class, Kinds::of('card'));

        foreach ([
            'Visitenkarten' => 'visitenkarte',
            'Visitenkarten mit Folienkaschierung' => 'visitenkarte',
            'Visitenkarten mit Goldprägung' => 'visitenkarte',
            'Klappvisitenkarten' => 'visitenkarte',
            'Postkarten' => 'postkarte',
            'Quadratische Karten' => 'quadrat',
            'Klappkarten' => 'postkarte',
            'Einladungskarten DIN lang' => 'lang',
            'Grußkarten' => 'postkarte',
            'Karten A6' => 'postkarte',
        ] as $name => $format) {
            $this->assertSame('card', Guess::type($name), $name);
            $this->assertSame(['format' => $format], Guess::product('card', $name), $name);
        }
    }

    public function test_option_names_say_the_finish_and_the_surface(): void
    {
        $this->assertSame(['gloss' => false], Guess::option('card', '300 g Bilderdruck matt'));
        $this->assertSame(['gloss' => true], Guess::option('card', '300 g Bilderdruck glänzend'));
        $this->assertSame(['finish' => 'plain', 'gloss' => false], Guess::option('card', 'Folienkaschierung matt'));
        $this->assertSame(['finish' => 'softtouch'], Guess::option('card', 'Soft-Touch-Folie'));
        $this->assertSame(['finish' => 'gold'], Guess::option('card', 'Heißfolienprägung Gold'));
        $this->assertSame(['finish' => 'silver'], Guess::option('card', 'Heißfolienprägung Silber'));
        $this->assertSame(['finish' => 'plain'], Guess::option('card', 'ohne Veredelung'));
        $this->assertSame(['fold' => true], Guess::option('card', 'Klappkarte'));
        $this->assertSame([], Guess::option('card', '4/4-farbig'));
    }

    public function test_a_product_and_its_options_resolve_to_a_drawing(): void
    {
        $resolved = Spec::resolve(['name' => 'Visitenkarten mit Goldprägung'], [['name' => 'Heißfolienprägung Gold'], ['name' => '400 g Bilderdruck matt']]);

        $this->assertSame('card', $resolved['type']);
        $this->assertSame('visitenkarte', $resolved['spec']['format']);
        $this->assertSame('gold', $resolved['spec']['finish']);
        $this->assertFalse($resolved['spec']['gloss']);
        $this->assertContains($resolved['spec']['motif'], Card::motifs());
        $this->assertWellFormed(Kinds::draw('card', $resolved['spec'] + ['label' => 'Visitenkarten mit Goldprägung']), 'resolved product');
    }

    private function assertInBox(float $x, float $y, string $what): void
    {
        $this->assertGreaterThanOrEqual(10.0, round($x, 2), "{$what}: x");
        $this->assertLessThanOrEqual(110.0, round($x, 2), "{$what}: x");
        $this->assertGreaterThanOrEqual(10.0, round($y, 2), "{$what}: y");
        $this->assertLessThanOrEqual(110.0, round($y, 2), "{$what}: y");
    }

    private function assertIdsResolve(string $page, int $atLeast): void
    {
        preg_match_all('/\sid="([^"]+)"/', $page, $ids);
        preg_match_all('/url\(#([^)]+)\)|href="#([^"]+)"/', $page, $references);

        $referenced = array_unique(array_filter(array_merge($references[1], $references[2])));

        $this->assertGreaterThan($atLeast, count($ids[1]));
        $this->assertSame([], array_keys(array_filter(array_count_values($ids[1]), static fn (int $count): bool => $count > 1)), 'Ids repeat.');
        $this->assertNotEmpty($referenced);
        $this->assertSame([], array_values(array_diff($referenced, $ids[1])), 'References point nowhere.');
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
