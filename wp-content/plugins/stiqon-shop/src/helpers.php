<?php
/**
 * Functions the theme's templates call. They return markup that is ready to print.
 */

use Stiqon\Shop\Catalog\Choice;
use Stiqon\Shop\Catalog\Gallery;
use Stiqon\Shop\Catalog\ProductView;
use Stiqon\Shop\Content\References;
use Stiqon\Shop\Mockup\Flyer;
use Stiqon\Shop\Mockup\Stage;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Plugin;
use Stiqon\Shop\Search\Index;

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
    // Named after the product: a variation's own name may repeat its options.
    $spec['label'] = $options['label'] ?? implode(', ', array_filter([$resolved['name'], $resolved['options'][0] ?? null]));

    return $resolved['type'] === 'sticker'
        ? stiqon_sticker($spec, $options['class'] ?? '')
        : stiqon_flyer($spec, $options['class'] ?? '');
}

/**
 * A product drawn once for each value of the option a visitor can see in the
 * drawing (a sticker on each material), for a product page that switches
 * between them. A product that always looks the same comes back as one view.
 *
 * The shop draws the values that can be bought. With $ticked, every value set
 * on the product is drawn – for its editor, where variations may not exist yet.
 *
 * @return array{key: ?string, views: list<array{value: string, name: string, html: string}>}  "key" names the option as in a request ("attribute_pa_material")
 */
function stiqon_product_views(WC_Product $product, bool $ticked = false): array
{
    $visible = ProductView::visible($product, $ticked);

    if ($visible === null) {
        return ['key' => null, 'views' => [['value' => '', 'name' => '', 'html' => stiqon_product_view($product)]]];
    }

    $key = 'attribute_'.$visible['key'];
    $views = [];

    foreach ($visible['options'] as $option) {
        $views[] = [
            'value' => $option['slug'],
            'name' => $option['name'],
            'html' => stiqon_product_view($product, [$key => $option['slug']], ['label' => $product->get_name().', '.$option['name']]),
        ];
    }

    return ['key' => $key, 'views' => $views];
}

/**
 * Which options of a product the current request chooses – always a complete
 * choice, see Catalog\Choice – the variation that makes, and what the product
 * offers per option ("pa_material" => values that can be bought).
 *
 * @return array{chosen: array<string, string>, variation: ?WC_Product_Variation, offered: array<string, list<string>>}
 */
function stiqon_product_choice(WC_Product $product): array
{
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- options of a public page; values are only compared with what the product offers.
    $chosen = Choice::of($product, wp_unslash($_REQUEST));

    return ['chosen' => $chosen, 'variation' => Choice::variation($product, $chosen), 'offered' => Choice::offered($product)];
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
 * Other products of the same area, each as its first card – for "more of
 * these" on a product page. Cards as stiqon_gallery() returns them.
 *
 * @return list<array>
 */
function stiqon_products_beside(WC_Product $product, string $category, int $limit = 4): array
{
    return Gallery::beside($product, $category, $limit);
}

/**
 * What the shop's search finds for a query, best first: areas of the shop, and
 * cards as stiqon_gallery() returns them – a product, or a product with one of
 * its options chosen when that option was asked for. See Search\Index.
 *
 * @return array{query: string, total: int, areas: list<array{name: string, note: string, url: string}>, cards: list<array>}  "total" counts everything found, also beyond $limit
 */
function stiqon_search(string $query, int $limit = 48): array
{
    return Index::find($query, $limit);
}

/**
 * Address from which a page's script fetches what search looks through.
 */
function stiqon_search_index_url(): string
{
    return rest_url(Index::REST_NAMESPACE.Index::REST_ROUTE);
}

/**
 * The references the shop shows, in their order: work it has delivered. See
 * Content\References.
 *
 * @return list<array{id: int, title: string, text: string, image: int}>  "image": id of the photo in the media library, 0 without one
 */
function stiqon_references(int $limit = -1): array
{
    return References::all($limit);
}

/**
 * Address of the list of references – null while there is none to show, and
 * then the address does not exist either.
 */
function stiqon_references_url(): ?string
{
    return References::url();
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
