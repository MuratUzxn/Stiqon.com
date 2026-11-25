<?php
/**
 * Functions the theme's templates call. They return markup that is ready to print.
 */

use Stiqon\Shop\Mockup\Flyer;
use Stiqon\Shop\Mockup\Stage;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Plugin;

defined('ABSPATH') || exit;

/**
 * A sticker as inline SVG.
 *
 * @param  array{shape?: string, material?: string, finish?: string, motif?: string, peel?: bool, label?: ?string}  $spec
 * @param  string  $class  extra classes; a width class replaces the full-width default
 */
function stiqon_sticker(array $spec = [], string $class = ''): string
{
    return stiqon_shop_render(static fn (): string => Sticker::fromArray($spec)->render($class));
}

/**
 * A flyer as inline SVG.
 *
 * @param  array{format?: string, motif?: string, stack?: bool, heavy?: bool, gloss?: bool, label?: ?string}  $spec
 * @param  string  $class  extra classes; a width class replaces the full-width default
 */
function stiqon_flyer(array $spec = [], string $class = ''): string
{
    return stiqon_shop_render(static fn (): string => Flyer::fromArray($spec)->render($class));
}

/**
 * The card surface around a mockup.
 *
 * @param  string  $content  markup from stiqon_sticker() or stiqon_flyer()
 */
function stiqon_stage(string $content, string $tone = 'mist', string $class = '', string $ratio = 'square'): string
{
    return stiqon_shop_render(
        static fn (): string => Stage::render($content, $tone, $class, $ratio),
        // A card in the standard look still shows its content; an empty string would lose it.
        static fn (): string => Stage::render($content, class: $class),
    );
}

/**
 * Runs a renderer. A name the plugin does not know – a shape, a motif, a card
 * tone – is a mistake in a template, or data on a product that has gone stale
 * (theme and plugin are updated separately). While developing, that stops the
 * page so it is noticed. On a live shop the one view is left out, or replaced
 * by $fallback, and the reason goes to the error log.
 *
 * @param  callable(): string  $render
 * @param  (callable(): string)|null  $fallback
 */
function stiqon_shop_render(callable $render, ?callable $fallback = null): string
{
    static $reported = [];

    try {
        return $render();
    } catch (InvalidArgumentException $exception) {
        if (Plugin::strict()) {
            throw $exception;
        }

        // A gallery repeats the same mistake on every card; one line per page is enough.
        if (! isset($reported[$exception->getMessage()])) {
            $reported[$exception->getMessage()] = true;

            error_log('Stiqon Shop-Funktionen: '.$exception->getMessage());
        }

        return $fallback !== null ? $fallback() : '';
    }
}
