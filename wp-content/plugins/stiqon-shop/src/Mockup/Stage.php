<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Html;

/**
 * The surface a mockup lies on: one card of the gallery grid.
 */
final class Stage
{
    /** Cards are one neutral grey; the mockups bring the colour. */
    public const TONES = [
        'mist' => 'bg-mist',
        'paper' => 'bg-paper',
        'night' => 'bg-night',
    ];

    public const RATIOS = [
        'square' => 'aspect-square',
        'portrait' => 'aspect-[4/5]',
        'landscape' => 'aspect-[5/4]',
    ];

    /**
     * @param  string  $content  markup of the mockup (already escaped where needed)
     */
    public static function render(string $content, string $tone = 'mist', string $class = '', string $ratio = 'square'): string
    {
        if (! isset(self::TONES[$tone])) {
            throw new InvalidArgumentException("Unknown stage tone [{$tone}].");
        }

        if (! isset(self::RATIOS[$ratio])) {
            throw new InvalidArgumentException("Unknown stage ratio [{$ratio}].");
        }

        $classes = Html::classes([
            'relative flex items-center justify-center overflow-hidden rounded-card',
            self::TONES[$tone],
            self::RATIOS[$ratio],
            $class,
        ]);

        return '<div class="'.esc_attr($classes).'">'.$content.'</div>';
    }
}
