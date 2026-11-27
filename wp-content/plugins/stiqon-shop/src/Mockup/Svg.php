<?php

namespace Stiqon\Shop\Mockup;

use Stiqon\Shop\Html;

/**
 * What every drawing's root element shares.
 */
final class Svg
{
    /**
     * Classes of a drawing's <svg>: a block of automatic height whose shadow
     * may spill over its box, full width unless the caller passes a width class
     * (w-…, md:w-…), and the soft shadow every product casts ("lift", or
     * "lift-faint" for something nearly transparent).
     */
    public static function classes(string $class, string $lift = 'lift'): string
    {
        $sized = (bool) preg_match('/(^|[\s:])w-/', $class);

        return Html::classes(['block h-auto overflow-visible', 'w-full' => ! $sized, $lift, $class]);
    }

    /**
     * A box for the printed surface (see templates/mockup/parts/motif.php).
     *
     * @return array{x: float, y: float, width: float, height: float}
     */
    public static function box(float $x, float $y, float $width, float $height): array
    {
        return ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height];
    }
}
