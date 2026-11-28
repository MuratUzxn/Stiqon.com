<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Box;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Mockup\Uid;

/**
 * A shipping box, closed and taped, seen from above one corner: three sizes,
 * brown or white board, a logo printed on the front.
 */
final class BoxTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_every_size_and_colour(): void
    {
        $this->assertGreaterThanOrEqual(12, count(Box::motifs()));

        foreach (Box::motifs() as $motif) {
            foreach (array_keys(Box::form()['values']) as $size) {
                foreach (Box::COLORS as $color) {
                    $svg = stiqon_mockup('box', compact('size', 'motif', 'color'));

                    $this->assertWellFormed($svg, "{$motif} on {$size}/{$color}");
                    $this->assertStringContainsString('data-mockup="box"', $svg);
                    $this->assertStringContainsString('data-motif-image', $svg);
                    $this->assertStringContainsString('data-motif-sample', $svg);
                }
            }
        }
    }

    public function test_the_logo_is_printed_onto_the_front_in_its_perspective(): void
    {
        foreach (array_keys(Box::SIZES) as $size) {
            $box = new Box(size: $size, motif: 'siegel');
            $svg = $box->render();
            $print = $box->printBox;

            // The print layer is turned into place with the front's own matrix.
            $this->assertStringContainsString('<g x-data="motifLayer" transform="'.$box->matrix('front').'">', $svg, $size);
            $this->assertStringContainsString(sprintf('<svg x="%s" y="%s" width="%s" height="%s" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet"', $print['x'], $print['y'], $print['width'], $print['height']), $svg, $size);

            // A square logo, whole and centred on the front, clear of its edges.
            $this->assertSame($print['width'], $print['height'], $size);
            $this->assertGreaterThan(0.0, $print['x'], $size);
            $this->assertGreaterThan(0.0, $print['y'], $size);
            $this->assertEqualsWithDelta($box->length, 2 * $print['x'] + $print['width'], 0.02, $size);
            $this->assertEqualsWithDelta($box->height, 2 * $print['y'] + $print['height'], 0.02, $size);
            $this->assertGreaterThan(0.7 * $box->height, $print['height'], "{$size}: the logo is large enough to see");

            // The front leans back into the picture: its far end is higher.
            [$a, $b, $c, $d] = $box->faces['front'];
            $this->assertGreaterThan(0.0, $b, $size);
            $this->assertSame(0.0, $c, $size);
            $this->assertLessThan(1.0, $a, $size);
            $this->assertLessThan(1.0, $d, $size);
        }
    }

    public function test_the_faces_meet_at_their_corners(): void
    {
        foreach (array_keys(Box::SIZES) as $size) {
            $box = new Box(size: $size);

            // Each face's own corners, turned into place, land on the box's corners.
            foreach ([
                ['front', 0, 0, 'left-top'],
                ['front', $box->length, 0, 'near-top'],
                ['front', 0, $box->height, 'left-bottom'],
                ['front', $box->length, $box->height, 'near-bottom'],
                ['side', 0, 0, 'near-top'],
                ['side', $box->depth, 0, 'back-top'],
                ['side', $box->depth, $box->height, 'back-bottom'],
                ['top', $box->length, 0, 'near-top'],
                ['top', $box->length, $box->depth, 'back-top'],
                ['top', 0, $box->depth, 'far-top'],
            ] as [$face, $u, $v, $corner]) {
                [$a, $b, $c, $d, $e, $f] = $box->faces[$face];

                $this->assertEqualsWithDelta($box->corners[$corner][0], $a * $u + $c * $v + $e, 0.05, "{$size}: {$face} at {$corner}");
                $this->assertEqualsWithDelta($box->corners[$corner][1], $b * $u + $d * $v + $f, 0.05, "{$size}: {$face} at {$corner}");
            }
        }
    }

    public function test_s_is_small_and_flat_and_l_big_and_deep(): void
    {
        $extent = [];

        foreach (array_keys(Box::SIZES) as $size) {
            $box = new Box(size: $size);
            $xs = array_column($box->corners, 0);
            $ys = array_column($box->corners, 1);
            $extent[$size] = ['width' => max($xs) - min($xs), 'height' => max($ys) - min($ys), 'flat' => $box->height / $box->length, 'deep' => $box->depth / $box->length];

            // Inside the card, with room below for the shadow.
            $this->assertGreaterThanOrEqual(9.0, min($xs), $size);
            $this->assertLessThanOrEqual(111.0, max($xs), $size);
            $this->assertGreaterThanOrEqual(9.0, min($ys), $size);
            $this->assertLessThanOrEqual(104.0, max($ys), $size);
        }

        foreach (['width', 'height', 'flat', 'deep'] as $measure) {
            $this->assertLessThan($extent['m'][$measure], $extent['s'][$measure], $measure);
            $this->assertLessThan($extent['l'][$measure], $extent['m'][$measure], $measure);
        }

        $this->assertGreaterThan(0.68, $extent['s']['width'] / 120, 'S is still well visible.');
        $this->assertGreaterThan(0.8, $extent['l']['width'] / 120, 'L nearly fills the card.');
        $this->assertLessThan(0.75 * $extent['l']['height'], $extent['s']['height'], 'S is clearly flatter.');
    }

    public function test_the_logo_is_printed_straight_onto_brown_and_white_board_in_dark_ink(): void
    {
        // The sample "siegel" brings a cream background of its own; on board it is left out.
        $background = '<rect width="100" height="100" fill="#f1ebdd" />';

        foreach (Box::COLORS as $color) {
            $this->assertSame(['bare' => true, 'ink' => 'dark'], (new Box(color: $color))->motifVars(), $color);
            $this->assertStringNotContainsString($background, stiqon_mockup('box', ['color' => $color, 'motif' => 'siegel']), $color);

            // Light lettering would fade into kraft and vanish on white: the star's is dark.
            $this->assertStringContainsString('fill="#1d1d1b">Top!', stiqon_mockup('box', ['color' => $color, 'motif' => 'stern']), $color);
        }

        // Kraft board has fibres, white board has none; the visitor's motif gets no backing on either.
        $this->assertStringContainsString('-fibre)', stiqon_mockup('box', ['color' => 'kraft']));
        $this->assertStringNotContainsString('-fibre', stiqon_mockup('box', ['color' => 'white']));

        foreach (Box::COLORS as $color) {
            $svg = stiqon_mockup('box', ['color' => $color]);

            $this->assertSame(0, preg_match('/<g x-show="active" style="display:none">\s*<rect/', $svg), $color);
        }
    }

    public function test_tape_seals_the_lid_on_every_size(): void
    {
        foreach (array_keys(Box::SIZES) as $size) {
            $box = new Box(size: $size);
            $svg = $box->render();

            // Over the seam in the middle of the lid, and on down the side.
            $this->assertStringContainsString('<rect y="'.($box->depth / 2 - Box::TAPE / 2).'" width="'.$box->length.'" height="'.Box::TAPE.'"', $svg, $size);
            $this->assertMatchesRegularExpression('/<path d="M'.preg_quote((string) ($box->depth / 2 - Box::TAPE / 2), '/').' 0H[^"]+z" fill="#a8804f"/', $svg, $size);
        }
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertSame('Versandkarton', Box::label());
        $this->assertStringContainsString('aria-label="Versandkarton S, braun"', stiqon_mockup('box'));
        $this->assertStringContainsString('aria-label="Versandkarton M, weiß"', stiqon_mockup('box', ['size' => 'm', 'color' => 'white']));
        $this->assertStringContainsString('aria-label="Versandkarton L, braun"', stiqon_mockup('box', ['size' => 'l', 'color' => 'kraft']));
        $this->assertStringContainsString('aria-label="Kartons für den Versand"', stiqon_mockup('box', ['label' => 'Kartons für den Versand']));
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
            'size' => [static fn () => new Box(size: 'xl'), 'Unknown box size [xl].'],
            'size in capitals' => [static fn () => Box::fromArray(['size' => 'M']), 'Unknown box size [M].'],
            'size by its real measure' => [static fn () => Box::fromArray(['size' => '35 × 25 × 15 cm']), 'Unknown box size [35 × 25 × 15 cm].'],
            'motif' => [static fn () => new Box(motif: 'gibt-es-nicht'), 'Unknown box motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Box(motif: '../mockup/flyer'), 'Unknown box motif'],
            'flyer motif' => [static fn () => Box::fromArray(['motif' => 'fest']), 'Unknown box motif [fest].'],
            'color' => [static fn () => new Box(color: 'gold'), 'Unknown box color [gold].'],
            'color in German' => [static fn () => Box::fromArray(['color' => 'braun']), 'Unknown box color [braun].'],
        ];
    }

    public function test_without_values_the_first_size_motif_and_colour_are_drawn(): void
    {
        $box = Box::fromArray([]);

        $this->assertSame(array_key_first(Box::form()['values']), $box->size);
        $this->assertSame('s', $box->size);
        $this->assertSame(Box::motifs()[0], $box->motif);
        $this->assertSame(Box::COLORS[0], $box->color);
        $this->assertNull($box->label);
        $this->assertSame((new Box())->size, $box->size);
        $this->assertSame((new Box())->motif, $box->motif);
        $this->assertSame((new Box())->color, $box->color);
    }

    public function test_values_of_other_kinds_are_ignored(): void
    {
        $box = Box::fromArray(['shape' => 'dreieck', 'material' => 'gold', 'format' => 'a3', 'handles' => 'long', 'stack' => true, 'gloss' => true, 'size' => 'l', 'color' => 'white']);

        $this->assertSame('l', $box->size);
        $this->assertSame('white', $box->color);
        $this->assertSame(Box::motifs()[0], $box->motif);
        $this->assertWellFormed($box->render(), 'foreign keys');
    }

    public function test_ids_stay_unique_and_references_resolve_when_boxes_share_a_page(): void
    {
        $drawings = [
            stiqon_mockup('box', ['motif' => 'siegel']),
            stiqon_mockup('box', ['size' => 'm', 'color' => 'white', 'motif' => 'siegel']),
            stiqon_mockup('box', ['size' => 'l', 'motif' => 'wellen']),
        ];

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'bx', 3);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Box::keys();
        $form = Box::form();

        $this->assertSame(['size', 'motif', 'color'], array_keys($keys));
        $this->assertSame('size', $form['key']);
        $this->assertSame('Größe', $form['label']);
        $this->assertSame(['s' => 'S', 'm' => 'M', 'l' => 'L'], $form['values']);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Box::motifs(), $keys['motif']);
        $this->assertSame(Sticker::motifs(), Box::motifs());
        $this->assertSame(['kraft', 'white'], $keys['color']);

        $names = Box::names();
        $this->assertSame(array_keys($form['values']), array_keys($names['form']));
        $this->assertSame(['size', 'color'], array_keys($names['options']));
        $this->assertSame(array_keys($form['values']), array_keys($names['options']['size']));
        $this->assertEqualsCanonicalizing($keys['color'], array_keys($names['options']['color']));
        $this->assertSame([], $names['switches']);
    }

    public function test_names_say_a_product_is_a_box_how_large_and_what_colour(): void
    {
        foreach (['Versandkartons', 'Faltschachteln', 'Kartons', 'Versandkarton', 'Geschenkboxen'] as $name) {
            $this->assertSame('box', Guess::type($name), $name);
        }

        $this->assertSame('box', Guess::type('Größe M', 'Versandkartons'));

        $this->assertSame(['size' => 's'], Guess::product('box', 'Versandkartons S'));
        $this->assertSame(['size' => 'm'], Guess::product('box', 'Versandkarton M'));
        $this->assertSame(['size' => 'l'], Guess::product('box', 'Versandkartons L'));
        $this->assertSame(['size' => 's'], Guess::product('box', 'Faltschachteln klein'));
        $this->assertSame(['size' => 'm'], Guess::product('box', 'Kartons mittel'));
        $this->assertSame(['size' => 'l'], Guess::product('box', 'Versandkartons groß'));
        $this->assertSame([], Guess::product('box', 'Versandkartons'));
        $this->assertSame([], Guess::product('box', 'Kartons'));

        $this->assertSame(['size' => 's'], Guess::option('box', 'S (25 × 18 × 10 cm)'));
        $this->assertSame(['size' => 'm'], Guess::option('box', 'M (35 × 25 × 15 cm)'));
        $this->assertSame(['size' => 'l'], Guess::option('box', 'L (45 × 35 × 20 cm)'));
        $this->assertSame(['color' => 'kraft'], Guess::option('box', 'Braun'));
        $this->assertSame(['color' => 'kraft'], Guess::option('box', 'Kraftkarton'));
        $this->assertSame(['color' => 'kraft'], Guess::option('box', 'Natur'));
        $this->assertSame(['color' => 'white'], Guess::option('box', 'Weiß'));
        $this->assertSame(['color' => 'white'], Guess::option('box', 'Weiß, innen braun'));
        $this->assertSame([], Guess::option('box', '100 Stück'));
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        $resolved = Spec::resolve(['name' => 'Versandkartons'], [['name' => 'M (35 × 25 × 15 cm)'], ['name' => 'Weiß']]);

        $this->assertSame('box', $resolved['type']);
        $this->assertSame('m', $resolved['spec']['size']);
        $this->assertSame('white', $resolved['spec']['color']);
        $this->assertContains($resolved['spec']['motif'], Box::motifs());
        $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), 'resolved');

        foreach (['Versandkartons S', 'Faltschachteln', 'Kartons groß'] as $name) {
            $resolved = Spec::resolve(['name' => $name], [['name' => 'Braun'], ['name' => '250 Stück']]);

            $this->assertSame('box', $resolved['type'], $name);
            $this->assertSame('kraft', $resolved['spec']['color'], $name);
            $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), $name);
        }
    }

    public function test_text_from_outside_cannot_break_out_of_an_attribute(): void
    {
        $hostile = 'A "B" <c> & \'d\'';
        $escaped = 'A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;';

        foreach (['label' => stiqon_mockup('box', ['label' => $hostile]), 'class' => stiqon_mockup('box', ['size' => 'l'], $hostile)] as $what => $markup) {
            $this->assertStringContainsString($escaped, $markup, $what);
            $this->assertStringNotContainsString('<c>', $markup, $what);
        }
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b[^"]*\blift\b/', stiqon_mockup('box'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('box', [], 'w-40'));
        $this->assertStringContainsString('viewBox="0 0 120 120"', stiqon_mockup('box'));
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
