<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Flyer;
use Stiqon\Shop\Mockup\Standee;
use Stiqon\Shop\Mockup\Uid;

/**
 * A Kundenstopper holds its poster in a click frame and stands on the
 * pavement as an A: from the front and a little from the left, the second
 * leg shows behind the first.
 */
final class StandeeTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_every_format(): void
    {
        $this->assertGreaterThanOrEqual(5, count(Standee::motifs()));

        foreach (Standee::motifs() as $motif) {
            foreach (array_keys(Standee::form()['values']) as $format) {
                $svg = stiqon_mockup('standee', compact('format', 'motif'));

                $this->assertWellFormed($svg, "{$motif} as {$format}");
                $this->assertStringContainsString('data-mockup="standee"', $svg);
                $this->assertStringContainsString('data-motif-image', $svg);
                $this->assertStringContainsString('data-motif-sample', $svg);
            }
        }
    }

    public function test_the_motif_fills_the_poster_inside_the_frame(): void
    {
        $standee = new Standee(format: 'a0', motif: 'fest');
        $svg = $standee->render();
        $poster = $standee->poster;
        $frame = $standee->frame;
        $box = sprintf('x="%s" y="%s" width="%s" height="%s"', $poster['x'], $poster['y'], $poster['width'], $poster['height']);

        $this->assertEqualsWithDelta(M_SQRT2, $poster['height'] / $poster['width'], 0.01, 'A DIN poster.');
        $this->assertStringContainsString('<svg '.$box.' viewBox="0 0 100 141.4" preserveAspectRatio="xMidYMid slice"', $svg);
        $this->assertStringContainsString('<rect '.$box.' fill="#fff" />', $svg, 'White paper under the visitor\'s motif.');
        $this->assertStringContainsString('<image '.$box.' preserveAspectRatio="xMidYMid slice"', $svg);
        $this->assertStringContainsString('>Sommerfest</text>', $svg);

        // The profile runs all round the poster.
        $this->assertEqualsWithDelta(Standee::PROFILE, $poster['x'] - $frame['x'], 0.01);
        $this->assertEqualsWithDelta(Standee::PROFILE, $poster['y'] - $frame['y'], 0.01);
        $this->assertEqualsWithDelta(Standee::PROFILE, $frame['x'] + $frame['width'] - $poster['x'] - $poster['width'], 0.01);
        $this->assertEqualsWithDelta(Standee::PROFILE, $frame['y'] + $frame['height'] - $poster['y'] - $poster['height'], 0.01);
    }

    public function test_the_frame_is_mitred_at_its_corners(): void
    {
        $standee = new Standee();
        $svg = $standee->render();
        ['x' => $x, 'y' => $y] = $standee->frame;
        $profile = Standee::PROFILE;

        foreach (['top', 'left', 'bottom', 'right'] as $side) {
            $this->assertStringContainsString('fill="url(#sd1-'.$side.')"', $svg, $side);
        }

        // Top and left meet on the diagonal of the corner.
        $this->assertStringContainsString("M{$x} {$y}L".($x + $profile).' '.($y + $profile), $svg);
        $this->assertStringContainsString('l'.$profile.' '.$profile, $svg);
    }

    public function test_a0_stands_taller_than_a1_on_the_same_ground(): void
    {
        $a1 = new Standee(format: 'a1');
        $a0 = new Standee(format: 'a0');

        $this->assertGreaterThan($a1->poster['height'], $a0->poster['height']);
        $this->assertLessThan($a1->frame['y'], $a0->frame['y'], 'A0 rises higher.');
        $this->assertSame($a1->ground, $a0->ground, 'Both stand on the same ground.');
        $this->assertLessThan(1.3 * $a1->poster['height'], $a0->poster['height'], 'A step taller, not twice as large.');

        foreach ([$a1, $a0] as $standee) {
            $frame = $standee->frame;

            // The second leg stands behind the first: further left and a little higher up.
            $this->assertLessThan($frame['x'], $standee->back[0], $standee->format);
            $this->assertLessThan($standee->ground, $standee->back[1], $standee->format);
            $this->assertGreaterThan($frame['y'] + $frame['height'], $standee->back[1], $standee->format);

            // Inside the card, with room below for the shadow.
            $this->assertGreaterThanOrEqual(10.0, $standee->back[0], $standee->format);
            $this->assertLessThanOrEqual(110.0, $frame['x'] + $frame['width'], $standee->format);
            $this->assertGreaterThanOrEqual(8.0, $frame['y'], $standee->format);
            $this->assertLessThanOrEqual(108.0, $standee->ground, $standee->format);
        }

        // From the hinges to the feet, A1 fills most of the card's height and A0 nearly all of it.
        $this->assertGreaterThan(0.72 * 120, $a1->ground - $a1->frame['y']);
        $this->assertGreaterThan(0.8 * 120, $a0->ground - $a0->frame['y']);
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertSame('Kundenstopper', Standee::label());
        $this->assertStringContainsString('aria-label="Kundenstopper DIN A1"', stiqon_mockup('standee'));
        $this->assertStringContainsString('aria-label="Kundenstopper DIN A0"', stiqon_mockup('standee', ['format' => 'a0']));
        $this->assertStringContainsString('aria-label="Aufsteller vor dem Café"', stiqon_mockup('standee', ['label' => 'Aufsteller vor dem Café']));
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
            'format' => [static fn () => new Standee(format: 'a2'), 'Unknown standee format [a2].'],
            'format as written for people' => [static fn () => Standee::fromArray(['format' => 'DIN A1']), 'Unknown standee format [DIN A1].'],
            'motif' => [static fn () => new Standee(motif: 'gibt-es-nicht'), 'Unknown standee motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Standee(motif: '../mockup/flyer'), 'Unknown standee motif'],
            'sticker motif' => [static fn () => Standee::fromArray(['motif' => 'siegel']), 'Unknown standee motif [siegel].'],
            'banner motif' => [static fn () => Standee::fromArray(['motif' => 'messe']), 'Unknown standee motif [messe].'],
        ];
    }

    public function test_without_values_the_first_format_and_motif_are_drawn(): void
    {
        $standee = Standee::fromArray([]);

        $this->assertSame(array_key_first(Standee::form()['values']), $standee->format);
        $this->assertSame('a1', $standee->format);
        $this->assertSame(Standee::motifs()[0], $standee->motif);
        $this->assertNull($standee->label);
        $this->assertSame((new Standee())->format, $standee->format);
        $this->assertSame((new Standee())->motif, $standee->motif);
    }

    public function test_values_of_other_kinds_are_ignored(): void
    {
        $standee = Standee::fromArray(['shape' => 'dreieck', 'material' => 'gold', 'model' => 'premium', 'variant' => 'desk', 'gloss' => true, 'format' => 'a0']);

        $this->assertSame('a0', $standee->format);
        $this->assertWellFormed($standee->render(), 'foreign keys');
    }

    public function test_ids_stay_unique_and_references_resolve_when_standees_share_a_page(): void
    {
        $drawings = [
            stiqon_mockup('standee'),
            stiqon_mockup('standee', ['format' => 'a0', 'motif' => 'fest']),
            stiqon_mockup('standee', ['motif' => 'prozent']),
        ];

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'sd', 3);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Standee::keys();
        $form = Standee::form();

        $this->assertSame(['format', 'motif'], array_keys($keys));
        $this->assertSame('format', $form['key']);
        $this->assertSame('Format', $form['label']);
        $this->assertSame(['a1' => 'DIN A1', 'a0' => 'DIN A0'], $form['values']);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Standee::motifs(), $keys['motif']);
        $this->assertSame(Flyer::motifs(), Standee::motifs());

        $names = Standee::names();
        $this->assertSame(array_keys($form['values']), array_keys($names['form']));
        $this->assertSame(['format'], array_keys($names['options']));
        $this->assertSame($names['form'], $names['options']['format']);
        $this->assertSame([], $names['switches']);
    }

    public function test_names_say_a_product_is_a_standee_and_how_large_it_is(): void
    {
        foreach (['Kundenstopper', 'Plakatständer', 'Gehwegaufsteller', 'Kundenstopper DIN A1', 'Plakatstander'] as $name) {
            $this->assertSame('standee', Guess::type($name), $name);
        }

        $this->assertSame('standee', Guess::type('DIN A0', 'Kundenstopper'));

        $this->assertSame(['format' => 'a1'], Guess::product('standee', 'Kundenstopper DIN A1'));
        $this->assertSame(['format' => 'a0'], Guess::product('standee', 'Plakatständer A0'));
        $this->assertSame([], Guess::product('standee', 'Gehwegaufsteller'));

        $this->assertSame(['format' => 'a1'], Guess::option('standee', 'DIN A1'));
        $this->assertSame(['format' => 'a0'], Guess::option('standee', 'DIN A0'));
        $this->assertSame([], Guess::option('standee', '1 Stück'));
        $this->assertSame([], Guess::option('standee', 'Auflage 10'));
        $this->assertSame([], Guess::option('standee', 'beidseitig'));
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        $resolved = Spec::resolve(['name' => 'Kundenstopper'], [['name' => 'DIN A0'], ['name' => '2 Stück']]);

        $this->assertSame('standee', $resolved['type']);
        $this->assertSame('a0', $resolved['spec']['format']);
        $this->assertContains($resolved['spec']['motif'], Standee::motifs());
        $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), 'resolved');
    }

    public function test_the_drawing_itself_carries_no_lettering_styles_or_scripts(): void
    {
        foreach (array_keys(Standee::FORMATS) as $format) {
            $drawing = $this->withoutSample(stiqon_mockup('standee', ['format' => $format, 'motif' => 'fest']));

            $this->assertStringNotContainsString('<text', $drawing, $format);
            $this->assertStringNotContainsString('<style', $drawing, $format);
            $this->assertStringNotContainsString('<script', $drawing, $format);
            $this->assertSame(1, substr_count($drawing, ' class="'), "{$format}: only the root has classes");
        }
    }

    public function test_text_from_outside_cannot_break_out_of_an_attribute(): void
    {
        $hostile = 'A "B" <c> & \'d\'';
        $escaped = 'A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;';

        foreach (['label' => stiqon_mockup('standee', ['label' => $hostile]), 'class' => stiqon_mockup('standee', ['format' => 'a0'], $hostile)] as $what => $markup) {
            $this->assertStringContainsString($escaped, $markup, $what);
            $this->assertStringNotContainsString('<c>', $markup, $what);
        }
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b[^"]*\blift\b/', stiqon_mockup('standee'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('standee', [], 'w-40'));
        $this->assertStringContainsString('viewBox="0 0 120 120"', stiqon_mockup('standee'));
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
