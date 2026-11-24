<x-layouts.storefront title="Musterbogen">
    <main class="page py-12">
        <h1 class="text-title">Musterbogen</h1>

        <section class="mt-10" aria-labelledby="mockups">
            <h2 id="mockups" class="text-heading">Mockups</h2>
            <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
                <x-mockup.stage tone="mist"><x-mockup.sticker class="w-[72%]" shape="circle" motif="siegel" /></x-mockup.stage>
                <x-mockup.stage tone="sand"><x-mockup.sticker class="w-[72%]" shape="square" motif="sonne" :peel="true" /></x-mockup.stage>
                <x-mockup.stage tone="sage"><x-mockup.sticker class="w-[72%]" shape="circle" motif="monogramm" finish="matte" /></x-mockup.stage>
                <x-mockup.stage tone="sky"><x-mockup.sticker class="w-[78%]" shape="rect" motif="wellen" /></x-mockup.stage>
                <x-mockup.stage tone="mist"><x-mockup.sticker class="w-[72%]" shape="square" motif="neu" /></x-mockup.stage>
                <x-mockup.stage tone="blush"><x-mockup.sticker class="w-[78%]" shape="oval" motif="danke" /></x-mockup.stage>
                <x-mockup.stage tone="mist"><x-mockup.sticker class="w-[72%]" shape="circle" motif="aktion" /></x-mockup.stage>
                <x-mockup.stage tone="sand"><x-mockup.sticker class="w-[72%]" shape="circle" material="kraft" motif="blatt" finish="matte" /></x-mockup.stage>
                <x-mockup.stage tone="mist"><x-mockup.sticker class="w-[72%]" shape="square" material="holo" motif="raster" /></x-mockup.stage>
                <x-mockup.stage tone="sky"><x-mockup.sticker class="w-[72%]" shape="circle" material="clear" motif="monogramm" /></x-mockup.stage>
                <x-mockup.stage tone="mist"><x-mockup.sticker class="w-[76%]" shape="contour" motif="sprechblase" /></x-mockup.stage>
                <x-mockup.stage tone="sage"><x-mockup.sticker class="w-[72%]" shape="contour" motif="stern" /></x-mockup.stage>
                <x-mockup.stage tone="mist"><x-mockup.sticker class="w-[72%]" shape="circle" material="holo" motif="siegel" /></x-mockup.stage>
                <x-mockup.stage tone="blush"><x-mockup.sticker class="w-[78%]" shape="rect" motif="punkt" :peel="true" /></x-mockup.stage>
                <x-mockup.stage tone="mist"><x-mockup.sticker class="w-[72%]" shape="circle" material="kraft" motif="siegel" finish="matte" /></x-mockup.stage>
                <x-mockup.stage tone="mist"><x-mockup.flyer class="w-[62%]" format="a6" motif="fest" /></x-mockup.stage>
                <x-mockup.stage tone="sand"><x-mockup.flyer class="w-[62%]" format="a5" motif="eroeffnung" :stack="true" /></x-mockup.stage>
                <x-mockup.stage tone="sage"><x-mockup.flyer class="w-[62%]" format="a4" motif="karte" :heavy="true" /></x-mockup.stage>
                <x-mockup.stage tone="mist"><x-mockup.flyer class="w-[62%]" format="lang" motif="prozent" :gloss="true" /></x-mockup.stage>
                <x-mockup.stage tone="sky"><x-mockup.flyer class="w-[62%]" format="quadrat" motif="kursplan" :stack="true" /></x-mockup.stage>
            </div>
        </section>
    </main>
</x-layouts.storefront>
