{{-- A single serif initial in a ring. --}}
@unless ($bare)
    <rect width="100" height="100" fill="#1f3d2e" />
@endunless
<circle cx="50" cy="50" r="38" fill="none" stroke="{{ $bare ? '#1f3d2e' : '#e9dfc8' }}" stroke-width="0.9" />
<text x="50" y="68" text-anchor="middle" font-size="58" fill="{{ $bare ? '#1f3d2e' : '#e9dfc8' }}" style="font-family:var(--font-accent);font-style:italic">M</text>
