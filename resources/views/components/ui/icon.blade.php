@props(['name'])
@php
    // A small hand-picked set, drawn on a 24 px grid with round 1.75 px strokes.
    $paths = [
        'arrow-right' => 'M5 12h14M13 6l6 6-6 6',
        'arrow-up-right' => 'M7 17 17 7M8 7h9v9',
        'upload' => 'M12 16V4M7 9l5-5 5 5M5 20h14',
        'image' => 'M4 5h16v14H4zM4 16l4.5-4.5 3.5 3.5 3-3 5 5M9 9.5h.01',
        'check' => 'M5 12.5l4.5 4.5L19 7.5',
        'menu' => 'M4 7h16M4 12h16M4 17h16',
        'x' => 'M6 6l12 12M18 6 6 18',
        'chevron-down' => 'M6 9l6 6 6-6',
        'plus' => 'M12 5v14M5 12h14',
    ];
@endphp
<svg
    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
    aria-hidden="true" focusable="false" {{ $attributes->class(['size-5 shrink-0']) }}
>
    <path d="{{ $paths[$name] }}" />
</svg>
