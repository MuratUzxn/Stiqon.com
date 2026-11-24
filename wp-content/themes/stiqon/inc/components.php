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
 * false and null leave the attribute out.
 *
 * @param  array<string, string|int|bool|null>  $attributes
 */
function stiqon_attributes(array $attributes): string
{
    $html = '';

    foreach ($attributes as $name => $value) {
        if ($value === false || $value === null) {
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
        'chevron-down' => 'M6 9l6 6 6-6',
        'plus' => 'M12 5v14M5 12h14',
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
    $base = 'text-action inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-full '
        .'transition-colors duration-200 ease-soft disabled:pointer-events-none disabled:opacity-50';

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
 * A filter chip: a link when "href" is given, otherwise a toggle button.
 *
 * @param  array{active?: bool, href?: ?string, class?: string}  $options
 */
function stiqon_chip(string $label, array $options = []): string
{
    $active = ! empty($options['active']);

    $classes = stiqon_classes([
        'text-action inline-flex h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-full px-3.5 text-sm transition-colors duration-200 ease-soft',
        'bg-ink text-paper' => $active,
        'bg-ink/8 text-ink hover:bg-ink/14' => ! $active,
        $options['class'] ?? '',
    ]);

    if (! empty($options['href'])) {
        return '<a href="'.esc_url($options['href']).'"'.($active ? ' aria-current="true"' : '').' class="'.esc_attr($classes).'">'.esc_html($label).'</a>';
    }

    return '<button type="button" aria-pressed="'.($active ? 'true' : 'false').'" class="'.esc_attr($classes).'">'.esc_html($label).'</button>';
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
 *
 * @param  array{variant?: string, size?: string, class?: string}  $options
 */
function stiqon_motif_button(string $label = 'Eigenes Motiv ausprobieren', array $options = []): string
{
    $button = stiqon_button(stiqon_icon('image').esc_html($label), [
        'variant' => $options['variant'] ?? 'primary',
        'size' => $options['size'] ?? 'md',
        'class' => 'w-full',
        'attributes' => ['x-on:click' => 'pick'],
    ]);

    return '<span x-data="motifPicker" class="'.esc_attr(stiqon_classes(['inline-flex', $options['class'] ?? ''])).'">'
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
