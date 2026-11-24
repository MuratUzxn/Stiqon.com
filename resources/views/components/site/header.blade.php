@php
    $areas = [
        ['label' => 'Sticker', 'url' => route('area.show', 'sticker'), 'current' => request()->routeIs('area.show') && request()->route('area') === 'sticker'],
        ['label' => 'Flyer', 'url' => route('area.show', 'flyer'), 'current' => request()->routeIs('area.show') && request()->route('area') === 'flyer'],
    ];
    $link = 'inline-flex h-10 items-center rounded-full px-4 text-[0.9375rem] font-medium transition-colors duration-200 ease-soft';
@endphp
<header class="sticky top-0 z-40 border-b border-line bg-paper/90 backdrop-blur-md" x-data="siteHeader">
    <div class="page flex h-16 items-center gap-4 lg:h-18 lg:gap-8">
        <a href="{{ route('home') }}" class="shrink-0 rounded-sm" aria-label="Stiqon – zur Startseite">
            <x-site.logo class="h-7 lg:h-8" />
        </a>

        <nav aria-label="Bereiche" class="hidden items-center gap-1 md:flex">
            @foreach ($areas as $area)
                <a
                    href="{{ $area['url'] }}" @if ($area['current']) aria-current="page" @endif
                    @class([$link, 'bg-mist text-ink' => $area['current'], 'text-ink-soft hover:bg-mist hover:text-ink' => ! $area['current']])
                >{{ $area['label'] }}</a>
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-2">
            <x-ui.motif-button variant="secondary" size="sm" class="max-sm:hidden">Eigenes Motiv</x-ui.motif-button>

            <button
                type="button" x-on:click="toggle" x-bind:aria-expanded="open" aria-expanded="false" aria-controls="mobile-navigation"
                class="inline-flex size-11 items-center justify-center rounded-full text-ink transition-colors hover:bg-mist md:hidden"
            >
                <span class="sr-only">Menü</span>
                <x-ui.icon name="menu" x-show="closed" />
                <x-ui.icon name="x" x-show="open" x-cloak />
            </button>
        </div>
    </div>

    <div id="mobile-navigation" x-show="open" x-cloak class="border-t border-line md:hidden">
        <nav aria-label="Bereiche (mobil)" class="page flex flex-col gap-1 py-4">
            @foreach ($areas as $area)
                <a
                    href="{{ $area['url'] }}" @if ($area['current']) aria-current="page" @endif
                    @class(['flex h-12 items-center rounded-control px-4 text-lg font-medium', 'bg-mist' => $area['current'], 'hover:bg-mist' => ! $area['current']])
                >{{ $area['label'] }}</a>
            @endforeach
            <x-ui.motif-button variant="secondary" class="mt-3 w-full sm:hidden">Eigenes Motiv ausprobieren</x-ui.motif-button>
        </nav>
    </div>
</header>
