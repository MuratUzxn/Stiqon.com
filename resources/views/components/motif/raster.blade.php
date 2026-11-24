{{-- Checker pattern with a label plate. --}}
@unless ($bare)
    <rect width="100" height="100" fill="#ffffff" />
@endunless
<g fill="#141412">
    @foreach (range(0, 4) as $row)
        @foreach (range(0, 4) as $column)
            @if (($row + $column) % 2 === 0)
                <rect x="{{ $column * 20 }}" y="{{ $row * 20 }}" width="20" height="20" />
            @endif
        @endforeach
    @endforeach
</g>
<rect x="18" y="36" width="64" height="28" rx="14" fill="#d9a441" />
<text x="50" y="55" text-anchor="middle" font-size="14" font-weight="700" letter-spacing="-0.4" fill="#141412">Hallo</text>
