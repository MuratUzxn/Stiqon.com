@php
    // A width class from the caller (w-…, md:w-…) replaces the full-width default.
    $sized = (bool) preg_match('/(^|[\s:])w-/', (string) $attributes->get('class'));
    $x = $sheet['x'];
    $y = $sheet['y'];
    $w = $sheet['width'];
    $h = $sheet['height'];
@endphp
<svg
    viewBox="0 0 120 150"
    role="img"
    aria-label="{{ $describe() }}"
    data-mockup="flyer"
    {{ $attributes->class(['lift block h-auto overflow-visible', 'w-full' => ! $sized]) }}
>
    <defs>
        <linearGradient id="{{ $uid }}-gloss" x1="0.05" y1="0" x2="0.95" y2="1">
            <stop offset="0.32" stop-color="#fff" stop-opacity="0" />
            <stop offset="0.46" stop-color="#fff" stop-opacity="0.2" />
            <stop offset="0.56" stop-color="#fff" stop-opacity="0.06" />
            <stop offset="0.7" stop-color="#fff" stop-opacity="0" />
        </linearGradient>
    </defs>

    @if ($stack)
        {{-- Sheets underneath, fanned out slightly --}}
        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $w }}" height="{{ $h }}" rx="0.8" fill="#eeede8" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" transform="rotate(8 60 75)" />
        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $w }}" height="{{ $h }}" rx="0.8" fill="#f6f5f1" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" transform="rotate(2.5 60 75)" />
    @endif

    <g transform="rotate({{ $tilt }} 60 75)">
        @if ($heavy)
            {{-- The edge of thicker stock --}}
            <rect x="{{ $x + 0.9 }}" y="{{ $y + 1.1 }}" width="{{ $w }}" height="{{ $h }}" rx="0.8" fill="#d6d6d0" />
        @endif

        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $w }}" height="{{ $h }}" rx="0.8" fill="#ffffff" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" />

        {{-- Printed side: the sample motif, replaced by the visitor's own motif once chosen --}}
        <g x-data="motifLayer">
            <svg
                x="{{ $x }}" y="{{ $y }}" width="{{ $w }}" height="{{ $h }}"
                viewBox="0 0 100 141.4" preserveAspectRatio="xMidYMid slice" x-show="idle"
            >
                @include("components.motif.flyer.{$motif}", ['uid' => $uid])
            </svg>
            <g x-show="active" x-cloak>
                <image
                    x="{{ $x }}" y="{{ $y }}" width="{{ $w }}" height="{{ $h }}"
                    preserveAspectRatio="xMidYMid slice" x-bind:href="url" data-motif-image
                />
            </g>
        </g>

        @if ($gloss)
            <rect x="{{ $x }}" y="{{ $y }}" width="{{ $w }}" height="{{ $h }}" fill="url(#{{ $uid }}-gloss)" pointer-events="none" />
        @endif
    </g>
</svg>
