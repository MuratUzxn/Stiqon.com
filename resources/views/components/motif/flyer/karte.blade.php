{{-- Menu card: a serif title over ruled rows. --}}
<rect width="100" height="141.4" fill="#ffffff" />
<rect x="5" y="5" width="90" height="131.4" fill="none" stroke="#1d1d1b" stroke-width="0.5" />
<text x="50" y="30" text-anchor="middle" font-size="15" fill="#1d1d1b" style="font-family:var(--font-accent);font-style:italic">Mittagstisch</text>
<path d="M38 36h24" stroke="#c8553d" stroke-width="1" />
@foreach ([[34, 52], [28, 64], [38, 76], [30, 88], [36, 100]] as [$length, $top])
    <rect x="14" y="{{ $top }}" width="{{ $length }}" height="2.2" rx="1.1" fill="#1d1d1b" fill-opacity="0.78" />
    <rect x="14" y="{{ $top + 4.4 }}" width="{{ $length - 10 }}" height="1.5" rx="0.75" fill="#1d1d1b" fill-opacity="0.3" />
    <path d="M{{ 18 + $length }} {{ $top + 1.1 }}H76" stroke="#1d1d1b" stroke-opacity="0.3" stroke-width="0.5" stroke-dasharray="0.6 1.6" />
    <rect x="78" y="{{ $top }}" width="8" height="2.2" rx="1.1" fill="#1d1d1b" fill-opacity="0.78" />
@endforeach
<text x="50" y="122" text-anchor="middle" font-size="4.2" font-weight="700" letter-spacing="1.8" fill="#1d1d1b">MONTAG BIS FREITAG</text>
