<x-layouts.storefront :title="$area['name']" :description="$area['lead']">
    <section class="page pt-10 pb-16 lg:pt-16 lg:pb-24">
        <p class="text-overline text-muted">Bereich</p>
        <h1 class="text-display mt-5">{{ $area['name'] }}</h1>
        <p class="text-lead mt-6 max-w-2xl text-ink-soft">{{ $area['lead'] }}</p>
        <div class="mt-9 flex flex-wrap items-center gap-3">
            <x-ui.motif-button size="lg">Motiv wählen</x-ui.motif-button>
        </div>

        <p class="mt-12 rounded-card border border-line p-6 text-ink-soft">
            Die Galerie mit allen Produkten entsteht gerade. Die Karten unten zeigen bereits, wie Ihr Motiv wirken wird.
        </p>

        <ul class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4" aria-label="Beispiele">
            @if ($slug === 'sticker')
                <li><x-mockup.stage tone="mist"><x-mockup.sticker shape="circle" motif="siegel" /></x-mockup.stage></li>
                <li><x-mockup.stage tone="sand"><x-mockup.sticker shape="square" motif="sonne" :peel="true" /></x-mockup.stage></li>
                <li><x-mockup.stage tone="sky"><x-mockup.sticker shape="rect" motif="wellen" /></x-mockup.stage></li>
                <li><x-mockup.stage tone="blush"><x-mockup.sticker shape="oval" motif="danke" /></x-mockup.stage></li>
                <li><x-mockup.stage tone="mist"><x-mockup.sticker shape="contour" motif="sprechblase" /></x-mockup.stage></li>
                <li><x-mockup.stage tone="sky"><x-mockup.sticker shape="circle" material="clear" motif="monogramm" /></x-mockup.stage></li>
                <li><x-mockup.stage tone="mist"><x-mockup.sticker shape="square" material="holo" motif="raster" /></x-mockup.stage></li>
                <li><x-mockup.stage tone="sand"><x-mockup.sticker shape="circle" material="kraft" motif="blatt" finish="matte" /></x-mockup.stage></li>
            @else
                <li><x-mockup.stage tone="mist"><x-mockup.flyer format="a6" motif="fest" /></x-mockup.stage></li>
                <li><x-mockup.stage tone="sand"><x-mockup.flyer format="a5" motif="eroeffnung" :stack="true" /></x-mockup.stage></li>
                <li><x-mockup.stage tone="sage"><x-mockup.flyer format="a4" motif="karte" :heavy="true" /></x-mockup.stage></li>
                <li><x-mockup.stage tone="mist"><x-mockup.flyer format="lang" motif="prozent" :gloss="true" /></x-mockup.stage></li>
                <li><x-mockup.stage tone="sky"><x-mockup.flyer format="quadrat" motif="kursplan" :stack="true" /></x-mockup.stage></li>
            @endif
        </ul>
    </section>
</x-layouts.storefront>
