{{-- Bold word with a small burst. --}}
@unless ($bare)
    <rect width="100" height="100" fill="#0f0f0e" />
@endunless
<text x="50" y="63" text-anchor="middle" font-size="36" font-weight="700" letter-spacing="-1.6" fill="{{ $bare ? '#0f0f0e' : '#ffffff' }}">NEU</text>
<path d="M77 17l2.6 6.2 6.6-1.4-3.6 5.7 4.6 4.9-6.7.6-1.4 6.6-4-5.4-6.1 2.7 1.9-6.4-5.8-3.4 6.3-2.2-.4-6.7z" fill="#d9a441" />
<path d="M22 78h56" stroke="{{ $bare ? '#0f0f0e' : '#ffffff' }}" stroke-width="1" />
