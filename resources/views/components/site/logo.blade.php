@props(['variant' => 'dark'])
@php
    // "dark" is the original wordmark for light surfaces; "light" has off-white letters for dark ones.
    $name = $variant === 'light' ? 'logo-light' : 'logo';
@endphp
<img
    src="{{ asset("img/brand/{$name}-400.webp") }}"
    srcset="{{ asset("img/brand/{$name}-400.webp") }} 400w, {{ asset("img/brand/{$name}-800.webp") }} 800w"
    sizes="160px" width="400" height="123" alt="Stiqon" decoding="async"
    {{ $attributes->class(['h-8 w-auto']) }}
>
