<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Mockup\Flyer;
use Stiqon\Shop\Mockup\Stage;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Mockup\Uid;

/**
 * The mockups are assembled from shape, material and motif. Every combination
 * the shop can ask for has to render, and many of them share one page.
 */
final class RenderTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_sticker_motif_renders_on_every_shape_and_material(): void
    {
        $this->assertGreaterThanOrEqual(12, count(Sticker::motifs()));

        foreach (Sticker::motifs() as $motif) {
            foreach (Sticker::SHAPES as $shape) {
                foreach (Sticker::MATERIALS as $material) {
                    foreach (Sticker::FINISHES as $finish) {
                        $svg = stiqon_sticker(compact('shape', 'material', 'finish', 'motif') + ['peel' => true]);

                        $this->assertWellFormed($svg, "{$motif} on {$shape}/{$material}/{$finish}");
                        $this->assertStringContainsString('data-motif-image', $svg);
                    }
                }
            }
        }
    }

    public function test_every_flyer_motif_renders_in_every_format(): void
    {
        $this->assertGreaterThanOrEqual(5, count(Flyer::motifs()));

        foreach (Flyer::motifs() as $motif) {
            foreach (array_keys(Flyer::FORMATS) as $format) {
                foreach ([false, true] as $stack) {
                    $svg = stiqon_flyer(compact('format', 'motif', 'stack') + ['heavy' => true, 'gloss' => true]);

                    $this->assertWellFormed($svg, "{$motif} as {$format}");
                    $this->assertStringContainsString('data-motif-image', $svg);
                }
            }
        }
    }

    public function test_ids_stay_unique_and_references_resolve_when_many_mockups_share_a_page(): void
    {
        $page = '';

        foreach (Sticker::motifs() as $motif) {
            $page .= stiqon_sticker(['shape' => 'square', 'material' => 'holo', 'motif' => $motif, 'peel' => true]);
            $page .= stiqon_sticker(['shape' => 'circle', 'material' => 'kraft', 'motif' => $motif]);
        }

        foreach (Flyer::motifs() as $motif) {
            $page .= stiqon_flyer(['motif' => $motif, 'gloss' => true]);
        }

        preg_match_all('/\sid="([^"]+)"/', $page, $ids);
        preg_match_all('/url\(#([^)]+)\)|href="#([^"]+)"/', $page, $references);

        $referenced = array_unique(array_filter(array_merge($references[1], $references[2])));

        $this->assertGreaterThan(60, count($ids[1]));
        $this->assertSame([], array_keys(array_filter(array_count_values($ids[1]), static fn (int $count): bool => $count > 1)), 'Ids repeat.');
        $this->assertNotEmpty($referenced);
        $this->assertSame([], array_values(array_diff($referenced, $ids[1])), 'References point nowhere.');
    }

    public function test_a_mockup_describes_itself_for_screen_readers(): void
    {
        $this->assertStringContainsString('aria-label="Runder Sticker, Kraftpapier"', stiqon_sticker(['material' => 'kraft']));
        $this->assertStringContainsString('aria-label="Flyer DIN lang, Stapel"', stiqon_flyer(['format' => 'lang', 'stack' => true]));
        $this->assertStringContainsString('aria-label="Ihr Logo &amp; mehr"', stiqon_sticker(['label' => 'Ihr Logo & mehr']));
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b/', stiqon_sticker());
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_sticker([], 'absolute md:w-[66%]'));
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b/', stiqon_flyer([], 'rotate-2'));
    }

    public function test_only_straight_cornered_stickers_show_a_peeled_corner(): void
    {
        $this->assertNotNull((new Sticker(shape: 'square', peel: true))->peelCorner());
        $this->assertNotNull((new Sticker(shape: 'rect', peel: true))->peelCorner());
        $this->assertNull((new Sticker(shape: 'circle', peel: true))->peelCorner());
        $this->assertNull((new Sticker(shape: 'square'))->peelCorner());
    }

    public function test_clear_film_prints_to_the_edge(): void
    {
        $white = new Sticker(shape: 'circle', material: 'vinyl');
        $clear = new Sticker(shape: 'circle', material: 'clear');

        $this->assertSame($clear->outline, $clear->printArea);
        $this->assertNotSame($white->outline, $white->printArea);
        $this->assertGreaterThan($white->printBox['width'], $clear->printBox['width']);
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
            'shape' => [static fn () => new Sticker(shape: 'dreieck')],
            'material' => [static fn () => new Sticker(material: 'gold')],
            'finish' => [static fn () => new Sticker(finish: 'satin')],
            'sticker motif' => [static fn () => new Sticker(motif: 'gibt-es-nicht')],
            'motif outside the directory' => [static fn () => new Sticker(motif: '../mockup/flyer')],
            'flyer motif used on a sticker' => [static fn () => new Sticker(motif: 'fest')],
            'format' => [static fn () => new Flyer(format: 'a3')],
            'flyer motif' => [static fn () => new Flyer(motif: 'siegel')],
            'stage tone' => [static fn () => Stage::render('', 'neon')],
        ];
    }

    public function test_the_stage_wraps_its_content(): void
    {
        $html = stiqon_stage('<svg></svg>', 'paper', 'shrink-0');

        $this->assertStringStartsWith('<div class="', $html);
        $this->assertStringContainsString('bg-paper', $html);
        $this->assertStringContainsString('shrink-0', $html);
        $this->assertStringContainsString('<svg></svg>', $html);
    }

    private function assertWellFormed(string $svg, string $what): void
    {
        $previous = libxml_use_internal_errors(true);
        // The markup is HTML, checked here with an XML parser: Alpine's attributes carry a
        // colon without declaring a namespace, and two attributes come without a value.
        $xml = preg_replace('/\sx-[a-z]+:[a-z-]+=/', ' data-x=', $svg);
        $xml = preg_replace('/\s(x-cloak|data-motif-image)(?=[\s\/>])/', ' $1=""', $xml);
        $document = simplexml_load_string($xml);
        $errors = array_map(static fn ($error): string => trim($error->message), libxml_get_errors());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($document, "Not well-formed: {$what} – ".implode('; ', $errors));
        $this->assertSame('svg', $document->getName(), $what);
    }
}
