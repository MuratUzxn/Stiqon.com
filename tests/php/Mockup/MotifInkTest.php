<?php

namespace Stiqon\Shop\Tests\Mockup;

use ErrorException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Template;

/**
 * The sticker motifs printed bare – straight onto a material, without a
 * ground of their own – in the ink that suits it: "dark" for white and light
 * materials, "deep" for mid-tones such as kraft or grey cloth, "light" for
 * dark ones such as black or navy cotton. Without an ink they are drawn as
 * the stickers have always been.
 */
final class MotifInkTest extends TestCase
{
    /** Inks that vanish on black or navy. */
    private const DARK_INKS = ['#0f0f0e', '#141412', '#1d1d1b', '#1d3557', '#1f3d2e', '#2f4329', '#7a2e2e'];

    /** Inks that vanish on white. */
    private const WHITE_INKS = ['#fff', '#ffffff', '#f4efe6'];

    /** The dark materials a light print goes on: black and navy. */
    private const DARK_GROUNDS = ['#1f1f1f', '#1f2a44'];

    /** The mid-tones a deep print goes on: kraft board and paper, grey melange. */
    private const MID_GROUNDS = ['#c9a57c', '#b8babc'];

    private const GOLD = '#d9a441';

    /** Gold for kraft and grey cloth, which are about as light as gold itself. */
    private const BRONZE = '#7a5a1e';

    public function test_every_sticker_motif_renders_in_every_ink(): void
    {
        $this->assertGreaterThanOrEqual(12, count(Sticker::motifs()));

        foreach (Sticker::motifs() as $motif) {
            foreach ([true, false] as $bare) {
                foreach ([null, 'dark', 'deep', 'light'] as $ink) {
                    $this->assertWellFormed($this->svg($this->motif($motif, $bare, $ink)), "{$motif}, ".($bare ? 'bare' : 'on its ground').', ink '.var_export($ink, true));
                }
            }
        }
    }

    /**
     * Most drawings give no ink at all: the motif must not warn about it.
     */
    public function test_no_ink_draws_the_motif_as_without_one(): void
    {
        set_error_handler(static function (int $level, string $message): never {
            throw new ErrorException($message, 0, $level);
        });

        try {
            foreach (Sticker::motifs() as $motif) {
                foreach ([true, false] as $bare) {
                    $without = Template::render('motif/'.$motif, ['uid' => 'u7', 'bare' => $bare]);

                    $this->assertSame($without, $this->motif($motif, $bare, null), $motif);
                }
            }
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Inks come from the drawings only, but a mistake there must not break a page.
     */
    public function test_an_unknown_ink_is_no_ink(): void
    {
        foreach (Sticker::motifs() as $motif) {
            $plain = $this->motif($motif, true, null);

            foreach (['Light', 'DARK', 'Deep', 'white', '', 0, true, ['light']] as $ink) {
                $this->assertSame($plain, $this->motif($motif, true, $ink), "{$motif}: ".var_export($ink, true));
            }
        }
    }

    public function test_on_its_own_ground_a_motif_ignores_the_ink(): void
    {
        foreach (Sticker::motifs() as $motif) {
            $plain = $this->motif($motif, false, null);

            $this->assertSame($plain, $this->motif($motif, false, 'dark'), $motif);
            $this->assertSame($plain, $this->motif($motif, false, 'deep'), $motif);
            $this->assertSame($plain, $this->motif($motif, false, 'light'), $motif);
        }
    }

    public function test_light_ink_leaves_no_dark_ink_and_shows_on_black_and_navy(): void
    {
        foreach (Sticker::motifs() as $motif) {
            $inks = $this->inks($this->motif($motif, true, 'light'));

            $this->assertNotEmpty($inks, $motif);
            $this->assertSame([], array_values(array_intersect($inks, self::DARK_INKS)), "{$motif}: dark ink in a light print");

            foreach ($inks as $ink) {
                foreach (self::DARK_GROUNDS as $ground) {
                    $this->assertGreaterThanOrEqual(3.0, $this->contrast($ink, $ground), "{$motif}: {$ink} on {$ground}");
                }
            }
        }
    }

    public function test_dark_and_deep_ink_leave_no_white(): void
    {
        foreach (Sticker::motifs() as $motif) {
            foreach (['dark', 'deep'] as $print) {
                $inks = $this->inks($this->motif($motif, true, $print));

                $this->assertNotEmpty($inks, "{$motif}, {$print}");
                $this->assertSame([], array_values(array_intersect($inks, self::WHITE_INKS)), "{$motif}: white ink in a {$print} print");

                foreach ($inks as $ink) {
                    // Nothing near white either: cream, a pale tint of the motif's own ground.
                    $this->assertLessThan(0.6, $this->luminance($ink), "{$motif}, {$print}: {$ink}");
                }
            }
        }
    }

    /**
     * Kraft and grey cloth are about as light as gold: thin or small gold on
     * them all but vanishes. Deep ink is dark ink with that gold in bronze.
     * Gold on the motif's own dark mark stays, and so does a plate large
     * enough to show by its hue: a bronze one would bury its dark label.
     */
    public function test_deep_ink_is_dark_ink_with_the_gold_on_the_material_in_bronze(): void
    {
        foreach (Sticker::motifs() as $motif) {
            $this->assertSame($this->motif($motif, true, 'dark'), str_replace(self::BRONZE, self::GOLD, $this->motif($motif, true, 'deep')), $motif);
        }

        // The offer's rings, the burst and the sun.
        foreach (['aktion', 'neu', 'wellen'] as $motif) {
            $deep = $this->motif($motif, true, 'deep');

            $this->assertStringNotContainsString(self::GOLD, $deep, $motif);
            $this->assertStringContainsString('"'.self::BRONZE.'"', $deep, $motif);
        }

        // The dot on the mark and the plate under "Hallo".
        $this->assertStringContainsString('<circle cx="54.8" cy="38.2" r="3.4" fill="'.self::GOLD.'" />', $this->motif('punkt', true, 'deep'));
        $this->assertStringContainsString('rx="14" fill="'.self::GOLD.'"', $this->motif('raster', true, 'deep'));

        foreach (self::MID_GROUNDS as $ground) {
            $this->assertLessThan(1.3, $this->contrast(self::GOLD, $ground), "gold on {$ground}");
            $this->assertGreaterThanOrEqual(2.5, $this->contrast(self::BRONZE, $ground), "bronze on {$ground}");
        }
    }

    public function test_white_lettering_turns_dark_in_dark_and_deep_ink(): void
    {
        // "stern" is lettering only once printed bare: white for kraft and film.
        $this->assertStringContainsString('fill="#ffffff">Top!', $this->motif('stern', true, null));
        $this->assertStringContainsString('fill="#1d1d1b">Top!', $this->motif('stern', true, 'dark'));
        $this->assertStringContainsString('fill="#1d1d1b">Top!', $this->motif('stern', true, 'deep'));
        $this->assertStringContainsString('fill="#ffffff">Top!', $this->motif('stern', true, 'light'));
    }

    public function test_printed_in_an_ink_the_sun_is_a_half_one_over_its_stripes(): void
    {
        // On its own ground the ground hides the sun's lower half; printed without it, in
        // an ink, the half sun is drawn itself, so the stripes do not cross a whole disc.
        foreach (['dark', 'deep', 'light'] as $ink) {
            $svg = $this->motif('sonne', true, $ink);

            $this->assertStringContainsString('<path d="M26 58a24 24 0 0 1 48 0z" fill="#c8553d" />', $svg, $ink);
            $this->assertStringNotContainsString('<circle', $svg, $ink);
        }

        // Stickers keep the disc they were approved with.
        $this->assertStringContainsString('<circle cx="50" cy="58" r="24" fill="#c8553d" />', $this->motif('sonne', true, null));
        $this->assertStringContainsString('<circle cx="50" cy="58" r="24" fill="#c8553d" />', $this->motif('sonne', false, null));
    }

    public function test_in_light_ink_the_label_of_the_checkers_is_cut_out_of_its_plate(): void
    {
        $svg = $this->motif('raster', true, 'light');

        // Dark letters would vanish on black: the material itself shows through them.
        $this->assertStringContainsString('<mask id="u7-hallo">', $svg);
        $this->assertStringContainsString('fill="#d9a441" mask="url(#u7-hallo)"', $svg);
        $this->assertStringNotContainsString('<text', preg_replace('~<mask\b.*?</mask>~s', '', $svg));
        $this->assertStringContainsString('fill="#141412">Hallo', $this->motif('raster', true, 'dark'));
    }

    public function test_the_ids_a_motif_brings_are_its_own_in_every_ink(): void
    {
        foreach (Sticker::motifs() as $motif) {
            foreach ([null, 'dark', 'deep', 'light'] as $ink) {
                $svg = $this->motif($motif, true, $ink);
                preg_match_all('/\sid="([^"]+)"/', $svg, $ids);
                preg_match_all('/url\(#([^)]+)\)|href="#([^"]+)"/', $svg, $references);

                foreach ($ids[1] as $id) {
                    $this->assertStringStartsWith('u7-', $id, $motif);
                }

                $referenced = array_unique(array_filter(array_merge($references[1], $references[2])));
                $this->assertSame([], array_values(array_diff($referenced, $ids[1])), "{$motif}: references point nowhere");
            }
        }
    }

    private function motif(string $motif, bool $bare, mixed $ink): string
    {
        return Template::render('motif/'.$motif, ['uid' => 'u7', 'bare' => $bare, 'ink' => $ink]);
    }

    /** A motif on its own is a run of SVG elements; the root makes it a document. */
    private function svg(string $motif): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'.$motif.'</svg>';
    }

    /**
     * The colours a print is inked in, in lower case. A mask is no ink: black
     * in it cuts a shape out of the print.
     *
     * @return list<string>
     */
    private function inks(string $svg): array
    {
        $printed = preg_replace('~<mask\b.*?</mask>~s', '', $svg);
        preg_match_all('/\s(?:fill|stroke)="(#[0-9a-fA-F]{3,6})"/', $printed, $colours);

        return array_values(array_unique(array_map('strtolower', $colours[1])));
    }

    /** Relative luminance of an sRGB colour, as WCAG reckons it. */
    private function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        [$red, $green, $blue] = array_map(static function (string $channel): float {
            $value = hexdec($channel) / 255;

            return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split($hex, 2));

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }

    /** Contrast ratio of two colours, as WCAG reckons it: 1 to 21. */
    private function contrast(string $one, string $other): float
    {
        $luminances = [$this->luminance($one), $this->luminance($other)];

        return (max($luminances) + 0.05) / (min($luminances) + 0.05);
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
