@props(['title'])
{{-- For pages that must render even when the shop itself is in trouble (server error,
     maintenance): no build assets, no scripts, no database – only the logo and a few styles. --}}
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} – Stiqon</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; text-size-adjust: 100%; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 2rem 1.25rem;
            background: #ffffff; color: #0a0a0a;
            font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        main { width: 100%; max-width: 34rem; }
        img { display: block; height: 2rem; width: auto; }
        p.code { margin: 3rem 0 0; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: #6f6f6a; }
        h1 { margin: 1rem 0 0; font-size: clamp(1.75rem, 1.3rem + 2.2vw, 2.5rem); font-weight: 600; letter-spacing: -0.03em; line-height: 1.1; }
        p.text { margin: 1.25rem 0 0; font-size: 1.125rem; line-height: 1.5; color: #3d3d3a; }
        a.action {
            display: inline-flex; align-items: center; height: 3.25rem; margin-top: 2.25rem; padding: 0 1.75rem;
            border-radius: 999px; background: #0a0a0a; color: #ffffff; font-weight: 500; text-decoration: none;
        }
        a.action:hover { background: #3d3d3a; }
        a:focus-visible { outline: 2px solid #0a0a0a; outline-offset: 3px; }
    </style>
</head>
<body>
    <main>
        <img src="/img/brand/logo-400.webp" width="400" height="123" alt="Stiqon">
        {{ $slot }}
    </main>
</body>
</html>
