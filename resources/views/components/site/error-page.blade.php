@props(['code', 'title', 'heading'])
{{-- An error inside the shop frame: what happened, in one sentence, and where to go next. --}}
<x-layouts.storefront :title="$title">
    <section class="page py-20 lg:py-32">
        <p class="text-overline text-muted">Fehler {{ $code }}</p>
        <h1 class="text-title mt-5 max-w-3xl">{{ $heading }}</h1>
        <p class="text-lead mt-5 max-w-xl text-ink-soft">{{ $slot }}</p>
        <div class="mt-9 flex flex-wrap items-center gap-3">
            <x-ui.button size="lg" :href="route('home')">Zur Startseite</x-ui.button>
            <x-ui.button variant="secondary" size="lg" :href="route('area.show', 'sticker')">Sticker ansehen</x-ui.button>
            <x-ui.button variant="secondary" size="lg" :href="route('area.show', 'flyer')">Flyer ansehen</x-ui.button>
        </div>
    </section>
</x-layouts.storefront>
