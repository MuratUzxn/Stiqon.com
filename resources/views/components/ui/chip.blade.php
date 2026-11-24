@props(['active' => false, 'href' => null])
@php
    $classes = [
        'inline-flex h-10 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border px-4 text-sm font-medium transition-colors duration-200 ease-soft',
        'border-ink bg-ink text-paper' => $active,
        'border-control bg-paper text-ink hover:bg-mist' => ! $active,
    ];
@endphp
@if ($href)
    <a href="{{ $href }}" @if ($active) aria-current="true" @endif {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="button" aria-pressed="{{ $active ? 'true' : 'false' }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
