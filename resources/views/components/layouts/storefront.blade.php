@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' – '.config('app.name') : config('app.name').' – Sticker und Flyer mit Ihrem Motiv' }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    {{-- The shop is not launched yet; nothing here should be indexed. --}}
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/brand/icon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/brand/icon-180.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Explicit on purpose: Livewire only auto-injects its assets on pages that render a
         component, but the storefront uses the bundled Alpine everywhere. --}}
    @livewireStyles
</head>
<body class="flex min-h-screen flex-col">
    <a
        href="#inhalt"
        class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-full focus:bg-ink focus:px-5 focus:py-3 focus:text-paper"
    >Zum Inhalt springen</a>

    <x-site.header />

    <main id="inhalt" class="flex-1">
        {{ $slot }}
    </main>

    <x-site.footer />
    <x-site.motif-status />

    @livewireScripts
</body>
</html>
