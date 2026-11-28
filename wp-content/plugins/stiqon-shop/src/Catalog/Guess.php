<?php

namespace Stiqon\Shop\Catalog;

use Stiqon\Shop\Mockup\Flyer;
use Stiqon\Shop\Mockup\Sticker;

/**
 * Reads from names what a product looks like.
 *
 * Products and their options arrive from elsewhere – typed in by the owner or
 * imported by a supplier's plugin – and say nothing about how to draw them. Most
 * of the time the names do: "Runde Sticker", "Transparente Folie", "Flyer DIN
 * lang". What cannot be read here is left out, and the drawing falls back to its
 * defaults; the owner can always set it by hand.
 */
final class Guess
{
    /** Checked in this order: a rectangle "mit abgerundeten Ecken" is not round. */
    private const SHAPES = [
        'contour' => 'kontur|freiform|formgestanzt|die[ -]?cut',
        'oval' => 'oval|ellip',
        'rect' => 'rechteck|rectang',
        'square' => 'quadrat|square',
        'circle' => 'rund|kreis|round|circle',
    ];

    private const MATERIALS = [
        'holo' => 'holo|regenbogen|irisierend',
        'clear' => 'transparent|durchsichtig|glasklar|clear',
        'kraft' => 'kraft|naturpapier|graspapier',
        'vinyl' => 'vinyl|weiß|weiss|white',
    ];

    private const FORMATS = [
        'lang' => 'din ?lang|\bdl\b|lang',
        'quadrat' => 'quadrat|square',
        'a6' => '\ba ?6\b',
        'a5' => '\ba ?5\b',
        'a4' => '\ba ?4\b',
    ];

    /** Sample motifs that bring a cut line of their own. */
    private const CONTOUR_MOTIFS = ['sprechblase', 'stern'];

    /**
     * "sticker", "flyer", or null when none of the texts says. Pass the
     * product's name and the names of its categories.
     */
    public static function type(string ...$texts): ?string
    {
        $text = self::fold(implode(' ', $texts));

        return match (true) {
            self::has($text, 'sticker|aufkleber|etikett|label') => 'sticker',
            self::has($text, 'flyer|flugbl[aä]tt|handzettel') => 'flyer',
            default => null,
        };
    }

    /**
     * What a product's name says about its drawing.
     *
     * @return array{shape?: string, format?: string}
     */
    public static function product(string $type, string $name): array
    {
        $name = self::fold($name);

        if ($type === 'sticker') {
            $shape = self::first(self::SHAPES, $name);

            return $shape !== null ? ['shape' => $shape] : [];
        }

        $format = self::first(self::FORMATS, $name);

        return $format !== null ? ['format' => $format] : [];
    }

    /**
     * What the name of a chosen option says: a material, a finish, a paper.
     *
     * @return array<string, string|bool>
     */
    public static function option(string $type, string $name): array
    {
        $name = self::fold($name);
        $says = [];

        if ($type === 'sticker') {
            $material = self::first(self::MATERIALS, $name);

            if ($material !== null) {
                $says['material'] = $material;
            }

            if (self::has($name, 'matt')) {
                $says['finish'] = 'matte';
            } elseif (self::has($name, 'gl[aä]nz|gloss')) {
                $says['finish'] = 'gloss';
            } elseif ($material === 'kraft') {
                // Uncoated paper does not shine.
                $says['finish'] = 'matte';
            }

            return $says;
        }

        // Paper from 250 g/m² is drawn as the stiffer sheet.
        if (preg_match('/(\d{2,3})\s?g(?![a-zäöü])/u', $name, $grams) === 1) {
            $says['heavy'] = (int) $grams[1] >= 250;
        }

        if (self::has($name, 'gl[aä]nz|gloss')) {
            $says['gloss'] = true;
        } elseif (self::has($name, 'matt')) {
            $says['gloss'] = false;
        }

        return $says;
    }

    /**
     * A sample motif for a product that names none. The same name always gets
     * the same motif, so a page does not change between two visits.
     */
    public static function motif(string $type, string $name, ?string $shape = null): string
    {
        $motifs = match (true) {
            $type === 'flyer' => Flyer::motifs(),
            $shape === 'contour' => self::CONTOUR_MOTIFS,
            default => array_values(array_diff(Sticker::motifs(), self::CONTOUR_MOTIFS)),
        };

        return $motifs[crc32(self::fold($name)) % count($motifs)];
    }

    private static function fold(string $text): string
    {
        return mb_strtolower(trim($text), 'UTF-8');
    }

    private static function has(string $text, string $pattern): bool
    {
        // A word may continue after the match ("rund" in "Runde") but not start before
        // it: "abgerundet" is not "rund", "Mattlack" is "matt".
        return preg_match('/(?<![a-zäöüß])(?:'.$pattern.')/u', $text) === 1;
    }

    /**
     * @param  array<string, string>  $patterns
     */
    private static function first(array $patterns, string $text): ?string
    {
        foreach ($patterns as $value => $pattern) {
            if (self::has($text, $pattern)) {
                return $value;
            }
        }

        return null;
    }
}
