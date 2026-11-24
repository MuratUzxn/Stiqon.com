@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'button'])
@php
    $base = 'inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-full font-medium '
        .'transition-[background-color,border-color,color,transform] duration-200 ease-soft active:scale-[0.98] '
        .'disabled:pointer-events-none disabled:opacity-50';

    $sizes = [
        'sm' => 'h-10 px-4 text-sm',
        'md' => 'h-11 px-5 text-[0.9375rem]',
        'lg' => 'h-13 px-7 text-base',
    ];

    $variants = [
        'primary' => 'bg-ink text-paper hover:bg-ink-soft',
        'secondary' => 'border border-control bg-paper text-ink hover:bg-mist',
        'quiet' => 'text-ink hover:bg-mist',
        'on-night' => 'bg-on-night text-night hover:bg-white',
        'on-night-outline' => 'border border-on-night/45 text-on-night hover:bg-on-night/10',
    ];

    $classes = [$base, $sizes[$size], $variants[$variant]];
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
