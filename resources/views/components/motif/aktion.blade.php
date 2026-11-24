{{-- Offer badge: large figure in a ring. --}}
@unless ($bare)
    <rect width="100" height="100" fill="#141412" />
@endunless
<circle cx="50" cy="50" r="40" fill="none" stroke="#d9a441" stroke-width="1.6" />
<circle cx="50" cy="50" r="36" fill="none" stroke="#d9a441" stroke-width="0.5" stroke-dasharray="1.2 2.4" />
<text x="50" y="58" text-anchor="middle" font-size="27" font-weight="700" letter-spacing="-1.2" fill="{{ $bare ? '#141412' : '#ffffff' }}">−20 %</text>
<text x="50" y="72" text-anchor="middle" font-size="6.4" font-weight="600" letter-spacing="2.6" fill="#d9a441">AKTION</text>
