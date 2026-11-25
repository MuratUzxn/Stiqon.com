<?php
/**
 * The theme's building blocks: icon, button, chip, section heading, logo.
 *
 * Each function returns finished markup – everything variable is escaped
 * inside – so templates print the result as it is.
 */

defined('ABSPATH') || exit;

/**
 * A class attribute value from a list: plain entries are always used,
 * "class" => condition entries only when the condition holds.
 *
 * @param  array<int|string, string|bool|null>  $classes
 */
function stiqon_classes(array $classes): string
{
    $list = [];

    foreach ($classes as $key => $value) {
        if (is_int($key)) {
            if (is_string($value) && trim($value) !== '') {
                $list[] = trim($value);
            }
        } elseif ($value) {
            $list[] = $key;
        }
    }

    return implode(' ', $list);
}

/**
 * Attributes for a tag, with a leading space. true prints the bare name,
 * false and null leave the attribute out. A name that is not a plain attribute
 * name (it may carry Alpine's colons, dots and @) is left out as well: escaping
 * a name does not stop it from starting a second attribute.
 *
 * @param  array<string, string|int|bool|null>  $attributes
 */
function stiqon_attributes(array $attributes): string
{
    $html = '';

    foreach ($attributes as $name => $value) {
        if ($value === false || $value === null || preg_match('/\A[a-zA-Z_:@][a-zA-Z0-9_:@.-]*\z/', (string) $name) !== 1) {
            continue;
        }

        $html .= ' '.esc_attr($name).($value === true ? '' : '="'.esc_attr((string) $value).'"');
    }

    return $html;
}

/**
 * An icon from a small hand-picked set, drawn on a 24 px grid with round 1.75 px strokes.
 *
 * @param  array<string, string|int|bool|null>  $attributes
 */
function stiqon_icon(string $name, string $class = '', array $attributes = []): string
{
    $paths = [
        'arrow-right' => 'M5 12h14M13 6l6 6-6 6',
        'arrow-left' => 'M19 12H5M11 6l-6 6 6 6',
        'arrow-up-right' => 'M7 17 17 7M8 7h9v9',
        'upload' => 'M12 16V4M7 9l5-5 5 5M5 20h14',
        'image' => 'M4 5h16v14H4zM4 16l4.5-4.5 3.5 3.5 3-3 5 5M9 9.5h.01',
        'check' => 'M5 12.5l4.5 4.5L19 7.5',
        'menu' => 'M4 7h16M4 12h16M4 17h16',
        'x' => 'M6 6l12 12M18 6 6 18',
        'chevron-left' => 'M15 6l-6 6 6 6',
        'chevron-right' => 'M9 6l6 6-6 6',
        'chevron-down' => 'M6 9l6 6 6-6',
        'plus' => 'M12 5v14M5 12h14',
        'search' => 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4.05-4.05',
        'bag' => 'M6 8h12l1 12H5L6 8zM9 8V7a3 3 0 0 1 6 0v1',
    ];

    if (! isset($paths[$name])) {
        return '';
    }

    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"'
        .' aria-hidden="true" focusable="false" class="'.esc_attr(stiqon_classes(['size-5 shrink-0', $class])).'"'
        .stiqon_attributes($attributes).'><path d="'.esc_attr($paths[$name]).'" /></svg>';
}

/**
 * A pill button, or a link that looks like one when "href" is given.
 *
 * @param  string  $content  inner markup: escaped text, optionally with an icon
 * @param  array{variant?: string, size?: string, href?: ?string, type?: string, class?: string, attributes?: array<string, string|int|bool|null>}  $options  variant: primary, secondary, quiet, on-night, on-night-secondary; size: md (44 px) or sm (36 px)
 */
function stiqon_button(string $content, array $options = []): string
{
    // "aria-disabled" is for buttons that go out of use while they may hold the
    // keyboard focus: unlike "disabled" it does not throw the focus away.
    $base = 'text-action inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-full '
        .'transition-colors duration-200 ease-soft disabled:pointer-events-none disabled:opacity-50 '
        .'aria-disabled:pointer-events-none aria-disabled:opacity-50';

    $sizes = [
        'sm' => 'h-9 px-3.5 text-sm',
        'md' => 'h-11 px-4 text-base',
    ];

    // "secondary" is a translucent grey, so it reads as a button on white and on grey surfaces alike.
    $variants = [
        'primary' => 'bg-ink text-paper hover:bg-ink-soft',
        'secondary' => 'bg-ink/8 text-ink hover:bg-ink/14',
        'quiet' => 'text-ink hover:bg-ink/8',
        'on-night' => 'bg-paper text-ink hover:bg-mist-deep',
        'on-night-secondary' => 'bg-white/14 text-on-night hover:bg-white/22',
    ];

    $classes = stiqon_classes([
        $base,
        $sizes[$options['size'] ?? 'md'] ?? $sizes['md'],
        $variants[$options['variant'] ?? 'primary'] ?? $variants['primary'],
        $options['class'] ?? '',
    ]);

    $attributes = stiqon_attributes($options['attributes'] ?? []);

    if (! empty($options['href'])) {
        return '<a href="'.esc_url($options['href']).'" class="'.esc_attr($classes).'"'.$attributes.'>'.$content.'</a>';
    }

    return '<button type="'.esc_attr($options['type'] ?? 'button').'" class="'.esc_attr($classes).'"'.$attributes.'>'.$content.'</button>';
}

/**
 * A filter chip: a link when "href" is given, otherwise a toggle button. Whether
 * it is the chosen one is said by aria-current (or aria-pressed) alone, and the
 * look follows that attribute – so a script only has to change the attribute.
 *
 * @param  array{active?: bool, href?: ?string, class?: string, attributes?: array<string, string|int|bool|null>}  $options
 */
function stiqon_chip(string $label, array $options = []): string
{
    $active = ! empty($options['active']);
    $link = ! empty($options['href']);

    // Class names are written out in full: the stylesheet is built from the names
    // found in these files, and a name put together at run time is not found.
    $classes = stiqon_classes([
        'text-action inline-flex h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-full px-3.5 text-sm transition-colors duration-200 ease-soft',
        'bg-ink/8 text-ink hover:bg-ink/14',
        'aria-[current=true]:bg-ink aria-[current=true]:text-paper' => $link,
        'aria-pressed:bg-ink aria-pressed:text-paper' => ! $link,
        $options['class'] ?? '',
    ]);

    $attributes = stiqon_attributes($options['attributes'] ?? []);

    if ($link) {
        return '<a href="'.esc_url($options['href']).'" aria-current="'.($active ? 'true' : 'false').'" class="'.esc_attr($classes).'"'.$attributes.'>'.esc_html($label).'</a>';
    }

    return '<button type="button" aria-pressed="'.($active ? 'true' : 'false').'" class="'.esc_attr($classes).'"'.$attributes.'>'.esc_html($label).'</button>';
}

/**
 * One card of a gallery: the product, drawn with one of its options, on a card
 * surface, with title, option and lowest price. See stiqon_gallery().
 *
 * The link is the title alone, stretched over the whole card: wrapped around
 * the drawing it would carry the lettering of the sample motif as its text.
 *
 * @param  array{key: string, product: WC_Product, slug: string, title: string, option: ?string, subtitle: ?string, chosen: array<string, string>, url: string, price: ?float}  $card
 */
function stiqon_product_card(array $card, bool $hidden = false): string
{
    $view = stiqon_product_view($card['product'], $card['chosen'], [
        'label' => implode(', ', array_filter([$card['title'], $card['subtitle']])),
    ]);

    $details = array_filter([
        $card['subtitle'] !== null ? '<span data-subtitle>'.esc_html($card['subtitle']).'</span>' : null,
        // wc_price() returns finished markup. The price stays on one line.
        $card['price'] !== null ? '<span class="whitespace-nowrap" data-price>ab '.wc_price($card['price']).'</span>' : null,
    ]);

    // "data-summary" is for the quick view, which shows the card large with a sentence about the product.
    return '<li class="group relative" data-card'.stiqon_attributes([
        'data-key' => $card['key'],
        'data-produkt' => $card['slug'],
        'data-option' => $card['option'] ?? '',
        'data-summary' => $card['summary'] ?? '',
        'hidden' => $hidden,
    ]).'>'
        .stiqon_stage($view, 'mist', 'transition-colors duration-200 ease-soft group-hover:bg-mist-deep')
        .'<h3 class="mt-3 font-semibold"><a href="'.esc_url($card['url']).'" class="outline-none after:absolute after:inset-0 after:z-10 after:rounded-card focus-visible:after:outline-2 focus-visible:after:outline-offset-4 focus-visible:after:outline-ink">'
        .esc_html($card['title']).'</a></h3>'
        .($details !== [] ? '<p class="text-label text-muted">'.implode(' · ', $details).'</p>' : '')
        .'</li>';
}

/**
 * One reference: its photo, the customer or the project, and a sentence or two.
 * See stiqon_references().
 *
 * @param  array{id: int, title: string, text: string, image: int}  $reference
 */
function stiqon_reference_card(array $reference): string
{
    // WordPress writes the tag, with the sizes it has of the photo.
    $photo = wp_get_attachment_image($reference['image'], 'stiqon-referenz', false, [
        'class' => 'size-full object-cover',
        'loading' => 'lazy',
        'sizes' => '(min-width: 64rem) 23rem, (min-width: 40rem) 50vw, 100vw',
    ]);

    // Widths for a row that is centred: one, two or three in a line, whatever their number.
    return '<li id="referenz-'.(int) $reference['id'].'" class="w-full scroll-mt-8 sm:w-[calc((100%-1rem)/2)] lg:w-[calc((100%-2rem)/3)]">'
        .'<div class="aspect-[4/3] overflow-hidden rounded-card bg-mist">'.$photo.'</div>'
        .'<h3 class="mt-4 text-lg font-semibold">'.esc_html($reference['title']).'</h3>'
        .($reference['text'] !== '' ? '<p class="mt-1 text-muted">'.esc_html($reference['text']).'</p>' : '')
        .'</li>';
}

/**
 * Title of a page section with an optional small label above and lead paragraph
 * below. Centred by default, as section titles are throughout the site.
 *
 * @param  array{title: string, label?: ?string, id?: ?string, level?: int, lead?: ?string, align?: string, class?: string}  $options
 */
function stiqon_section_heading(array $options): string
{
    $level = max(1, min(6, (int) ($options['level'] ?? 2)));
    $label = $options['label'] ?? null;
    $centred = ($options['align'] ?? 'center') === 'center';

    $html = '<div class="'.esc_attr(stiqon_classes(['max-w-2xl', 'mx-auto text-center' => $centred, $options['class'] ?? ''])).'">';

    if ($label !== null && $label !== '') {
        $html .= '<p class="text-label text-muted">'.esc_html($label).'</p>';
    }

    $html .= '<h'.$level.(empty($options['id']) ? '' : ' id="'.esc_attr($options['id']).'"')
        .' class="'.esc_attr(stiqon_classes(['text-title', 'mt-3' => $label !== null && $label !== ''])).'">'
        .esc_html($options['title']).'</h'.$level.'>';

    if (! empty($options['lead'])) {
        $html .= '<p class="text-lead mt-4 text-muted">'.esc_html($options['lead']).'</p>';
    }

    return $html.'</div>';
}

/**
 * Lets the visitor choose an image. It is only shown on the mockups; nothing is uploaded.
 * Classes passed in apply to the wrapper, so callers can hide or stretch the whole control.
 * The control only works with the page's script, so without script it is not shown at all.
 *
 * @param  array{variant?: string, size?: string, class?: string}  $options
 */
function stiqon_motif_button(string $label = 'Eigenes Motiv ausprobieren', array $options = []): string
{
    $button = stiqon_button(stiqon_icon('image').esc_html($label), [
        'variant' => $options['variant'] ?? 'primary',
        'size' => $options['size'] ?? 'md',
        'class' => 'w-full',
        'attributes' => ['x-on:click' => 'pick', 'data-motif-button' => true],
    ]);

    return '<span x-data="motifPicker" data-needs-script class="'.esc_attr(stiqon_classes(['inline-flex', $options['class'] ?? ''])).'">'
        .$button
        .'<input x-ref="file" x-on:change="chosen" type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" tabindex="-1" aria-hidden="true">'
        .'</span>';
}

/**
 * The wordmark. "dark" is the original for light surfaces; "light" has off-white
 * letters for dark ones. "sizes" is the width the logo is shown at, so phones do
 * not fetch the large file for nothing. "class" replaces the default size classes.
 *
 * @param  array{variant?: string, class?: string, sizes?: string, attributes?: array<string, string|int|bool|null>}  $options
 */
function stiqon_logo(array $options = []): string
{
    $name = ($options['variant'] ?? 'dark') === 'light' ? 'logo-light' : 'logo';
    $small = get_theme_file_uri("assets/brand/{$name}-400.webp");
    $large = get_theme_file_uri("assets/brand/{$name}-800.webp");

    return '<img src="'.esc_url($small).'" srcset="'.esc_url($small).' 400w, '.esc_url($large).' 800w"'
        .' sizes="'.esc_attr($options['sizes'] ?? '104px').'" width="400" height="123" alt="Stiqon" decoding="async"'
        .' class="'.esc_attr(($options['class'] ?? '') !== '' ? $options['class'] : 'h-8 w-auto').'"'
        .stiqon_attributes($options['attributes'] ?? []).'>';
}
