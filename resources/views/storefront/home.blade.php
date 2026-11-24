@php
    // Fixed example cards for this stage of the build; the catalogue replaces them.
    $assortment = [
        ['title' => 'Runde Sticker', 'meta' => 'Weißes Vinyl', 'area' => 'sticker', 'tone' => 'mist', 'sticker' => ['shape' => 'circle', 'material' => 'vinyl', 'motif' => 'aktion']],
        ['title' => 'Quadratische Sticker', 'meta' => 'Holografische Folie', 'area' => 'sticker', 'tone' => 'mist', 'sticker' => ['shape' => 'square', 'material' => 'holo', 'motif' => 'raster']],
        ['title' => 'Sticker mit Konturschnitt', 'meta' => 'Weißes Vinyl', 'area' => 'sticker', 'tone' => 'sage', 'sticker' => ['shape' => 'contour', 'material' => 'vinyl', 'motif' => 'stern']],
        ['title' => 'Runde Sticker', 'meta' => 'Kraftpapier', 'area' => 'sticker', 'tone' => 'sand', 'sticker' => ['shape' => 'circle', 'material' => 'kraft', 'motif' => 'siegel']],
        ['title' => 'Flyer DIN A6', 'meta' => 'Einzelblatt', 'area' => 'flyer', 'tone' => 'mist', 'flyer' => ['format' => 'a6', 'motif' => 'fest', 'stack' => false]],
        ['title' => 'Flyer DIN A5', 'meta' => 'Auflage im Stapel', 'area' => 'flyer', 'tone' => 'sand', 'flyer' => ['format' => 'a5', 'motif' => 'eroeffnung', 'stack' => true]],
        ['title' => 'Flyer DIN lang', 'meta' => 'Glänzend', 'area' => 'flyer', 'tone' => 'mist', 'flyer' => ['format' => 'lang', 'motif' => 'prozent', 'stack' => false, 'gloss' => true]],
        ['title' => 'Flyer Quadrat', 'meta' => 'Auflage im Stapel', 'area' => 'flyer', 'tone' => 'sky', 'flyer' => ['format' => 'quadrat', 'motif' => 'kursplan', 'stack' => true]],
    ];

    $steps = [
        ['title' => 'Produkt wählen', 'text' => 'Form, Größe und Material für Sticker – Format und Papier für Flyer.'],
        ['title' => 'Motiv hinzufügen', 'text' => 'Ein eigenes Bild verwenden oder eine Vorlage anpassen.'],
        ['title' => 'Vorschau prüfen', 'text' => 'Sie sehen Ihr Motiv auf dem Produkt, bevor es in den Druck geht.'],
    ];

    $questions = [
        ['question' => 'Sehe ich mein Motiv, bevor ich bestelle?', 'answer' => 'Ja. Sobald Sie ein Bild wählen, erscheint es auf allen Stickern und Flyern dieser Seite – in jeder Form und auf jedem Material.'],
        ['question' => 'Wird mein Bild dabei hochgeladen?', 'answer' => 'Nein. Für die Vorschau bleibt das Bild in Ihrem Browser. Es verlässt Ihr Gerät nicht.'],
        ['question' => 'Welche Dateien eignen sich für die Vorschau?', 'answer' => 'Bilder im Format PNG, JPEG oder WebP. Ein freigestelltes PNG zeigt am besten, wie ein Sticker mit Konturschnitt wirkt.'],
        ['question' => 'Welche Formen und Materialien zeigt die Vorschau?', 'answer' => 'Runde, quadratische, rechteckige und ovale Sticker sowie Sticker mit Konturschnitt – auf weißem Vinyl, transparenter und holografischer Folie und auf Kraftpapier. Flyer von DIN A6 bis DIN A4, DIN lang und quadratisch.'],
        ['question' => 'Kann ich schon bestellen?', 'answer' => 'Noch nicht. Der Shop wird gerade aufgebaut; die Bestellung folgt.'],
    ];
@endphp
<x-layouts.storefront :accent-font="true" description="Sticker und Flyer mit Ihrem Motiv: Bild wählen, Vorschau auf allen Formen und Formaten ansehen.">
    {{-- Hero: one action, the wall of mockups as a calm backdrop --}}
    <section class="page pt-10 pb-16 lg:pt-16 lg:pb-24">
        <div class="grid items-center gap-10 lg:grid-cols-[1.05fr_1fr] lg:gap-16">
            <div>
                <p class="text-overline text-muted">Sticker und Flyer</p>
                <h1 class="text-display mt-5">Werbemittel, die <span class="accent text-[1.06em]">haften bleiben.</span></h1>
                <p class="text-lead mt-6 max-w-xl text-ink-soft">
                    Wählen Sie Ihr Motiv und sehen Sie es sofort auf Stickern und Flyern – in jeder Form, auf jedem Material.
                </p>
                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <x-ui.motif-button size="lg">Motiv wählen</x-ui.motif-button>
                    <x-ui.button variant="secondary" size="lg" :href="route('area.show', 'sticker')">Sticker ansehen</x-ui.button>
                    <x-ui.button variant="secondary" size="lg" :href="route('area.show', 'flyer')">Flyer ansehen</x-ui.button>
                </div>
                <p class="mt-5 text-sm text-muted">Ihr Bild bleibt in Ihrem Browser – es wird nichts hochgeladen.</p>
            </div>

            <x-site.card-wall class="hidden lg:grid" />

            {{-- Small screens: four cards, standing still --}}
            <div class="grid grid-cols-2 gap-3 lg:hidden">
                <x-mockup.stage tone="mist"><x-mockup.sticker shape="circle" motif="siegel" /></x-mockup.stage>
                <x-mockup.stage tone="sand"><x-mockup.flyer format="a5" motif="eroeffnung" :stack="true" /></x-mockup.stage>
                <x-mockup.stage tone="sage"><x-mockup.sticker shape="contour" motif="sprechblase" /></x-mockup.stage>
                <x-mockup.stage tone="sky"><x-mockup.sticker shape="rect" motif="wellen" /></x-mockup.stage>
            </div>
        </div>
    </section>

    {{-- The two areas. Text and mockups each have their own column, so they never overlap. --}}
    <section class="page pb-16 lg:pb-24" aria-labelledby="bereiche">
        <h2 id="bereiche" class="sr-only">Bereiche</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <div class="group relative grid overflow-hidden rounded-tile bg-mist md:min-h-[22rem] md:grid-cols-2">
                <div class="flex flex-col justify-between gap-8 p-7 lg:p-10">
                    <div>
                        <h3 class="text-title">
                            <a href="{{ route('area.show', 'sticker') }}" class="after:absolute after:inset-0 after:rounded-tile">Sticker</a>
                        </h3>
                        <p class="mt-3 text-ink-soft">Rund, eckig, oval oder mit Konturschnitt – auf Vinyl, Folie oder Kraftpapier.</p>
                    </div>
                    <span class="inline-flex items-center gap-2 font-medium">
                        Sticker ansehen <x-ui.icon name="arrow-right" class="transition-transform duration-200 ease-soft group-hover:translate-x-1" />
                    </span>
                </div>
                <div class="pointer-events-none relative h-52 transition-transform duration-500 ease-soft group-hover:-translate-y-1.5 md:h-auto" aria-hidden="true">
                    <x-mockup.sticker class="absolute top-[2%] left-[2%] w-[48%] -rotate-12 md:top-[12%]" shape="square" motif="sonne" />
                    <x-mockup.sticker class="absolute -right-[8%] -bottom-[30%] w-[66%] md:-bottom-[10%] md:w-[78%]" shape="circle" motif="monogramm" />
                </div>
            </div>

            <div class="group relative grid overflow-hidden rounded-tile bg-stage-sand md:min-h-[22rem] md:grid-cols-2">
                <div class="flex flex-col justify-between gap-8 p-7 lg:p-10">
                    <div>
                        <h3 class="text-title">
                            <a href="{{ route('area.show', 'flyer') }}" class="after:absolute after:inset-0 after:rounded-tile">Flyer</a>
                        </h3>
                        <p class="mt-3 text-ink-soft">Von DIN A6 bis DIN A4, DIN lang und quadratisch – einzeln gezeigt oder im Stapel.</p>
                    </div>
                    <span class="inline-flex items-center gap-2 font-medium">
                        Flyer ansehen <x-ui.icon name="arrow-right" class="transition-transform duration-200 ease-soft group-hover:translate-x-1" />
                    </span>
                </div>
                <div class="pointer-events-none relative h-52 transition-transform duration-500 ease-soft group-hover:-translate-y-1.5 md:h-auto" aria-hidden="true">
                    <x-mockup.flyer class="absolute -bottom-[30%] -left-[2%] w-[42%] -rotate-6 md:-bottom-[14%] md:-left-[16%] md:w-[56%]" format="lang" motif="prozent" />
                    <x-mockup.flyer class="absolute right-[8%] -bottom-[24%] w-[54%] rotate-2 md:right-[6%] md:-bottom-[22%] md:w-[64%]" format="a5" motif="fest" :stack="true" />
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="page pb-16 lg:pb-24" aria-labelledby="ablauf">
        <x-ui.section-heading id="ablauf" overline="So funktioniert's" title="In drei Schritten zum fertigen Werbemittel" />
        <ol class="mt-10 grid gap-4 md:grid-cols-3">
            @foreach ($steps as $step)
                <li class="rounded-card border border-line p-7">
                    <span class="accent block text-5xl leading-none text-muted" aria-hidden="true">{{ $loop->iteration }}</span>
                    <h3 class="text-heading mt-6">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-ink-soft">{{ $step['text'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- A look at the assortment --}}
    <section class="page pb-16 lg:pb-24" aria-labelledby="sortiment">
        <x-ui.section-heading id="sortiment" overline="Aus dem Sortiment" title="Formen, Materialien und Formate">
            Jede Karte zeigt ein Beispielmotiv. Mit Ihrem eigenen Bild sehen Sie hier Ihr Werbemittel.
        </x-ui.section-heading>
        <ul class="mt-10 grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 xl:grid-cols-4">
            @foreach ($assortment as $item)
                <li class="group relative">
                    <x-mockup.stage :tone="$item['tone']">
                        @isset($item['sticker'])
                            <x-mockup.sticker
                                class="transition-transform duration-300 ease-soft group-hover:-translate-y-1.5"
                                :shape="$item['sticker']['shape']" :material="$item['sticker']['material']" :motif="$item['sticker']['motif']"
                                :finish="$item['sticker']['material'] === 'kraft' ? 'matte' : 'gloss'"
                            />
                        @else
                            <x-mockup.flyer
                                class="transition-transform duration-300 ease-soft group-hover:-translate-y-1.5"
                                :format="$item['flyer']['format']" :motif="$item['flyer']['motif']" :stack="$item['flyer']['stack']"
                                :gloss="$item['flyer']['gloss'] ?? false"
                            />
                        @endisset
                    </x-mockup.stage>
                    <h3 class="mt-4 font-medium">
                        <a href="{{ route('area.show', $item['area']) }}" class="after:absolute after:inset-0 after:rounded-card">{{ $item['title'] }}</a>
                    </h3>
                    <p class="mt-0.5 text-sm text-muted">{{ $item['meta'] }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Questions --}}
    <section class="page pb-16 lg:pb-24" aria-labelledby="fragen">
        <div class="grid gap-10 lg:grid-cols-[1fr_1.4fr] lg:gap-16">
            <x-ui.section-heading id="fragen" overline="Häufige Fragen" title="Gut zu wissen" />
            <div class="divide-y divide-line border-y border-line">
                @foreach ($questions as $item)
                    <details class="group">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-5 text-lg font-medium [&::-webkit-details-marker]:hidden">
                            {{ $item['question'] }}
                            <x-ui.icon name="plus" class="text-muted transition-transform duration-200 ease-soft group-open:rotate-45" />
                        </summary>
                        <p class="max-w-2xl pb-6 text-ink-soft">{{ $item['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Closing call to action --}}
    <section class="page pb-16 lg:pb-24">
        <div class="on-night relative overflow-hidden rounded-tile bg-night px-7 py-14 text-on-night lg:px-16 lg:py-20">
            <div class="relative z-10 max-w-xl">
                <h2 class="text-title">Zeigen Sie uns <span class="accent text-[1.06em] text-gold">Ihr Motiv.</span></h2>
                <p class="text-lead mt-4 text-on-night-muted">
                    Ein Bild genügt, um zu sehen, wie es als Sticker und als Flyer wirkt.
                </p>
                <div class="mt-8">
                    <x-ui.motif-button variant="on-night" size="lg">Motiv wählen</x-ui.motif-button>
                </div>
            </div>
            <div class="pointer-events-none absolute right-[3%] -bottom-[34%] hidden w-[21rem] md:block lg:w-[24rem]" aria-hidden="true">
                <x-mockup.sticker class="absolute top-[4%] -left-[42%] w-[58%] -rotate-6" shape="circle" motif="aktion" />
                <x-mockup.sticker shape="square" motif="wellen" class="rotate-6" />
            </div>
        </div>
    </section>
</x-layouts.storefront>
