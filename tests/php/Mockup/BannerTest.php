<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Banner;
use Stiqon\Shop\Mockup\Uid;
use Stiqon\Shop\Template;

/**
 * A PVC banner is hemmed, has an eyelet every 50 cm and is tied at its
 * corners. Every format is 100 cm tall and drawn the same width, so a longer
 * banner shows as a narrower strip.
 */
final class BannerTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_every_format(): void
    {
        $this->assertGreaterThanOrEqual(2, count(Banner::motifs()));
        $this->assertLessThanOrEqual(4, count(Banner::motifs()));

        foreach (Banner::motifs() as $motif) {
            foreach (array_keys(Banner::form()['values']) as $format) {
                $svg = stiqon_mockup('banner', compact('format', 'motif'));

                $this->assertWellFormed($svg, "{$motif} as {$format}");
                $this->assertStringContainsString('data-mockup="banner"', $svg);
                $this->assertStringContainsString('data-motif-image', $svg);
                $this->assertStringContainsString('data-motif-sample', $svg);
            }
        }
    }

    public function test_the_motif_fills_the_banner_up_to_its_drawn_in_edges(): void
    {
        $banner = new Banner(format: '300x100', motif: 'messe');
        $svg = $banner->render();
        $box = sprintf('x="%s" y="%s" width="%s" height="%s"', $banner->sheet['x'], $banner->sheet['y'], $banner->sheet['width'], $banner->sheet['height']);

        $this->assertStringContainsString('<g x-data="motifLayer" clip-path="url(#bn1-print)">', $svg);
        $this->assertStringContainsString('<clipPath id="bn1-print">', $svg);
        $this->assertStringContainsString('<path d="'.$banner->outline().'" />', $svg);
        $this->assertStringContainsString('<svg '.$box.' viewBox="0 0 300 100" preserveAspectRatio="xMidYMid slice"', $svg);
        $this->assertStringContainsString('<rect '.$box.' fill="#fff" />', $svg, 'White PVC under the visitor\'s motif.');
        $this->assertStringContainsString('<image '.$box.' preserveAspectRatio="xMidYMid slice"', $svg);
        $this->assertStringContainsString('>12.–14. März</text>', $svg);
    }

    public function test_a_longer_banner_is_drawn_as_a_narrower_strip_of_the_same_width(): void
    {
        $ratios = ['200x100' => 2, '300x100' => 3, '400x100' => 4];

        foreach ($ratios as $format => $ratio) {
            $sheet = (new Banner(format: $format))->sheet;

            $this->assertEqualsWithDelta($ratio, $sheet['width'] / $sheet['height'], 0.01, $format);
            $this->assertSame((new Banner())->sheet['width'], $sheet['width'], $format);
            $this->assertEqualsWithDelta(60.0, $sheet['x'] + $sheet['width'] / 2, 0.01, "{$format}: centred");
            $this->assertGreaterThanOrEqual(10.0, $sheet['x'], $format);
            $this->assertLessThanOrEqual(110.0, $sheet['x'] + $sheet['width'], $format);
            // A little above the middle, with room for the shadow below.
            $this->assertLessThan(60.0, $sheet['y'] + $sheet['height'] / 2, $format);
            $this->assertLessThanOrEqual(90.0, $sheet['y'] + $sheet['height'], $format);
        }
    }

    public function test_an_eyelet_sits_in_the_hem_every_50_cm(): void
    {
        foreach (['200x100' => 12, '300x100' => 16, '400x100' => 20] as $format => $count) {
            $banner = new Banner(format: $format);
            ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height] = $banner->sheet;

            $this->assertCount($count, $banner->eyelets, $format);
            $this->assertCount($count, array_unique(array_map('json_encode', $banner->eyelets)), "{$format}: no eyelet twice");
            $this->assertSame($count, substr_count($banner->render(), 'r="1.15"'), "{$format}: each one drawn");

            foreach ($banner->eyelets as [$ex, $ey]) {
                $fromEdge = min($ex - $x, $x + $width - $ex, $ey - $y, $y + $height - $ey);

                $this->assertGreaterThanOrEqual(1.5, $fromEdge, "{$format}: inside the banner");
                $this->assertLessThanOrEqual(3.2, $fromEdge, "{$format}: in the hem");
            }

            // Evenly spaced along the top edge.
            $top = array_values(array_filter($banner->eyelets, static fn (array $eyelet): bool => $eyelet[1] < $y + $height / 2 && $eyelet[0] > $x + 3 && $eyelet[0] < $x + $width - 3));
            $gaps = [];

            for ($index = 1; $index < count($top); $index++) {
                $gaps[] = round($top[$index][0] - $top[$index - 1][0], 1);
            }

            $this->assertCount(1, array_unique($gaps), "{$format}: evenly spaced");
        }
    }

    public function test_ropes_pull_the_corners_out_towards_a_frame_outside_the_picture(): void
    {
        foreach (array_keys(Banner::FORMATS) as $format) {
            $banner = new Banner(format: $format);
            ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height] = $banner->sheet;
            $svg = $banner->render();

            $this->assertCount(4, $banner->ropes, $format);

            foreach ($banner->ropes as [$start, $end]) {
                $this->assertContains($start, $banner->eyelets, "{$format}: through an eyelet");
                $this->assertTrue($end[0] < $x || $end[0] > $x + $width, "{$format}: out to the side");
                $this->assertTrue($end[1] < $y || $end[1] > $y + $height, "{$format}: and up or down");
                $this->assertGreaterThanOrEqual(6.0, $end[0], $format);
                $this->assertLessThanOrEqual(114.0, $end[0], $format);
            }

            // The ropes fade out on their way to the frame.
            $this->assertStringContainsString('<g mask="url(#'.$banner->uid.'-ropes)"', $svg, $format);
        }
    }

    public function test_the_edges_are_drawn_in_between_the_corners(): void
    {
        $banner = new Banner();
        ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height] = $banner->sheet;

        $this->assertStringStartsWith("M{$x} {$y}Q", $banner->outline());
        $this->assertMatchesRegularExpression('/^M[\d.]+ [\d.]+Q[\d.]+ ([\d.]+) /', $banner->outline());
        preg_match('/^M[\d.]+ [\d.]+Q[\d.]+ ([\d.]+) /', $banner->outline(), $control);
        $this->assertGreaterThan($y, (float) $control[1], 'The top edge bends inwards.');

        // The hem follows the outline inside.
        $this->assertStringStartsWith('M'.($x + 3.5).' '.($y + 3.5).'Q', $banner->outline(3.5));
        $this->assertStringContainsString('<path d="'.$banner->outline(3.5).'" fill="none"', $banner->render());
    }

    public function test_the_sample_motifs_are_a_set_of_their_own(): void
    {
        $others = array_merge(Template::names('motif'), Template::names('motif/flyer'));

        foreach (Banner::motifs() as $motif) {
            $this->assertMatchesRegularExpression('/\A[a-z]+\z/', $motif);
            $this->assertNotContains($motif, $others, "{$motif} repeats another motif's name.");
            $this->assertStringContainsString('300 x 100 box', (string) file_get_contents(dirname(__DIR__, 3).'/wp-content/plugins/stiqon-shop/templates/motif/banner/'.$motif.'.php'));
        }

        $this->assertSame(Template::names('motif/banner'), Banner::motifs());
        $this->assertSame('angebot', Banner::motifs()[0]);
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertSame('Banner', Banner::label());
        $this->assertStringContainsString('aria-label="Banner 200 × 100 cm"', stiqon_mockup('banner'));
        $this->assertStringContainsString('aria-label="Banner 300 × 100 cm"', stiqon_mockup('banner', ['format' => '300x100']));
        $this->assertStringContainsString('aria-label="Banner 400 × 100 cm"', stiqon_mockup('banner', ['format' => '400x100']));
        $this->assertStringContainsString('aria-label="Banner für den Bauzaun"', stiqon_mockup('banner', ['label' => 'Banner für den Bauzaun']));
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
            'format' => [static fn () => new Banner(format: '500x100'), 'Unknown banner format [500x100].'],
            'format the other way round' => [static fn () => Banner::fromArray(['format' => '100x200']), 'Unknown banner format [100x200].'],
            'motif' => [static fn () => new Banner(motif: 'gibt-es-nicht'), 'Unknown banner motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Banner(motif: '../mockup/flyer'), 'Unknown banner motif'],
            'flyer motif' => [static fn () => Banner::fromArray(['motif' => 'fest']), 'Unknown banner motif [fest].'],
            'sticker motif' => [static fn () => Banner::fromArray(['motif' => 'siegel']), 'Unknown banner motif [siegel].'],
        ];
    }

    public function test_without_values_the_first_format_and_motif_are_drawn(): void
    {
        $banner = Banner::fromArray([]);

        $this->assertSame(array_key_first(Banner::form()['values']), $banner->format);
        $this->assertSame('200x100', $banner->format);
        $this->assertSame(Banner::motifs()[0], $banner->motif);
        $this->assertNull($banner->label);
        $this->assertSame((new Banner())->format, $banner->format);
        $this->assertSame((new Banner())->motif, $banner->motif);
    }

    public function test_values_of_other_kinds_are_ignored(): void
    {
        $banner = Banner::fromArray(['shape' => 'dreieck', 'material' => 'gold', 'model' => 'premium', 'stack' => true, 'gloss' => true, 'motif' => 'baustelle']);

        $this->assertSame('200x100', $banner->format);
        $this->assertSame('baustelle', $banner->motif);
        $this->assertWellFormed($banner->render(), 'foreign keys');
    }

    public function test_ids_stay_unique_and_references_resolve_when_banners_share_a_page(): void
    {
        $drawings = [
            stiqon_mockup('banner'),
            stiqon_mockup('banner', ['format' => '300x100', 'motif' => 'baustelle']),
            stiqon_mockup('banner', ['format' => '400x100', 'motif' => 'messe']),
        ];

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'bn', 3);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Banner::keys();
        $form = Banner::form();

        $this->assertSame(['format', 'motif'], array_keys($keys));
        $this->assertSame('format', $form['key']);
        $this->assertSame('Format', $form['label']);
        $this->assertSame(['200x100' => '200 × 100 cm', '300x100' => '300 × 100 cm', '400x100' => '400 × 100 cm'], $form['values']);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Banner::motifs(), $keys['motif']);

        $names = Banner::names();
        $this->assertSame(array_keys($form['values']), array_keys($names['form']));
        $this->assertSame(['format'], array_keys($names['options']));
        $this->assertSame($names['form'], $names['options']['format']);
        $this->assertSame([], $names['switches']);
    }

    public function test_names_say_a_product_is_a_banner_and_how_long_it_is(): void
    {
        foreach (['PVC-Banner', 'Bauzaunbanner', 'Mesh-Banner', 'Werbeplane', 'Werbeplanen', 'Banner'] as $name) {
            $this->assertSame('banner', Guess::type($name), $name);
        }

        $this->assertSame('banner', Guess::type('300 × 100 cm', 'Banner'));
        $this->assertNotSame('banner', Guess::type('Wandplaner'));

        $this->assertSame(['format' => '200x100'], Guess::product('banner', 'PVC-Banner 200 x 100 cm'));
        $this->assertSame(['format' => '300x100'], Guess::product('banner', 'Bauzaunbanner 300x100'));
        $this->assertSame(['format' => '400x100'], Guess::product('banner', 'Werbeplane 400 × 100 cm'));
        $this->assertSame([], Guess::product('banner', 'Mesh-Banner'));

        $this->assertSame(['format' => '200x100'], Guess::option('banner', '200 × 100 cm'));
        $this->assertSame(['format' => '300x100'], Guess::option('banner', '300 × 100 cm'));
        $this->assertSame(['format' => '400x100'], Guess::option('banner', '400 × 100 cm'));
        $this->assertSame([], Guess::option('banner', '5 Stück'));
        $this->assertSame([], Guess::option('banner', 'Ösen alle 50 cm'));
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        $resolved = Spec::resolve(['name' => 'PVC-Banner'], [['name' => '400 × 100 cm'], ['name' => '2 Stück']]);

        $this->assertSame('banner', $resolved['type']);
        $this->assertSame('400x100', $resolved['spec']['format']);
        $this->assertContains($resolved['spec']['motif'], Banner::motifs());
        $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), 'resolved');
    }

    public function test_the_drawing_itself_carries_no_lettering_styles_or_scripts(): void
    {
        foreach (array_keys(Banner::FORMATS) as $format) {
            $drawing = $this->withoutSample(stiqon_mockup('banner', ['format' => $format]));

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

        foreach (['label' => stiqon_mockup('banner', ['label' => $hostile]), 'class' => stiqon_mockup('banner', ['format' => '400x100'], $hostile)] as $what => $markup) {
            $this->assertStringContainsString($escaped, $markup, $what);
            $this->assertStringNotContainsString('<c>', $markup, $what);
        }
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b[^"]*\blift\b/', stiqon_mockup('banner'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('banner', [], 'md:w-1/2'));
        $this->assertStringContainsString('viewBox="0 0 120 120"', stiqon_mockup('banner'));
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
