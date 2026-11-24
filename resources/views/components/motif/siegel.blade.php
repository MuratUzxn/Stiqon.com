{{-- Seal: lettering on a ring around a serif word. --}}
@unless ($bare)
    <rect width="100" height="100" fill="#f1ebdd" />
@endunless
<defs>
    <path id="{{ $uid }}-ring" d="M16.5 50a33.5 33.5 0 1 1 67 0a33.5 33.5 0 1 1 -67 0" />
</defs>
<circle cx="50" cy="50" r="42" fill="none" stroke="#1d1d1b" stroke-width="1.1" />
<circle cx="50" cy="50" r="26" fill="none" stroke="#1d1d1b" stroke-width="0.7" />
<text font-size="6.6" font-weight="600" fill="#1d1d1b">
    <textPath href="#{{ $uid }}-ring" textLength="206" lengthAdjust="spacing">HANDGEMACHT · MIT SORGFALT · IN KLEINER AUFLAGE ·</textPath>
</text>
<text x="50" y="56.5" text-anchor="middle" font-size="19" fill="#1d1d1b" style="font-family:var(--font-accent);font-style:italic">Fein</text>
