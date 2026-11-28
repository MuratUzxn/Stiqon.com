<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Kinds;
use Stiqon\Shop\Mockup\Leaflet;
use Stiqon\Shop\Mockup\Uid;
use Stiqon\Shop\Template;

/**
 * Folded leaflets: DIN lang panels standing partly open, rolled in, folded
 * like an accordion or once down the middle, on thin or stiff paper, matte or
 * glossy. Every combination the shop can ask for has to render, the visitor's
 * motif has to reach the front panel, and the names of products and options
 * have to lead to the right drawing.
 */
final class LeafletTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_the_shop_knows_the_kind(): void
    {
        $this->assertTrue(Kinds::has('leaflet'));
        $this->assertSame(Leaflet::class, Kinds::of('leaflet'));
        $this->assertSame('Faltblatt', Kinds::labels()['leaflet']);
    }

    public function test_every_motif_renders_with_every_fold(): void
    {
        $this->assertGreaterThanOrEqual(5, count(Leaflet::motifs()));

        foreach (Leaflet::motifs() as $motif) {
            foreach (array_keys(Leaflet::form()['values']) as $fold) {
                $svg = stiqon_mockup('leaflet', compact('fold', 'motif'));

                $this->assertWellFormed($svg, "{$motif} with {$fold}");
                $this->assertStringContainsString('data-motif-sample', $svg);
                $this->assertStringContainsString('data-motif-image', $svg);
                $this->assertSame(1, substr_count($svg, 'x-data="motifLayer"'), 'One front panel, printed once.');
            }
        }
    }

    public function test_every_switch_renders_with_every_fold(): void
    {
        foreach (array_keys(Leaflet::form()['values']) as $fold) {
            foreach ([[], ['heavy' => true], ['gloss' => true], ['heavy' => true, 'gloss' => true]] as $switches) {
                $spec = compact('fold') + $switches;
                $svg = stiqon_mockup('leaflet', $spec);

                $this->assertWellFormed($svg, json_encode($spec));
                $this->assertStringContainsString('data-motif-image', $svg);
            }
        }
    }

    public function test_the_switches_change_the_drawing(): void
    {
        $plain = new Leaflet();
        $heavy = new Leaflet(heavy: true);
        $glossy = new Leaflet(gloss: true);

        $this->assertStringContainsString('#d6d6d0', $heavy->render(), 'Stiff paper shows its edge.');
        $this->assertStringNotContainsString('#d6d6d0', $plain->render());
        $this->assertStringContainsString('url(#'.$glossy->uid.'-gloss)', $glossy->render());
        $this->assertStringNotContainsString('-gloss', $plain->render());
    }

    public function test_each_fold_stands_its_own_way(): void
    {
        $rolled = new Leaflet(fold: 'wickel');
        $accordion = new Leaflet(fold: 'zickzack');
        $book = new Leaflet(fold: 'einbruch');

        $this->assertCount(3, $rolled->panels);
        $this->assertCount(3, $accordion->panels);
        $this->assertCount(2, $book->panels);

        // Rolled in: both folds point the same way. Accordion: they take turns. Book: one fold.
        $this->assertSame([true, true], array_column($rolled->folds, 'ridge'));
        $this->assertSame([false, true], array_column($accordion->folds, 'ridge'));
        $this->assertSame([true], array_column($book->folds, 'ridge'));

        foreach ([$rolled, $accordion, $book] as $leaflet) {
            $faces = array_column($leaflet->panels, 'face');

            $this->assertSame(['cover'], array_values(array_unique(array_diff($faces, ['page']))), "{$leaflet->fold}: one front panel");
            $this->assertCount(1, array_keys($faces, 'cover', true));

            // The front panel catches the most light, so the motif reads best.
            $cover = $leaflet->panels[array_search('cover', $faces, true)];
            $this->assertSame(max(array_column($leaflet->panels, 'light')), $cover['light'], $leaflet->fold);
        }
    }

    public function test_the_panels_are_din_lang_and_follow_each_other_without_a_gap(): void
    {
        foreach (array_keys(Leaflet::FOLDS) as $fold) {
            $leaflet = new Leaflet(fold: $fold);

            $this->assertEqualsWithDelta(210 / 99, $leaflet->panelHeight / $leaflet->panelWidth, 0.01, $fold);

            foreach ($leaflet->panels as $index => $panel) {
                if ($index > 0) {
                    $this->assertSame($leaflet->panels[$index - 1]['right'], $panel['left'], "{$fold}: panel {$index}");
                    $this->assertSame($panel['left'][0], $leaflet->folds[$index - 1]['x']);
                }

                // The matrix lays the unfolded panel (0 0 width height) onto the panel's corners.
                [$a, $b, $c, $d, $e, $f] = array_map('floatval', explode(' ', trim($panel['matrix'], 'matrix()')));

                $this->assertEqualsWithDelta($panel['left'], [$e, $f], 0.001, "{$fold}: panel {$index}");
                $this->assertEqualsWithDelta($panel['right'], [$a * $leaflet->panelWidth + $e, $b * $leaflet->panelWidth + $f], 0.02, "{$fold}: panel {$index}");
                $this->assertSame([0.0, 1.0], [$c, $d], 'Panels stand upright.');
            }
        }
    }

    public function test_every_drawing_keeps_inside_the_box_and_fills_it(): void
    {
        foreach (array_keys(Leaflet::FOLDS) as $fold) {
            $leaflet = new Leaflet(fold: $fold, heavy: true);
            $xs = [];
            $ys = [];

            foreach ($leaflet->panels as $panel) {
                foreach ([$panel['left'], $panel['right']] as [$x, $y]) {
                    $xs[] = $x;
                    // The edge of stiff paper rises a little over the top.
                    $ys[] = $y - 1.2;
                    $ys[] = $y + $leaflet->panelHeight;
                }
            }

            $this->assertGreaterThanOrEqual(10.0, min($xs), $fold);
            $this->assertLessThanOrEqual(110.0, max($xs), $fold);
            $this->assertGreaterThanOrEqual(10.0, min($ys), $fold);
            $this->assertLessThanOrEqual(110.0, max($ys), $fold);
            $this->assertGreaterThan(85.0, max(max($xs) - min($xs), max($ys) - min($ys)), "{$fold} fills its card.");
        }
    }

    public function test_panels_turned_from_the_light_and_folds_turned_away_are_darker(): void
    {
        $accordion = new Leaflet(fold: 'zickzack');

        [$coverLeft, $coverRight] = $accordion->shade(0);
        [$middleLeft, $middleRight] = $accordion->shade(1);

        $this->assertGreaterThan(max($coverLeft, $coverRight), min($middleLeft, $middleRight), 'The panel turned from the light is darker.');
        $this->assertGreaterThan($coverLeft, $coverRight, 'Darker into the valley fold.');
        $this->assertGreaterThan($middleRight, $middleLeft, 'Darker into the valley fold, lighter at the ridge.');
    }

    public function test_a_leaflet_describes_itself_for_screen_readers(): void
    {
        $this->assertSame('Faltblatt mit Wickelfalz', (new Leaflet())->describe());
        $this->assertSame('Faltblatt mit Zickzackfalz', (new Leaflet(fold: 'zickzack'))->describe());
        $this->assertSame('Faltblatt mit Einbruchfalz', (new Leaflet(fold: 'einbruch'))->describe());
        $this->assertSame('Programmheft', (new Leaflet(label: 'Programmheft'))->describe());

        $this->assertStringContainsString('aria-label="Faltblatt mit Zickzackfalz"', stiqon_mockup('leaflet', ['fold' => 'zickzack']));
        $this->assertStringContainsString('aria-label="A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;"', stiqon_mockup('leaflet', ['label' => 'A "B" <c> & \'d\'']));
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
            'fold' => [static fn () => new Leaflet(fold: 'kreuzbruch')],
            'motif' => [static fn () => new Leaflet(motif: 'gibt-es-nicht')],
            'sticker motif' => [static fn () => new Leaflet(motif: 'stern')],
            'motif outside the directory' => [static fn () => new Leaflet(motif: '../mockup/leaflet')],
            'fold from a spec' => [static fn () => Leaflet::fromArray(['fold' => 'Wickel'])],
            'motif from a spec' => [static fn () => Leaflet::fromArray(['motif' => 'sonne'])],
        ];
    }

    public function test_an_empty_spec_draws_the_first_of_everything(): void
    {
        $leaflet = Leaflet::fromArray([]);

        $this->assertSame(array_key_first(Leaflet::form()['values']), $leaflet->fold);
        $this->assertSame('wickel', $leaflet->fold);
        $this->assertSame(Leaflet::motifs()[0], $leaflet->motif);
        $this->assertFalse($leaflet->heavy);
        $this->assertFalse($leaflet->gloss);
        $this->assertNull($leaflet->label);
    }

    public function test_keys_of_other_kinds_are_ignored(): void
    {
        $leaflet = Leaflet::fromArray([
            'type' => 'flyer', 'format' => 'a4', 'binding' => 'perfect', 'shape' => 'circle',
            'stack' => true, 'finish' => 'gold', 'fold' => 'einbruch', 'heavy' => 1,
        ]);

        $this->assertSame('einbruch', $leaflet->fold);
        $this->assertTrue($leaflet->heavy);
        $this->assertFalse($leaflet->gloss);
        $this->assertSame(Leaflet::motifs()[0], $leaflet->motif);
        $this->assertWellFormed($leaflet->render(), 'a spec with foreign keys');
    }

    public function test_the_switches_are_read_as_true_or_false(): void
    {
        $this->assertTrue(Leaflet::fromArray(['heavy' => '1', 'gloss' => 1])->heavy);
        $this->assertTrue(Leaflet::fromArray(['heavy' => '1', 'gloss' => 1])->gloss);
        $this->assertFalse(Leaflet::fromArray(['heavy' => '', 'gloss' => 0])->heavy);
        $this->assertFalse(Leaflet::fromArray(['heavy' => '', 'gloss' => 0])->gloss);
    }

    public function test_ids_stay_unique_and_references_resolve_when_drawings_share_a_page(): void
    {
        $drawings = [
            Leaflet::fromArray(['fold' => 'wickel', 'heavy' => true, 'gloss' => true]),
            Leaflet::fromArray(['fold' => 'zickzack', 'motif' => 'fest', 'gloss' => true]),
            Leaflet::fromArray(['fold' => 'einbruch', 'motif' => 'prozent']),
        ];
        $page = implode('', array_map(static fn (Leaflet $leaflet): string => $leaflet->render(), $drawings));

        preg_match_all('/\sid="([^"]+)"/', $page, $ids);
        preg_match_all('/url\(#([^)]+)\)|href="#([^"]+)"/', $page, $references);

        $referenced = array_unique(array_filter(array_merge($references[1], $references[2])));

        $this->assertGreaterThan(8, count($ids[1]));
        $this->assertSame([], array_keys(array_filter(array_count_values($ids[1]), static fn (int $count): bool => $count > 1)), 'Ids repeat.');
        $this->assertNotEmpty($referenced);
        $this->assertSame([], array_values(array_diff($referenced, $ids[1])), 'References point nowhere.');

        foreach ($drawings as $leaflet) {
            preg_match_all('/\sid="([^"]+)"/', $leaflet->render(), $own);

            foreach ($own[1] as $id) {
                $this->assertStringStartsWith($leaflet->uid.'-', $id);
            }
        }
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Leaflet::keys();
        $form = Leaflet::form();

        $this->assertSame('fold', $form['key']);
        $this->assertSame('Falzart', $form['label']);
        $this->assertSame(['wickel' => 'Wickelfalz', 'zickzack' => 'Zickzackfalz', 'einbruch' => 'Einbruchfalz'], $form['values']);
        $this->assertArrayHasKey($form['key'], $keys);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Leaflet::motifs(), $keys['motif']);
        $this->assertSame(['fold', 'motif', 'heavy', 'gloss'], array_keys($keys));
        $this->assertNull($keys['heavy']);
        $this->assertNull($keys['gloss']);

        foreach (Leaflet::motifs() as $motif) {
            $this->assertTrue(Template::exists('motif/flyer/'.$motif), $motif);
        }

        foreach (array_keys(Leaflet::names()['form']) as $value) {
            $this->assertContains($value, $keys[$form['key']]);
        }

        foreach (array_keys(Leaflet::names()['switches']) as $key) {
            $this->assertNull($keys[$key]);
        }
    }

    /**
     * @dataProvider productNames
     */
    public function test_product_names_say_it_is_a_leaflet(string $name): void
    {
        $this->assertSame('leaflet', Guess::type($name));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function productNames(): array
    {
        return [
            'roll fold' => ['Faltblatt mit Wickelfalz'],
            'zigzag fold' => ['Faltblatt mit Zickzackfalz'],
            'folded flyer' => ['Falzflyer'],
            'leporello' => ['Leporello DIN lang'],
            'plural' => ['Faltblätter'],
        ];
    }

    public function test_a_flyer_stays_a_flyer(): void
    {
        $this->assertSame('flyer', Guess::type('Flyer DIN lang'));
        $this->assertSame('flyer', Guess::type('Flyer DIN A5'));
    }

    public function test_names_say_the_fold(): void
    {
        $this->assertSame(['fold' => 'wickel'], Guess::product('leaflet', 'Faltblatt mit Wickelfalz'));
        $this->assertSame(['fold' => 'zickzack'], Guess::product('leaflet', 'Faltblatt mit Zickzackfalz'));
        $this->assertSame(['fold' => 'zickzack'], Guess::product('leaflet', 'Leporello DIN lang'));
        $this->assertSame(['fold' => 'einbruch'], Guess::product('leaflet', 'Faltblatt mit Einbruchfalz'));
        $this->assertSame(['fold' => 'einbruch'], Guess::product('leaflet', 'Klappflyer'));
        $this->assertSame([], Guess::product('leaflet', 'Falzflyer'));
    }

    public function test_option_names_say_the_paper_and_the_finish(): void
    {
        $this->assertSame(['heavy' => false], Guess::option('leaflet', '135 g Bilderdruck'));
        $this->assertSame(['heavy' => true], Guess::option('leaflet', '250 g Bilderdruck'));
        $this->assertSame(['heavy' => true, 'gloss' => true], Guess::option('leaflet', '300 g Bilderdruck glänzend'));
        $this->assertSame(['heavy' => false, 'gloss' => false], Guess::option('leaflet', '135 g Bilderdruck matt'));
        $this->assertSame([], Guess::option('leaflet', '250 Stück'));
    }

    public function test_a_product_resolves_to_a_drawing(): void
    {
        $resolved = Spec::resolve(['name' => 'Faltblatt mit Zickzackfalz'], [['name' => '250 g Bilderdruck'], ['name' => '500']]);

        $this->assertSame('leaflet', $resolved['type']);
        $this->assertSame('zickzack', $resolved['spec']['fold']);
        $this->assertTrue($resolved['spec']['heavy']);
        $this->assertContains($resolved['spec']['motif'], Leaflet::motifs());
        $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), 'resolved');

        $stored = Spec::resolve(['name' => 'Falzflyer', 'stored' => ['type' => 'leaflet', 'fold' => 'einbruch', 'motif' => 'karte']], [['name' => '135 g Bilderdruck']]);

        $this->assertSame(['fold' => 'einbruch', 'motif' => 'karte', 'heavy' => false], $stored['spec']);
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('leaflet'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('leaflet', [], 'w-40'));
        $this->assertMatchesRegularExpression('/class="[^"]*\blift\b/', stiqon_mockup('leaflet'));
    }

    public function test_the_drawing_keeps_to_the_house_rules(): void
    {
        $svg = stiqon_mockup('leaflet', ['fold' => 'zickzack', 'heavy' => true, 'gloss' => true]);
        $plugin = dirname(__DIR__, 3).'/wp-content/plugins/stiqon-shop/templates/mockup/';

        $this->assertStringStartsWith('<svg', ltrim($svg));
        $this->assertStringContainsString('viewBox="0 0 120 120"', $svg);
        $this->assertStringContainsString('data-mockup="leaflet"', $svg);
        $this->assertSame(1, substr_count($svg, 'class="'), 'Only the root has classes.');
        $this->assertStringNotContainsString('<script', $svg);
        $this->assertStringNotContainsString('<style', $svg);

        // Lettering belongs to the motifs, the printed surface to the shared part: text on inner pages is grey bars.
        foreach (['leaflet.php', 'parts/booklet-page.php'] as $file) {
            $template = (string) file_get_contents($plugin.$file);

            $this->assertStringNotContainsString('<text', $template, $file);
            $this->assertStringNotContainsString('x-data', $template, $file);
            $this->assertStringNotContainsString('data-motif', $template, $file);
        }
    }

    public function test_an_inner_page_fills_its_height_with_text(): void
    {
        $short = Template::render('mockup/parts/booklet-page', ['width' => 40.0, 'height' => 60.0, 'layout' => 'picture']);
        $tall = Template::render('mockup/parts/booklet-page', ['width' => 40.0, 'height' => 90.0, 'layout' => 'picture']);

        $this->assertGreaterThan(substr_count($short, '<rect'), substr_count($tall, '<rect'));
        $this->assertSame(1, substr_count($tall, '<circle'), 'One picture.');
        $this->assertSame(1, substr_count(Template::render('mockup/parts/booklet-page', ['width' => 40.0, 'height' => 90.0, 'layout' => 'text']), '<circle'));

        preg_match_all('/\sy="([\d.]+)" width="[\d.]+" height="([\d.]+)"/', $tall, $rows, PREG_SET_ORDER);

        foreach ($rows as [, $y, $height]) {
            $this->assertLessThanOrEqual(90.0, (float) $y + (float) $height, 'Nothing runs off the page.');
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
