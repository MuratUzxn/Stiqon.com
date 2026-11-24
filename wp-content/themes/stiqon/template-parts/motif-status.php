<?php
/**
 * Reports the chosen motif at the bottom of the screen and offers to remove it.
 */

defined('ABSPATH') || exit;
?>
<div x-data="motifStatus" class="pointer-events-none fixed inset-x-0 bottom-5 z-50 flex flex-col items-center gap-2 px-4">
    <p
        x-show="hasError" x-cloak x-text="error" role="alert"
        class="text-label pointer-events-auto rounded-full bg-night px-5 py-3 text-on-night"
    ></p>
    <div
        x-show="active" x-cloak x-transition.opacity.duration.200ms role="status"
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
