@props(['tone' => 'mist', 'ratio' => 'square'])
@php
    $tones = [
        'mist' => 'bg-mist',
        'sand' => 'bg-stage-sand',
        'sky' => 'bg-stage-sky',
        'sage' => 'bg-stage-sage',
        'blush' => 'bg-stage-blush',
        'night' => 'bg-night',
        'paper' => 'border border-line bg-paper',
    ];
    $ratios = [
        'square' => 'aspect-square',
        'portrait' => 'aspect-[4/5]',
        'landscape' => 'aspect-[5/4]',
    ];
@endphp
{{-- The surface a mockup lies on: one card of the gallery grid. --}}
<div {{ $attributes->class(['relative flex items-center justify-center overflow-hidden rounded-card', $tones[$tone], $ratios[$ratio]]) }}>
    {{ $slot }}
</div>
