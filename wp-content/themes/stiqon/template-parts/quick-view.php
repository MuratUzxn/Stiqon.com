<?php
/**
 * The quick view of a gallery: a card opened large over the page, with arrows
 * to the cards before and after it – the way mobbin.com opens a screen. The
 * page's script fills it from the card that was clicked; without script, and
 * on small screens, a card simply leads to its product.
 */

defined('ABSPATH') || exit;

$arrow = 'text-action inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-ink/8 text-ink transition-colors duration-200 ease-soft hover:bg-ink/14 aria-disabled:pointer-events-none aria-disabled:opacity-40';
?>
<dialog
    x-ref="viewer" aria-labelledby="schnellansicht-titel"
    class="quick-view m-auto w-[min(68rem,calc(100vw-2rem))] max-w-none rounded-tile bg-paper p-0 text-ink"
    x-on:close="closed" x-on:click="backdrop"
    x-on:keydown.arrow-left.prevent="previous" x-on:keydown.arrow-right.prevent="next"
>
    <div class="grid gap-6 p-4 sm:p-6 md:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)] md:gap-10 md:p-8">
        <div class="relative flex aspect-square items-center justify-center overflow-hidden rounded-card bg-mist p-[9%]" x-ref="stage"></div>

        <div class="flex flex-col md:py-2">
            <div class="flex items-start justify-between gap-4">
                <p class="text-label pt-3 text-muted" x-text="position" role="status"></p>
                <button type="button" x-on:click="dismiss" aria-label="Schnellansicht schließen" class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-ink/8 transition-colors duration-200 ease-soft hover:bg-ink/14">
                    <?php echo stiqon_icon('x'); ?>
                </button>
            </div>

            <h2 id="schnellansicht-titel" class="text-title mt-4" x-text="title"></h2>
            <p class="text-lead mt-2 text-muted" x-text="subtitle"></p>
            <p class="mt-5 text-2xl font-[650]" x-text="price"></p>
            <p class="text-label mt-1 text-muted">inkl. MwSt., zzgl. Versand</p>
            <p class="mt-5 text-muted" x-text="summary"></p>

            <div class="mt-8 flex flex-wrap items-center gap-2">
                <a x-bind:href="url" class="text-action inline-flex h-11 items-center justify-center gap-2 rounded-full bg-ink px-4 text-paper transition-colors duration-200 ease-soft hover:bg-ink-soft">
                    Produkt ansehen <?php echo stiqon_icon('arrow-right'); ?>
                </a>
                <?php echo stiqon_motif_button('Eigenes Motiv', ['variant' => 'secondary']); ?>
            </div>

            <div class="mt-auto flex items-center gap-2 pt-8">
                <button type="button" x-on:click="previous" x-bind:aria-disabled="atStart" aria-label="Vorherige Ausführung" class="<?php echo esc_attr($arrow); ?>">
                    <?php echo stiqon_icon('chevron-left'); ?>
                </button>
                <button type="button" x-on:click="next" x-bind:aria-disabled="atEnd" aria-label="Nächste Ausführung" class="<?php echo esc_attr($arrow); ?>">
                    <?php echo stiqon_icon('chevron-right'); ?>
                </button>
                <p class="text-label ml-2 text-muted max-md:hidden">Auch mit den Pfeiltasten ← →</p>
            </div>
        </div>
    </div>
</dialog>
