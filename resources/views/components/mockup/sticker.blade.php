@php
    // A width class from the caller (w-…, md:w-…) replaces the full-width default.
    $sized = (bool) preg_match('/(^|[\s:])w-/', (string) $attributes->get('class'));
    $box = $printBox;
    $corner = $peelCorner();
    $blend = in_array($material, ['holo', 'kraft'], true) ? 'mix-blend-mode:multiply' : null;
    $backing = match ($material) {
        'vinyl' => '#ffffff',
        'holo' => "url(#{$uid}-holo)",
        'kraft' => '#c9a57c',
        'clear' => 'none',
    };
@endphp
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="{{ $describe() }}"
    data-mockup="sticker"
    {{ $attributes->class(['block h-auto overflow-visible', 'w-full' => ! $sized, $material === 'clear' ? 'lift-faint' : 'lift']) }}
>
    <defs>
        <clipPath id="{{ $uid }}-print">
            @if ($contour)
                <path d="{{ $contour }}" transform="translate(10 10)" />
            @else
                <path d="{{ $printArea }}" />
            @endif
        </clipPath>

        {{-- Diagonal band of light: the laminate. Fainter on matte stock. --}}
        <linearGradient id="{{ $uid }}-gloss" x1="0.08" y1="0" x2="0.92" y2="1">
            <stop offset="0.22" stop-color="#fff" stop-opacity="0" />
            <stop offset="0.44" stop-color="#fff" stop-opacity="{{ $finish === 'gloss' ? '0.24' : '0.07' }}" />
            <stop offset="0.56" stop-color="#fff" stop-opacity="{{ $finish === 'gloss' ? '0.08' : '0.02' }}" />
            <stop offset="0.78" stop-color="#fff" stop-opacity="0" />
        </linearGradient>

        @if ($material === 'holo')
            <linearGradient id="{{ $uid }}-holo" x1="0" y1="0.1" x2="1" y2="0.9">
                <stop offset="0" stop-color="#c7f0ff" />
                <stop offset="0.2" stop-color="#e2d2ff" />
                <stop offset="0.4" stop-color="#ffd4ea" />
                <stop offset="0.6" stop-color="#fff0b8" />
                <stop offset="0.8" stop-color="#c8ffe1" />
                <stop offset="1" stop-color="#c7f0ff" />
            </linearGradient>
        @endif

        @if ($material === 'kraft')
            <pattern id="{{ $uid }}-fibre" width="14" height="14" patternUnits="userSpaceOnUse" patternTransform="rotate(18)">
                <path d="M1 3h3M9 9h4M4 12h2" stroke="#8a6a45" stroke-width="0.3" stroke-opacity="0.2" stroke-linecap="round" />
                <path d="M9 2h2M0 7h3" stroke="#f0d9ba" stroke-width="0.3" stroke-opacity="0.3" stroke-linecap="round" />
            </pattern>
        @endif

        @if ($corner)
            <mask id="{{ $uid }}-peel">
                <rect width="120" height="120" fill="#fff" />
                <path d="M{{ $corner[0][0] }} {{ $corner[0][1] }}L{{ $corner[1][0] }} {{ $corner[1][1] }}L{{ $corner[1][0] }} {{ $corner[0][1] }}z" fill="#000" />
            </mask>
            <linearGradient id="{{ $uid }}-flap" x1="1" y1="1" x2="0" y2="0">
                <stop offset="0" stop-color="#cfcfca" />
                <stop offset="0.55" stop-color="#f1f1ee" />
                <stop offset="1" stop-color="#ffffff" />
            </linearGradient>
            <linearGradient id="{{ $uid }}-flap-shade" x1="1" y1="1" x2="0" y2="0">
                <stop offset="0" stop-color="#000" stop-opacity="0.2" />
                <stop offset="1" stop-color="#000" stop-opacity="0" />
            </linearGradient>
        @endif
    </defs>

    <g @if ($corner) mask="url(#{{ $uid }}-peel)" @endif>
        {{-- Backing material --}}
        @if ($contour)
            <g transform="translate(10 10)">
                <path d="{{ $contour }}" fill="none" stroke="#0a0a0a" stroke-opacity="0.08" stroke-width="9.6" stroke-linejoin="round" />
                <path d="{{ $contour }}" fill="{{ $backing === 'none' ? 'none' : $backing }}" stroke="{{ $backing === 'none' ? 'none' : $backing }}" stroke-width="9" stroke-linejoin="round" />
            </g>
        @elseif ($material !== 'clear')
            <path d="{{ $outline }}" fill="{{ $backing }}" stroke="#0a0a0a" stroke-opacity="0.08" stroke-width="0.5" />
            @if ($material === 'kraft')
                <path d="{{ $outline }}" fill="url(#{{ $uid }}-fibre)" />
            @endif
        @endif

        {{-- Printed area: the sample motif, replaced by the visitor's own motif once chosen --}}
        <g clip-path="url(#{{ $uid }}-print)" @if ($blend) style="{{ $blend }}" @endif x-data="motifLayer">
            <svg
                x="{{ $box['x'] }}" y="{{ $box['y'] }}" width="{{ $box['width'] }}" height="{{ $box['height'] }}"
                viewBox="0 0 100 100" preserveAspectRatio="xMidYMid slice" x-show="idle"
            >
                @include("components.motif.{$motif}", ['uid' => $uid, 'bare' => $bareMotif()])
            </svg>
            <g x-show="active" x-cloak>
                @if ($material === 'vinyl')
                    <rect x="{{ $box['x'] }}" y="{{ $box['y'] }}" width="{{ $box['width'] }}" height="{{ $box['height'] }}" fill="#fff" />
                @endif
                <image
                    x="{{ $box['x'] }}" y="{{ $box['y'] }}" width="{{ $box['width'] }}" height="{{ $box['height'] }}"
                    preserveAspectRatio="xMidYMid slice" x-bind:href="url" data-motif-image
                />
            </g>
        </g>

        {{-- Finish --}}
        @if ($material === 'clear' && ! $contour)
            <path d="{{ $outline }}" fill="none" stroke="#fff" stroke-opacity="0.7" stroke-width="0.9" />
            <path d="{{ $outline }}" fill="none" stroke="#0a0a0a" stroke-opacity="0.08" stroke-width="0.35" />
        @endif
        <rect width="120" height="120" fill="url(#{{ $uid }}-gloss)" clip-path="url(#{{ $uid }}-print)" pointer-events="none" />
    </g>

    @if ($corner)
        {{-- The corner lifted off the backing paper --}}
        <path
            d="M{{ $corner[0][0] }} {{ $corner[0][1] }}L{{ $corner[1][0] }} {{ $corner[1][1] }}L{{ $corner[2][0] - 9 }} {{ $corner[2][1] - 9 }}z"
            fill="url(#{{ $uid }}-flap-shade)"
        />
        <path
            d="M{{ $corner[0][0] }} {{ $corner[0][1] }}L{{ $corner[1][0] }} {{ $corner[1][1] }}L{{ $corner[2][0] + 5 }} {{ $corner[2][1] }}Q{{ $corner[2][0] }} {{ $corner[2][1] }} {{ $corner[2][0] }} {{ $corner[2][1] + 5 }}z"
            fill="url(#{{ $uid }}-flap)" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.4" stroke-linejoin="round"
        />
    @endif
</svg>
