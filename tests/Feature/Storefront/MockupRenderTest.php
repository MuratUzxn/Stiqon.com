<?php

namespace Tests\Feature\Storefront;

use App\View\Components\Mockup\Flyer;
use App\View\Components\Mockup\Sticker;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * The mockups are assembled from shape, material and motif. Every combination
 * the shop can ask for has to render, and many of them share one page.
 */
class MockupRenderTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function motifs(string $directory): array
    {
        return collect(File::files(resource_path('views/components/motif/'.$directory)))
            ->map(fn ($file) => $file->getBasename('.blade.php'))
            ->values()
            ->all();
    }

    public function test_every_sticker_motif_renders_on_every_shape_and_material(): void
    {
        $motifs = $this->motifs('');

        $this->assertNotEmpty($motifs);

        foreach ($motifs as $motif) {
            foreach (Sticker::SHAPES as $shape) {
                foreach (Sticker::MATERIALS as $material) {
                    $svg = Blade::render(
                        '<x-mockup.sticker :shape="$shape" :material="$material" :motif="$motif" :peel="true" />',
                        compact('shape', 'material', 'motif'),
                    );

                    $this->assertStringContainsString('<svg', $svg, "{$motif} on {$shape}/{$material}");
                    $this->assertStringContainsString('data-motif-image', $svg, "{$motif} on {$shape}/{$material}");
                }
            }
        }
    }

    public function test_every_flyer_motif_renders_in_every_format(): void
    {
        $motifs = $this->motifs('flyer');

        $this->assertNotEmpty($motifs);

        foreach ($motifs as $motif) {
            foreach (array_keys(Flyer::FORMATS) as $format) {
                foreach ([false, true] as $stack) {
                    $svg = Blade::render(
                        '<x-mockup.flyer :format="$format" :motif="$motif" :stack="$stack" :gloss="true" :heavy="true" />',
                        compact('format', 'motif', 'stack'),
                    );

                    $this->assertStringContainsString('<svg', $svg, "{$motif} as {$format}");
                    $this->assertStringContainsString('data-motif-image', $svg, "{$motif} as {$format}");
                }
            }
        }
    }

    public function test_ids_stay_unique_when_many_mockups_share_a_page(): void
    {
        $html = $this->get('/musterbogen')->assertOk()->getContent();

        preg_match_all('/\sid="([^"]+)"/', $html, $ids);

        $this->assertGreaterThan(40, count($ids[1]));
        $this->assertSame([], array_keys(array_filter(array_count_values($ids[1]), fn (int $count) => $count > 1)));
    }

    public function test_every_reference_to_an_id_points_to_an_element_of_the_page(): void
    {
        $html = $this->get('/musterbogen')->assertOk()->getContent();

        preg_match_all('/\sid="([^"]+)"/', $html, $ids);
        preg_match_all('/url\(#([^)]+)\)|href="#([^"]+)"/', $html, $references);

        $referenced = array_unique(array_filter(array_merge($references[1], $references[2])));

        $this->assertNotEmpty($referenced);
        $this->assertSame([], array_values(array_diff($referenced, $ids[1])));
    }

    public function test_a_mockup_describes_itself_for_screen_readers_when_labelled(): void
    {
        $svg = Blade::render('<x-mockup.sticker shape="circle" motif="siegel" label="Runder Sticker" />');

        $this->assertStringContainsString('role="img"', $svg);
        $this->assertStringContainsString('Runder Sticker', $svg);
    }

    public function test_an_unknown_shape_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Sticker(shape: 'dreieck');
    }
}
