<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Brochure;
use Stiqon\Shop\Mockup\Kinds;
use Stiqon\Shop\Mockup\Uid;
use Stiqon\Shop\Template;

/**
 * Brochures: four formats, stapled or glued, matte or glossy, with a flyer
 * motif on the cover. Every combination the shop can ask for has to render,
 * the visitor's motif has to reach the cover, and the names of products and
 * options have to lead to the right drawing.
 */
final class BrochureTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_the_shop_knows_the_kind(): void
    {
        $this->assertTrue(Kinds::has('brochure'));
        $this->assertSame(Brochure::class, Kinds::of('brochure'));
        $this->assertSame('Broschüre', Kinds::labels()['brochure']);
    }

    public function test_every_motif_renders_in_every_format(): void
    {
        $this->assertGreaterThanOrEqual(5, count(Brochure::motifs()));

        foreach (Brochure::motifs() as $motif) {
            foreach (array_keys(Brochure::form()['values']) as $format) {
                $svg = stiqon_mockup('brochure', compact('format', 'motif'));

                $this->assertWellFormed($svg, "{$motif} as {$format}");
                $this->assertStringContainsString('data-motif-sample', $svg);
                $this->assertStringContainsString('data-motif-image', $svg);
            }
        }
    }

    public function test_every_binding_renders_in_every_format_matte_and_glossy(): void
    {
        foreach (array_keys(Brochure::form()['values']) as $format) {
            foreach (Brochure::keys()['binding'] as $binding) {
                foreach ([false, true] as $gloss) {
                    $spec = compact('format', 'binding', 'gloss');
                    $svg = stiqon_mockup('brochure', $spec);

                    $this->assertWellFormed($svg, json_encode($spec));
                    $this->assertStringContainsString('data-motif-image', $svg);
                }
            }
        }
    }

    public function test_the_binding_shows_on_the_spine(): void
    {
        $stapled = new Brochure(binding: 'staple');
        $glued = new Brochure(binding: 'perfect');

        $this->assertCount(2, $stapled->staples(), 'Two staples in the fold.');
        $this->assertSame([], $glued->staples());
        $this->assertGreaterThan($stapled->edge['x'], $glued->edge['x'], 'A glued brochure has the thicker spine.');
        $this->assertGreaterThan($stapled->edge['y'], $glued->edge['y'], 'A glued brochure has the thicker page block.');

        $this->assertStringContainsString('url(#'.$stapled->uid.'-wire)', $stapled->render());
        $this->assertStringNotContainsString('-wire', $glued->render());

        foreach ($stapled->staples() as $staple) {
            // On the spine, between the cover's edge and the brochure's underside.
            $this->assertGreaterThan($stapled->cover['x'] - $stapled->edge['x'], $staple['x']);
            $this->assertLessThan($stapled->cover['x'], $staple['x']);
            $this->assertGreaterThan($stapled->cover['y'], $staple['y']);
            $this->assertLessThan($stapled->cover['y'] + $stapled->cover['height'], $staple['y']);
        }
    }

    public function test_gloss_shows_as_a_band_of_light(): void
    {
        $glossy = new Brochure(gloss: true);
        $matte = new Brochure();

        $this->assertStringContainsString('url(#'.$glossy->uid.'-gloss)', $glossy->render());
        $this->assertStringNotContainsString('-gloss', $matte->render());
    }

    public function test_the_visitors_motif_reaches_the_cover_and_runs_round_the_spine(): void
    {
        $brochure = new Brochure(format: 'lang', motif: 'prozent', binding: 'perfect');
        $svg = $brochure->render();

        $this->assertSame(2, substr_count($svg, 'x-data="motifLayer"'), 'Cover and spine are printed.');
        $this->assertSame(2, substr_count($svg, 'data-motif-image'));
        $this->assertStringContainsString('transform="'.$brochure->spineTransform().'" clip-path="url(#'.$brochure->uid.'-spine)"', $svg);

        // The cover's motif fills the cover; the spine takes the cover's edge, laid over the spine.
        $cover = $brochure->cover;
        $this->assertStringContainsString('<image x="'.$cover['x'].'" y="'.$cover['y'].'" width="'.$cover['width'].'" height="'.$cover['height'].'" preserveAspectRatio="xMidYMid slice"', $svg);

        [$a, $b, , , $e, $f] = array_map('floatval', explode(' ', trim($brochure->spineTransform(), 'matrix()')));
        $strip = $brochure->spineStrip();
        $fold = [$strip['x'], $strip['y']];
        $inner = [$strip['x'] + $strip['width'], $strip['y']];

        // The strip's edge at the fold stays where it is; its inner edge lands on the spine's outer edge.
        $this->assertEqualsWithDelta($fold, [$a * $fold[0] + $e, $b * $fold[0] + $fold[1] + $f], 0.02);
        $this->assertEqualsWithDelta(
            [$cover['x'] - $brochure->edge['x'], $cover['y'] + $brochure->edge['y']],
            [$a * $inner[0] + $e, $b * $inner[0] + $inner[1] + $f],
            0.02,
        );
    }

    public function test_a_brochure_describes_itself_for_screen_readers(): void
    {
        $this->assertSame('Broschüre DIN A5, Rückstichheftung', (new Brochure())->describe());
        $this->assertSame('Broschüre DIN A4, Klebebindung', (new Brochure(format: 'a4', binding: 'perfect'))->describe());
        $this->assertSame('Broschüre DIN lang, Rückstichheftung', (new Brochure(format: 'lang'))->describe());
        $this->assertSame('Quadratische Broschüre, Klebebindung', (new Brochure(format: 'quadrat', binding: 'perfect'))->describe());
        $this->assertSame('Katalog 2027', (new Brochure(label: 'Katalog 2027'))->describe());

        $this->assertStringContainsString('aria-label="Broschüre DIN lang, Klebebindung"', stiqon_mockup('brochure', ['format' => 'lang', 'binding' => 'perfect']));
        $this->assertStringContainsString('aria-label="Ihr Heft &amp; mehr"', stiqon_mockup('brochure', ['label' => 'Ihr Heft & mehr']));
    }

    /**
     * @dataProvider unknownValues
     */
    public function test_unknown_values_are_rejected(callable $build): void
    {
        $this->expectException(InvalidArgumentException::class);

        $build();
    }

    /**
     * @return array<string, array{callable}>
     */
    public static function unknownValues(): array
    {
        return [
            'format' => [static fn () => new Brochure(format: 'a3')],
            'format of a flyer' => [static fn () => new Brochure(format: 'a6')],
            'motif' => [static fn () => new Brochure(motif: 'gibt-es-nicht')],
            'sticker motif' => [static fn () => new Brochure(motif: 'siegel')],
            'motif outside the directory' => [static fn () => new Brochure(motif: '../flyer/fest')],
            'binding' => [static fn () => new Brochure(binding: 'spirale')],
            'format from a spec' => [static fn () => Brochure::fromArray(['format' => 'A5'])],
            'motif from a spec' => [static fn () => Brochure::fromArray(['motif' => 'wellen'])],
            'binding from a spec' => [static fn () => Brochure::fromArray(['binding' => 'ring'])],
        ];
    }

    public function test_an_empty_spec_draws_the_first_of_everything(): void
    {
        $brochure = Brochure::fromArray([]);

        $this->assertSame(array_key_first(Brochure::form()['values']), $brochure->format);
        $this->assertSame('a5', $brochure->format);
        $this->assertSame(Brochure::motifs()[0], $brochure->motif);
        $this->assertSame(Brochure::keys()['binding'][0], $brochure->binding);
        $this->assertFalse($brochure->gloss);
        $this->assertNull($brochure->label);
    }

    public function test_keys_of_other_kinds_are_ignored(): void
    {
        $brochure = Brochure::fromArray([
            'type' => 'sticker', 'shape' => 'circle', 'material' => 'gold', 'fold' => 'wickel',
            'stack' => true, 'heavy' => true, 'finish' => 'softtouch', 'format' => 'quadrat', 'binding' => 'perfect',
        ]);

        $this->assertSame('quadrat', $brochure->format);
        $this->assertSame('perfect', $brochure->binding);
        $this->assertSame(Brochure::motifs()[0], $brochure->motif);
        $this->assertFalse($brochure->gloss);
        $this->assertWellFormed($brochure->render(), 'a spec with foreign keys');
    }

    public function test_the_gloss_switch_is_read_as_true_or_false(): void
    {
        $this->assertTrue(Brochure::fromArray(['gloss' => '1'])->gloss);
        $this->assertTrue(Brochure::fromArray(['gloss' => 1])->gloss);
        $this->assertFalse(Brochure::fromArray(['gloss' => ''])->gloss);
        $this->assertFalse(Brochure::fromArray(['gloss' => 0])->gloss);
    }

    public function test_ids_stay_unique_and_references_resolve_when_drawings_share_a_page(): void
    {
        $drawings = [
            Brochure::fromArray(['format' => 'a4', 'binding' => 'perfect', 'gloss' => true]),
            Brochure::fromArray(['format' => 'lang', 'motif' => 'fest']),
            Brochure::fromArray(['format' => 'quadrat', 'binding' => 'staple', 'gloss' => true]),
        ];
        $page = implode('', array_map(static fn (Brochure $brochure): string => $brochure->render(), $drawings));

        preg_match_all('/\sid="([^"]+)"/', $page, $ids);
        preg_match_all('/url\(#([^)]+)\)|href="#([^"]+)"/', $page, $references);

        $referenced = array_unique(array_filter(array_merge($references[1], $references[2])));

        $this->assertGreaterThan(10, count($ids[1]));
        $this->assertSame([], array_keys(array_filter(array_count_values($ids[1]), static fn (int $count): bool => $count > 1)), 'Ids repeat.');
        $this->assertNotEmpty($referenced);
        $this->assertSame([], array_values(array_diff($referenced, $ids[1])), 'References point nowhere.');

        foreach ($drawings as $brochure) {
            preg_match_all('/\sid="([^"]+)"/', $brochure->render(), $own);

            foreach ($own[1] as $id) {
                $this->assertStringStartsWith($brochure->uid.'-', $id);
            }
        }
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Brochure::keys();
        $form = Brochure::form();

        $this->assertSame('format', $form['key']);
        $this->assertSame('Format', $form['label']);
        $this->assertSame(['a5' => 'DIN A5', 'a4' => 'DIN A4', 'lang' => 'DIN lang', 'quadrat' => 'Quadratisch'], $form['values']);
        $this->assertArrayHasKey($form['key'], $keys);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Brochure::motifs(), $keys['motif']);
        $this->assertSame(['staple', 'perfect'], $keys['binding']);
        $this->assertArrayHasKey('gloss', $keys);
        $this->assertNull($keys['gloss']);
        $this->assertSame(['format', 'motif', 'binding', 'gloss'], array_keys($keys));

        foreach (Brochure::motifs() as $motif) {
            $this->assertTrue(Template::exists('motif/flyer/'.$motif), $motif);
        }

        foreach (array_keys(Brochure::names()['form']) as $value) {
            $this->assertContains($value, $keys[$form['key']]);
        }

        foreach (Brochure::names()['options'] as $key => $patterns) {
            $this->assertSame([], array_diff(array_keys($patterns), $keys[$key]), $key);
        }

        foreach (array_keys(Brochure::names()['switches']) as $key) {
            $this->assertNull($keys[$key]);
        }
    }

    public function test_every_drawing_keeps_inside_the_box(): void
    {
        foreach (array_keys(Brochure::form()['values']) as $format) {
            foreach (Brochure::keys()['binding'] as $binding) {
                $brochure = new Brochure(format: $format, binding: $binding);
                ['x' => $x, 'y' => $y, 'width' => $w, 'height' => $h] = $brochure->cover;
                ['x' => $dx, 'y' => $dy] = $brochure->edge;
                $turn = deg2rad($brochure->tilt);

                // The cover's corners and the underside's, turned about the middle as the template turns them.
                foreach ([[$x, $y], [$x + $w, $y], [$x + $w, $y + $h], [$x, $y + $h], [$x - $dx, $y + $dy], [$x - $dx, $y + $h + $dy], [$x + $w - $dx, $y + $h + $dy]] as [$px, $py]) {
                    $tx = 60 + ($px - 60) * cos($turn) - ($py - 60) * sin($turn);
                    $ty = 60 + ($px - 60) * sin($turn) + ($py - 60) * cos($turn);

                    $this->assertGreaterThanOrEqual(9.0, $tx, "{$format}/{$binding}");
                    $this->assertLessThanOrEqual(111.0, $tx, "{$format}/{$binding}");
                    $this->assertGreaterThanOrEqual(9.0, $ty, "{$format}/{$binding}");
                    $this->assertLessThanOrEqual(111.0, $ty, "{$format}/{$binding}");
                }

                // Large enough to sit beside a sticker or a flyer in one grid.
                $this->assertGreaterThan(80.0, max($w, $h) + $dy, "{$format}/{$binding}");
            }
        }
    }

    /**
     * @dataProvider productNames
     */
    public function test_product_names_say_it_is_a_brochure(string $name): void
    {
        $this->assertSame('brochure', Guess::type($name));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function productNames(): array
    {
        return [
            'stapled' => ['Broschüren mit Rückstichheftung'],
            'glued' => ['Broschüren mit Klebebindung'],
            'catalogues' => ['Kataloge'],
            'magazine' => ['Magazin'],
            'singular' => ['Broschüre DIN A5'],
        ];
    }

    public function test_names_say_the_format(): void
    {
        $this->assertSame(['format' => 'a5'], Guess::product('brochure', 'Broschüre DIN A5'));
        $this->assertSame(['format' => 'a4'], Guess::product('brochure', 'Katalog A4'));
        $this->assertSame(['format' => 'lang'], Guess::product('brochure', 'Broschüre DIN lang'));
        $this->assertSame(['format' => 'quadrat'], Guess::product('brochure', 'Quadratische Broschüre'));
        $this->assertSame([], Guess::product('brochure', 'Broschüren mit Rückstichheftung'));

        $this->assertSame(['format' => 'a5'], Guess::option('brochure', 'DIN A5'));
        $this->assertSame(['format' => 'a4'], Guess::option('brochure', 'DIN A4'));
        $this->assertSame(['format' => 'lang'], Guess::option('brochure', 'DIN lang'));
    }

    public function test_option_names_say_the_binding_and_the_finish(): void
    {
        $this->assertSame(['binding' => 'staple'], Guess::option('brochure', 'Rückstichheftung'));
        $this->assertSame(['binding' => 'staple'], Guess::option('brochure', 'Drahtheftung'));
        $this->assertSame(['binding' => 'staple'], Guess::option('brochure', 'Klammerheftung'));
        $this->assertSame(['binding' => 'perfect'], Guess::option('brochure', 'Klebebindung'));
        $this->assertSame(['binding' => 'perfect'], Guess::option('brochure', 'PUR-Klebebindung'));
        $this->assertSame(['binding' => 'perfect'], Guess::option('brochure', 'Softcover'));

        $this->assertSame(['gloss' => true], Guess::option('brochure', '135 g Bilderdruck glänzend'));
        $this->assertSame(['gloss' => false], Guess::option('brochure', 'Umschlag matt'));

        // A brochure has no stiffer sheet to draw: its paper's weight says nothing here.
        $this->assertSame([], Guess::option('brochure', '300 g Bilderdruck'));
        $this->assertSame([], Guess::option('brochure', '16 Seiten'));
    }

    public function test_a_product_resolves_to_a_drawing(): void
    {
        $resolved = Spec::resolve(['name' => 'Broschüren mit Rückstichheftung'], [['name' => 'DIN A4'], ['name' => '16 Seiten'], ['name' => '100']]);

        $this->assertSame('brochure', $resolved['type']);
        $this->assertSame('a4', $resolved['spec']['format']);
        $this->assertContains($resolved['spec']['motif'], Brochure::motifs());
        $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), 'resolved');

        // What the shop stores on the product wins, as for every kind.
        $stored = Spec::resolve(
            ['name' => 'Broschüren mit Klebebindung', 'stored' => ['type' => 'brochure', 'binding' => 'perfect', 'motif' => 'karte']],
            [['name' => 'DIN lang'], ['name' => 'Glänzend']],
        );

        // The same values in whatever order the sources added them (the binding is also in the product's name).
        $this->assertEquals(['motif' => 'karte', 'binding' => 'perfect', 'format' => 'lang', 'gloss' => true], $stored['spec']);
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('brochure'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('brochure', [], 'md:w-[60%]'));
        $this->assertMatchesRegularExpression('/class="[^"]*\blift\b/', stiqon_mockup('brochure'));
    }

    public function test_the_drawing_keeps_to_the_house_rules(): void
    {
        $svg = stiqon_mockup('brochure', ['binding' => 'perfect', 'gloss' => true]);
        $template = (string) file_get_contents(dirname(__DIR__, 3).'/wp-content/plugins/stiqon-shop/templates/mockup/brochure.php');

        $this->assertStringStartsWith('<svg', ltrim($svg));
        $this->assertStringContainsString('viewBox="0 0 120 120"', $svg);
        $this->assertStringContainsString('data-mockup="brochure"', $svg);
        $this->assertSame(1, substr_count($svg, 'class="'), 'Only the root has classes.');
        $this->assertStringNotContainsString('<script', $svg);
        $this->assertStringNotContainsString('<style', $svg);

        // Lettering belongs to the motifs; the printed surface to the shared part.
        $this->assertStringNotContainsString('<text', $template);
        $this->assertStringNotContainsString('x-data', $template);
        $this->assertStringNotContainsString('data-motif', $template);
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
