<?php
/**
 * Functions the theme's templates call. They return markup that is ready to print.
 */

use Stiqon\Shop\Mockup\Flyer;
use Stiqon\Shop\Mockup\Stage;
use Stiqon\Shop\Mockup\Sticker;

defined('ABSPATH') || exit;

/**
 * A sticker as inline SVG.
 *
 * @param  array{shape?: string, material?: string, finish?: string, motif?: string, peel?: bool, label?: ?string}  $spec
 * @param  string  $class  extra classes; a width class replaces the full-width default
 */
function stiqon_sticker(array $spec = [], string $class = ''): string
{
    return Sticker::fromArray($spec)->render($class);
}

/**
 * A flyer as inline SVG.
 *
 * @param  array{format?: string, motif?: string, stack?: bool, heavy?: bool, gloss?: bool, label?: ?string}  $spec
 * @param  string  $class  extra classes; a width class replaces the full-width default
 */
function stiqon_flyer(array $spec = [], string $class = ''): string
{
    return Flyer::fromArray($spec)->render($class);
}

/**
 * The card surface around a mockup.
 *
 * @param  string  $content  markup from stiqon_sticker() or stiqon_flyer()
 */
function stiqon_stage(string $content, string $tone = 'mist', string $class = '', string $ratio = 'square'): string
{
    return Stage::render($content, $tone, $class, $ratio);
}
