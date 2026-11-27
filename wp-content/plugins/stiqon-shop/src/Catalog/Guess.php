<?php

namespace Stiqon\Shop\Catalog;

use Stiqon\Shop\Mockup\Flyer;
use Stiqon\Shop\Mockup\Kinds;
use Stiqon\Shop\Mockup\Sticker;

/**
 * Reads from names what a product looks like.
 *
 * Products and their options arrive from elsewhere – typed in by the owner or
 * imported by a supplier's plugin – and say nothing about how to draw them. Most
 * of the time the names do: "Runde Sticker", "Transparente Folie", "Flyer DIN
 * lang", "Tassen", "Schwarz". What cannot be read here is left out, and the
 * drawing falls back to its defaults; the owner can always set it by hand.
 *
 * Which words mean what is said by each kind of drawing (Mockup\Kind::names())
 * and, for the kind itself, by Mockup\Kinds.
 */
final class Guess
{
    /** Sample motifs that bring a cut line of their own. */
    private const CONTOUR_MOTIFS = ['sprechblase', 'stern'];

    /**
     * The kind of product ("sticker", "card", …), or null when none of the
     * texts says. Pass the product's name first, then the names of its
     * categories: the first text that names a kind decides, so "Tassen" in
     * the category "Werbeartikel" are mugs, and "Etiketten" in the category
     * "Sticker" are stickers.
     */
    public static function type(string ...$texts): ?string
    {
        foreach ($texts as $text) {
            $text = self::fold($text);

            foreach (Kinds::types() as $type) {
                if (self::has($text, Kinds::pattern($type))) {
                    return $type;
                }
            }
        }

        return null;
    }

    /**
     * What a product's name says about its drawing: the value of the kind's
     * form ("shape" for a sticker, "format" for a flyer …).
     *
     * @return array<string, string>
     */
    public static function product(string $type, string $name): array
    {
        $kind = Kinds::of($type);
        $form = self::first($kind::names()['form'], self::fold($name));

        return $form !== null ? [$kind::form()['key'] => $form] : [];
    }

    /**
     * What the name of a chosen option says: a material, a finish, a paper, a colour.
     *
     * @return array<string, string|bool>
     */
    public static function option(string $type, string $name): array
    {
        $name = self::fold($name);
        $kind = Kinds::of($type);
        $names = $kind::names();
        $says = [];

        // Paper from 250 g/m² is drawn as the stiffer sheet, by every kind that can draw one.
        if (array_key_exists('heavy', $kind::keys()) && preg_match('/(\d{2,3})\s?g(?![a-zäöü])/u', $name, $grams) === 1) {
            $says['heavy'] = (int) $grams[1] >= 250;
        }

        foreach ($names['options'] as $key => $patterns) {
            $value = self::first($patterns, $name);

            if ($value !== null) {
                $says[$key] = $value;
            }
        }

        foreach ($names['switches'] as $key => [$on, $off]) {
            if (self::has($name, $on)) {
                $says[$key] = true;
            } elseif (self::has($name, $off)) {
                $says[$key] = false;
            }
        }

        if ($type === 'sticker') {
            if (self::has($name, 'matt')) {
                $says['finish'] = 'matte';
            } elseif (self::has($name, 'gl[aä]nz|gloss')) {
                $says['finish'] = 'gloss';
            } elseif (($says['material'] ?? null) === 'kraft') {
                // Uncoated paper does not shine.
                $says['finish'] = 'matte';
            }
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
            $type === 'sticker' && $shape === 'contour' => self::CONTOUR_MOTIFS,
            $type === 'sticker' => array_values(array_diff(Sticker::motifs(), self::CONTOUR_MOTIFS)),
            default => Kinds::of($type)::motifs(),
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
                return (string) $value;
            }
        }

        return null;
    }
}
