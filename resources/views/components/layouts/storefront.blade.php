@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' – '.config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Explicit on purpose: Livewire only auto-injects its assets on pages that render a
         component, but the storefront uses the bundled Alpine everywhere. --}}
    @livewireStyles
</head>
<body class="min-h-screen bg-white font-sans text-neutral-950 antialiased">
    {{ $slot }}
    @livewireScripts
</body>
</html>
