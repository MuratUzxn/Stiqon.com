<?php
/**
 * Reports the chosen motif at the bottom of the screen and offers to remove it.
 */

defined('ABSPATH') || exit;
?>
<div x-data="motifStatus" data-needs-script class="pointer-events-none fixed inset-x-0 bottom-5 z-50 flex flex-col items-center gap-2 px-4">
    <?php
    // What happened, for screen readers. Both regions are part of the page from the
    // start and only their text changes: a region that first appears together with
    // its text is often not read out.
    ?>
    <p class="sr-only" role="status" x-text="announcement"></p>
    <p class="sr-only" role="alert" x-text="error"></p>

    <?php // The same for the eye: a mistake, or something to know. It stays until it is closed or a motif is chosen. ?>
    <div
        x-show="hasMessage" x-cloak
        class="on-night pointer-events-auto flex max-w-full items-center gap-1 rounded-card bg-night py-1.5 pr-1.5 pl-5 text-on-night"
    >
        <p class="text-label py-1.5" x-text="message" aria-hidden="true"></p>
        <button
            type="button" x-on:click="dismiss" aria-label="Meldung schließen"
            class="inline-flex size-9 shrink-0 items-center justify-center rounded-full transition-colors hover:bg-white/14"
        ><?php echo stiqon_icon('x'); ?></button>
    </div>

    <div
        x-show="active" x-cloak x-transition.opacity.duration.200ms role="group" aria-label="Ihr Motiv"
        class="on-night pointer-events-auto flex max-w-full items-center gap-3 rounded-full bg-night p-2 text-on-night"
    >
        <img x-bind:src="url" alt="" class="size-10 shrink-0 rounded-full bg-white object-cover">
        <p class="min-w-0 truncate text-[0.9375rem]">
            <span class="text-on-night-muted">Ihr Motiv:</span>
            <span x-text="name" class="font-semibold"></span>
        </p>
        <button
            type="button" x-on:click="clear"
            class="text-action inline-flex h-10 shrink-0 items-center rounded-full bg-paper px-4 text-sm text-ink transition-colors hover:bg-mist-deep"
        >Entfernen</button>
    </div>
</div>
