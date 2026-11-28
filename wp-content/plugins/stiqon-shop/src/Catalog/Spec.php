<?php

namespace Stiqon\Shop\Catalog;

use Stiqon\Shop\Mockup\Flyer;
use Stiqon\Shop\Mockup\Sticker;

/**
 * How to draw a product with a given choice of options.
 *
 * Three sources, the later one winning: what the names suggest (Guess), what is
 * stored on the product, and what is stored on each chosen option. Whatever is
 * stored is checked against what the drawings know; a value they do not know is
 * ignored, so data that has gone stale never stops a page.
 *
 * This class knows nothing about WordPress. ProductView reads the product and
 * hands its names and stored values in here.
 */
final class Spec
{
    public const TYPES = ['sticker', 'flyer'];

    private const SWITCHES = ['peel', 'stack', 'heavy', 'gloss'];

    /**
     * @param  array{name: string, categories?: list<string>, stored?: array<string, mixed>}  $product
     * @param  list<array{name: string, stored?: array<string, mixed>}>  $options  the chosen options, in the product's order
     * @return array{type: string, spec: array<string, string|bool>}|null  null when nothing says what kind of product this is
     */
    public static function resolve(array $product, array $options = []): ?array
    {
        $stored = $product['stored'] ?? [];
        $type = in_array($stored['type'] ?? null, self::TYPES, true)
            ? $stored['type']
            : Guess::type($product['name'], ...($product['categories'] ?? []));

        if ($type === null) {
            return null;
        }

        $spec = array_merge(
            Guess::product($type, $product['name']),
            self::known($type, $stored),
        );

        foreach ($options as $option) {
            $spec = array_merge(
                $spec,
                Guess::option($type, $option['name']),
                self::known($type, $option['stored'] ?? []),
            );
        }

        $spec['motif'] ??= Guess::motif($type, $product['name'], $spec['shape'] ?? null);

        return ['type' => $type, 'spec' => $spec];
    }

    /**
     * The part of stored values the drawing of this type understands.
     *
     * @param  array<string, mixed>  $stored
     * @return array<string, string|bool>
     */
    public static function known(string $type, array $stored): array
    {
        $allowed = $type === 'sticker'
            ? ['shape' => Sticker::SHAPES, 'material' => Sticker::MATERIALS, 'finish' => Sticker::FINISHES, 'motif' => Sticker::motifs(), 'peel' => null]
            : ['format' => array_keys(Flyer::FORMATS), 'motif' => Flyer::motifs(), 'stack' => null, 'heavy' => null, 'gloss' => null];

        $known = [];

        foreach ($allowed as $key => $values) {
            if (! array_key_exists($key, $stored)) {
                continue;
            }

            if (in_array($key, self::SWITCHES, true)) {
                $switch = filter_var($stored[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($switch !== null) {
                    $known[$key] = $switch;
                }
            } elseif (is_string($stored[$key]) && in_array($stored[$key], $values, true)) {
                $known[$key] = $stored[$key];
            }
        }

        return $known;
    }
}
