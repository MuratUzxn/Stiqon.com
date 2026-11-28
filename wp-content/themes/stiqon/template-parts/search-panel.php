<?php
/**
 * The search that opens over the page: a field, and below it what fits, while
 * the visitor types. The script for it (assets/src/js/search.js) is fetched
 * when the search is first asked for; it takes this markup out of its
 * <template> and brings it to life. Until then – and without script – none of
 * it is part of the page, and the search control of the header is a link to
 * the results page.
 *
 * Someone who is logged in gets a token along, so the request for what there
 * is to find says who it is from: before the shop opens, only its managers
 * are told (see Search\Index in the plugin).
 */

defined('ABSPATH') || exit;

// Key caps: white on the grey bar, like the one in the header's search control.
$key = 'inline-flex h-6 min-w-6 items-center justify-center rounded-md bg-paper px-1.5 font-sans text-xs font-semibold';
?>
<template id="suche-vorlage">
    <dialog
        id="suche" aria-label="Suche" x-data="searchPanel" x-on:click="backdrop"
        data-index="<?php echo esc_url(stiqon_search_index_url()); ?>"<?php echo stiqon_attributes(['data-nonce' => is_user_logged_in() ? wp_create_nonce('wp_rest') : null]); ?>

        class="search-panel mx-auto mt-[max(0.75rem,10vh)] mb-auto w-[min(40rem,calc(100vw-1.5rem))] max-w-none overflow-hidden rounded-card bg-paper p-0 text-ink"
    >
        <form role="search" action="<?php echo esc_url(home_url('/')); ?>" method="get">
            <?php // The line below the field is its focus mark: the field itself has no ring. ?>
            <div class="flex items-center gap-3 py-2 pr-2 pl-5 shadow-[inset_0_-1px_0_var(--color-line)] has-[input:focus-visible]:shadow-[inset_0_-2px_0_var(--color-ink)]">
                <label for="suche-feld" class="shrink-0 text-muted">
                    <span class="sr-only">Suchbegriff</span>
                    <?php echo stiqon_icon('search', 'size-6'); ?>
                </label>
                <input
                    id="suche-feld" type="search" name="s" maxlength="<?php echo esc_attr((string) STIQON_SEARCH_MAX_LENGTH); ?>" placeholder="Visitenkarten, Plakate, Material …"
                    autocomplete="off" autocapitalize="off" spellcheck="false" enterkeyhint="search"
                    role="combobox" aria-autocomplete="list" aria-controls="suche-treffer" aria-expanded="false"
                    class="h-12 min-w-0 flex-1 bg-transparent text-xl font-medium outline-none placeholder:font-normal placeholder:text-muted"
                    x-ref="field" x-model="query" x-on:input="search"
                    x-bind:aria-expanded="listed" x-bind:aria-activedescendant="active"
                    x-on:keydown.arrow-down.prevent="next" x-on:keydown.arrow-up.prevent="previous" x-on:keydown.enter="go"
                >
                <button type="button" x-on:click="dismiss" aria-label="Suche schließen" class="inline-flex size-11 shrink-0 items-center justify-center rounded-full transition-colors duration-200 ease-soft hover:bg-ink/8">
                    <?php echo stiqon_icon('x'); ?>
                </button>
            </div>

            <p id="suche-titel" class="text-label px-5 pt-4 pb-1 text-muted" x-show="listed" x-text="heading"></p>
            <div id="suche-treffer" role="listbox" aria-labelledby="suche-titel" class="max-h-[min(25rem,50vh)] overflow-y-auto overscroll-contain px-2 pb-2" x-show="listed">
                <template x-for="hit in hits" x-bind:key="hit.id">
                    <a
                        role="option" tabindex="-1" x-bind:id="hit.id" x-bind:href="hit.u" x-bind:aria-selected="hit.selected"
                        x-bind:data-index="hit.index" x-on:mousemove="point"
                        class="flex items-center gap-3 rounded-control px-3 py-2 outline-none aria-selected:bg-mist"
                    >
                        <span class="search-glyph" x-bind:data-glyph="hit.g" aria-hidden="true"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold" x-text="hit.n"></span>
                            <span class="text-label block truncate text-muted" x-text="hit.s"></span>
                        </span>
                        <span class="text-label shrink-0 whitespace-nowrap text-muted" x-text="hit.p"></span>
                    </a>
                </template>
            </div>

            <div class="px-5 py-7 text-center" x-show="nothing">
                <?php if (stiqon_shop_is_concealed()) : ?>
                    <p class="font-semibold">Der Shop öffnet bald.</p>
                    <p class="text-label mx-auto mt-1 max-w-sm text-muted">Bis dahin finden Sie hier noch keine Produkte.</p>
                <?php else : ?>
                    <p class="font-semibold">Dazu haben wir nichts gefunden.</p>
                    <p class="text-label mx-auto mt-1 max-w-sm text-muted">Versuchen Sie es mit einer Form, einem Format oder einem Material – etwa „rund“, „DIN A5“ oder „Kraftpapier“.</p>
                <?php endif; ?>
            </div>

            <p class="text-label px-5 py-5 text-muted" x-show="unavailable">
                Die Vorschläge lassen sich gerade nicht laden. Mit der Eingabetaste suchen Sie trotzdem.
            </p>

            <div class="flex min-h-14 items-center justify-between gap-4 bg-mist py-2 pr-5 pl-3" x-show="onward">
                <button type="submit" class="text-action inline-flex h-10 items-center gap-2 rounded-full px-3 transition-colors duration-200 ease-soft hover:bg-ink/8">
                    Alle Treffer anzeigen <?php echo stiqon_icon('arrow-right'); ?>
                </button>
                <p class="text-label flex items-center gap-1.5 text-muted max-sm:hidden pointer-coarse:hidden" aria-hidden="true">
                    <kbd class="<?php echo esc_attr($key); ?>">↑</kbd><kbd class="<?php echo esc_attr($key); ?>">↓</kbd> wählen
                    <kbd class="<?php echo esc_attr($key); ?> ml-2">esc</kbd> schließen
                </p>
            </div>

            <p class="sr-only" role="status" x-text="announcement"></p>
        </form>
    </dialog>
</template>
