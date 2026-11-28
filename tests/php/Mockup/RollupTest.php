<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Flyer;
use Stiqon\Shop\Mockup\Rollup;
use Stiqon\Shop\Mockup\Uid;

/**
 * A roll-up stands in its cassette, 200 cm tall in every format: the banner
 * only grows wider. Its cassette is a plain silver box or a wider chrome one.
 */
final class RollupTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_every_format_with_every_cassette(): void
    {
        $this->assertGreaterThanOrEqual(5, count(Rollup::motifs()));

        foreach (Rollup::motifs() as $motif) {
            foreach (array_keys(Rollup::form()['values']) as $format) {
                foreach (Rollup::keys()['model'] as $model) {
                    $svg = stiqon_mockup('rollup', compact('format', 'motif', 'model'));

                    $this->assertWellFormed($svg, "{$motif} as {$format} on {$model}");
                    $this->assertStringContainsString('data-mockup="rollup"', $svg);
                    $this->assertStringContainsString('data-motif-image', $svg);
                    $this->assertStringContainsString('data-motif-sample', $svg);
                }
            }
        }
    }

    public function test_the_motif_fills_the_banner_on_white_film(): void
    {
        $rollup = new Rollup(format: '100x200', motif: 'fest');
        $svg = $rollup->render();
        $box = sprintf('x="%s" y="%s" width="%s" height="%s"', $rollup->banner['x'], $rollup->banner['y'], $rollup->banner['width'], $rollup->banner['height']);

        $this->assertStringContainsString('<svg '.$box.' viewBox="0 0 100 141.4" preserveAspectRatio="xMidYMid slice"', $svg);
        $this->assertStringContainsString('<rect '.$box.' fill="#fff" />', $svg, 'White film under the visitor\'s motif.');
        // The sample fills the print; a visitor's logo is shown whole, on white, not cut to a strip.
        $this->assertStringContainsString('<image '.$box.' preserveAspectRatio="xMidYMid meet"', $svg);
        $this->assertStringContainsString('>Sommerfest</text>', $svg);
    }

    public function test_a_wider_format_draws_a_wider_banner_of_the_same_height(): void
    {
        $widths = [];

        foreach (array_keys(Rollup::FORMATS) as $format) {
            $rollup = new Rollup(format: $format);
            $banner = $rollup->banner;
            $widths[$format] = $banner['width'];

            $this->assertEqualsWithDelta(Rollup::FORMATS[$format]['width'] / 200, $banner['width'] / $banner['height'], 0.005, "{$format}: to scale");
            $this->assertEqualsWithDelta(60.0, $banner['x'] + $banner['width'] / 2, 0.01, "{$format}: centred");
            $this->assertSame((new Rollup())->banner['height'], $banner['height'], "{$format}: always 200 cm tall");

            // The rail covers the banner's top edge, the cassette its bottom edge.
            $this->assertLessThan($banner['x'], $rollup->rail['x'], $format);
            $this->assertGreaterThan($banner['x'] + $banner['width'], $rollup->rail['x'] + $rollup->rail['width'], $format);
            $this->assertLessThan($banner['y'], $rollup->rail['y'], $format);
            $this->assertGreaterThan($banner['y'], $rollup->rail['y'] + $rollup->rail['height'], $format);
            $this->assertLessThan($banner['y'] + $banner['height'], $rollup->cassette['y'], $format);
        }

        $this->assertLessThan($widths['100x200'], $widths['85x200']);
        $this->assertLessThan($widths['120x200'], $widths['100x200']);
    }

    public function test_the_premium_cassette_is_wider_and_rounded(): void
    {
        foreach (array_keys(Rollup::FORMATS) as $format) {
            $basic = new Rollup(format: $format, model: 'basic');
            $premium = new Rollup(format: $format, model: 'premium');

            $this->assertSame($basic->banner, $premium->banner, $format);
            $this->assertGreaterThan($basic->cassette['width'], $premium->cassette['width'], $format);
            $this->assertGreaterThan($basic->cassette['x'], $basic->banner['x'], $format);
        }

        $basic = stiqon_mockup('rollup', ['model' => 'basic']);
        Uid::reset();
        $premium = stiqon_mockup('rollup', ['model' => 'premium']);

        // Chrome reflects harder than brushed aluminium; the basic box stands on two feet.
        $this->assertGreaterThan(substr_count($basic, '<stop'), substr_count($premium, '<stop'));
        $this->assertSame(2, substr_count($basic, 'width="10" height="2.8" rx="1.4"'), 'Two flat feet.');
        $this->assertStringNotContainsString('width="10" height="2.8" rx="1.4"', $premium);
    }

    public function test_the_roll_up_uses_the_height_of_the_card(): void
    {
        foreach (array_keys(Rollup::FORMATS) as $format) {
            foreach (Rollup::keys()['model'] as $model) {
                $rollup = new Rollup(format: $format, model: $model);

                $this->assertGreaterThanOrEqual(5.0, $rollup->rail['y'] - 2.2, "{$format}/{$model}: the pole's hook stays inside");
                $this->assertLessThanOrEqual(9.0, $rollup->rail['y'], "{$format}/{$model}: reaches up");
                $this->assertGreaterThanOrEqual(106.0, $rollup->cassette['y'] + $rollup->cassette['height'], "{$format}/{$model}: reaches down");
                $this->assertLessThanOrEqual(111.0, $rollup->cassette['y'] + $rollup->cassette['height'], "{$format}/{$model}: room for feet and shadow");
                $this->assertGreaterThanOrEqual(10.0, $rollup->cassette['x'] - 2.4, "{$format}/{$model}");
                $this->assertLessThanOrEqual(110.0, $rollup->cassette['x'] + $rollup->cassette['width'] + 2.4, "{$format}/{$model}");
            }
        }
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertSame('Roll-up', Rollup::label());
        $this->assertStringContainsString('aria-label="Roll-up 85 × 200 cm"', stiqon_mockup('rollup'));
        $this->assertStringContainsString('aria-label="Roll-up 100 × 200 cm"', stiqon_mockup('rollup', ['format' => '100x200']));
        $this->assertStringContainsString('aria-label="Roll-up 120 × 200 cm, Modell Premium"', stiqon_mockup('rollup', ['format' => '120x200', 'model' => 'premium']));
        $this->assertStringContainsString('aria-label="Roll-up für die Messe"', stiqon_mockup('rollup', ['label' => 'Roll-up für die Messe']));
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
            'format' => [static fn () => new Rollup(format: '60x200'), 'Unknown rollup format [60x200].'],
            'format as written for people' => [static fn () => Rollup::fromArray(['format' => '85 × 200 cm']), 'Unknown rollup format [85 × 200 cm].'],
            'motif' => [static fn () => new Rollup(motif: 'gibt-es-nicht'), 'Unknown rollup motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Rollup(motif: '../mockup/flyer'), 'Unknown rollup motif'],
            'sticker motif' => [static fn () => Rollup::fromArray(['motif' => 'siegel']), 'Unknown rollup motif [siegel].'],
            'banner motif' => [static fn () => Rollup::fromArray(['motif' => 'angebot']), 'Unknown rollup motif [angebot].'],
            'model' => [static fn () => new Rollup(model: 'deluxe'), 'Unknown rollup model [deluxe].'],
            'model in capitals' => [static fn () => Rollup::fromArray(['model' => 'Premium']), 'Unknown rollup model [Premium].'],
        ];
    }

    public function test_without_values_the_first_format_motif_and_cassette_are_drawn(): void
    {
        $rollup = Rollup::fromArray([]);

        $this->assertSame(array_key_first(Rollup::form()['values']), $rollup->format);
        $this->assertSame('85x200', $rollup->format);
        $this->assertSame(Rollup::motifs()[0], $rollup->motif);
        $this->assertSame('basic', $rollup->model);
        $this->assertNull($rollup->label);
        $this->assertSame((new Rollup())->format, $rollup->format);
        $this->assertSame((new Rollup())->motif, $rollup->motif);
        $this->assertSame((new Rollup())->model, $rollup->model);
    }

    public function test_values_of_other_kinds_are_ignored(): void
    {
        $rollup = Rollup::fromArray(['shape' => 'dreieck', 'material' => 'gold', 'variant' => 'desk', 'stack' => true, 'gloss' => true, 'color' => 'black', 'model' => 'premium']);

        $this->assertSame('85x200', $rollup->format);
        $this->assertSame('premium', $rollup->model);
        $this->assertWellFormed($rollup->render(), 'foreign keys');
    }

    public function test_ids_stay_unique_and_references_resolve_when_roll_ups_share_a_page(): void
    {
        $drawings = [
            stiqon_mockup('rollup'),
            stiqon_mockup('rollup', ['format' => '120x200', 'motif' => 'fest', 'model' => 'premium']),
            stiqon_mockup('rollup', ['format' => '100x200', 'motif' => 'kursplan']),
        ];

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'ru', 3);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Rollup::keys();
        $form = Rollup::form();

        $this->assertSame(['format', 'motif', 'model'], array_keys($keys));
        $this->assertSame('format', $form['key']);
        $this->assertSame('Format', $form['label']);
        $this->assertSame(['85x200' => '85 × 200 cm', '100x200' => '100 × 200 cm', '120x200' => '120 × 200 cm'], $form['values']);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Rollup::motifs(), $keys['motif']);
        $this->assertSame(Flyer::motifs(), Rollup::motifs());
        $this->assertSame(['basic', 'premium'], $keys['model']);

        $names = Rollup::names();
        $this->assertSame(array_keys($form['values']), array_keys($names['form']));
        $this->assertSame(['format', 'model'], array_keys($names['options']));
        $this->assertSame($names['form'], $names['options']['format']);
        $this->assertEqualsCanonicalizing($keys['model'], array_keys($names['options']['model']));
        $this->assertSame([], $names['switches']);
    }

    public function test_names_say_a_product_is_a_roll_up_how_wide_it_is_and_which_cassette_it_has(): void
    {
        foreach (['Roll-ups', 'Roll-up Banner', 'Rollup Premium', 'Roll-Up Banner Premium', 'Rollups'] as $name) {
            $this->assertSame('rollup', Guess::type($name), $name);
        }

        $this->assertSame('rollup', Guess::type('85 × 200 cm', 'Roll-ups'));

        $this->assertSame(['format' => '85x200'], Guess::product('rollup', 'Roll-up 85 x 200 cm'));
        $this->assertSame(['format' => '100x200'], Guess::product('rollup', 'Roll-up Banner 100x200'));
        $this->assertSame(['format' => '120x200'], Guess::product('rollup', 'Roll-up 120 × 200 cm'));
        $this->assertSame([], Guess::product('rollup', 'Roll-ups'));
        $this->assertSame([], Guess::product('rollup', 'Rollup Premium'));

        $this->assertSame(['format' => '85x200'], Guess::option('rollup', '85 × 200 cm'));
        $this->assertSame(['format' => '100x200'], Guess::option('rollup', '100 × 200 cm'));
        $this->assertSame(['format' => '120x200'], Guess::option('rollup', '120 × 200 cm'));
        $this->assertSame(['model' => 'premium'], Guess::option('rollup', 'Premium'));
        $this->assertSame(['model' => 'basic'], Guess::option('rollup', 'Basic'));
        $this->assertSame(['model' => 'basic'], Guess::option('rollup', 'Classic'));
        $this->assertSame(['model' => 'basic'], Guess::option('rollup', 'Standard-Kassette'));
        $this->assertSame([], Guess::option('rollup', '2 Stück'));
        $this->assertSame([], Guess::option('rollup', 'mit Tragetasche'));
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        $resolved = Spec::resolve(['name' => 'Roll-ups'], [['name' => '100 × 200 cm'], ['name' => 'Premium'], ['name' => '2 Stück']]);

        $this->assertSame('rollup', $resolved['type']);
        $this->assertSame('100x200', $resolved['spec']['format']);
        $this->assertSame('premium', $resolved['spec']['model']);
        $this->assertContains($resolved['spec']['motif'], Rollup::motifs());
        $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), 'resolved');
    }

    public function test_the_drawing_itself_carries_no_lettering_styles_or_scripts(): void
    {
        foreach (Rollup::keys()['model'] as $model) {
            $drawing = $this->withoutSample(stiqon_mockup('rollup', ['model' => $model, 'motif' => 'fest']));

            $this->assertStringNotContainsString('<text', $drawing, $model);
            $this->assertStringNotContainsString('<style', $drawing, $model);
            $this->assertStringNotContainsString('<script', $drawing, $model);
            $this->assertSame(1, substr_count($drawing, ' class="'), "{$model}: only the root has classes");
        }
    }

    public function test_text_from_outside_cannot_break_out_of_an_attribute(): void
    {
        $hostile = 'A "B" <c> & \'d\'';
        $escaped = 'A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;';

        foreach (['label' => stiqon_mockup('rollup', ['label' => $hostile]), 'class' => stiqon_mockup('rollup', ['model' => 'premium'], $hostile)] as $what => $markup) {
            $this->assertStringContainsString($escaped, $markup, $what);
            $this->assertStringNotContainsString('<c>', $markup, $what);
        }
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b[^"]*\blift\b/', stiqon_mockup('rollup'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('rollup', [], 'w-[86%]'));
        $this->assertStringContainsString('viewBox="0 0 120 120"', stiqon_mockup('rollup'));
    }

    /**
     * The drawing without the sample motif printed on it.
     */
    private function withoutSample(string $svg): string
    {
        return (string) preg_replace('~<svg [^>]*data-motif-sample>.*?</svg>~s', '', $svg);
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
