<?php
/**
 * Functions the theme's templates call. They return markup that is ready to print.
 */

use Stiqon\Shop\Catalog\Gallery;
use Stiqon\Shop\Catalog\ProductView;
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
 * A product of the shop as inline SVG, with a choice of its options – a sticker
 * or a flyer, whichever the product is. Empty when nothing says what kind of
 * product it is.
 *
 * @param  array<string, string>  $chosen  attribute => chosen value, as in a request ("attribute_pa_material" => "kraftpapier")
 * @param  array{class?: string, label?: string, peel?: bool, stack?: bool}  $options  "label" names the drawing for screen readers
 */
function stiqon_product_view(WC_Product $product, array $chosen = [], array $options = []): string
{
    $resolved = ProductView::resolve($product, $chosen);

    if ($resolved === null) {
        return '';
    }

    $spec = array_intersect_key($options, ['peel' => true, 'stack' => true]) + $resolved['spec'];
    $spec['label'] = $options['label'] ?? implode(', ', array_filter([$product->get_name(), $resolved['options'][0] ?? null]));

    return $resolved['type'] === 'sticker'
        ? stiqon_sticker($spec, $options['class'] ?? '')
        : stiqon_flyer($spec, $options['class'] ?? '');
}

/**
 * The cards of an area of the shop ("sticker", "flyer"), with what its filters
 * offer and which of them the current request asks for. See Catalog\Gallery.
 *
 * @return array{products: list<array{slug: string, name: string}>, axis: ?array, cards: list<array>, selection: array{produkt: ?string, option: ?string}}
 */
function stiqon_gallery(string $category): array
{
    $gallery = Gallery::of($category);

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filters of a public page; values are only compared with known ones.
    return $gallery + ['selection' => Gallery::selection($gallery, wp_unslash($_GET))];
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
