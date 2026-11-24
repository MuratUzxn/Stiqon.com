<?php
/**
 * Reports the chosen motif at the bottom of the screen and offers to remove it.
 */

defined('ABSPATH') || exit;
?>
<div x-data="motifStatus" class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 px-4">
    <p
        x-show="hasError" x-cloak x-text="error" role="alert"
        class="pointer-events-auto rounded-full bg-night px-4 py-2.5 text-sm text-on-night shadow-lg"
    ></p>
    <div
        x-show="active" x-cloak x-transition.opacity.duration.200ms role="status"
        class="on-night pointer-events-auto flex max-w-full items-center gap-3 rounded-full bg-night py-1.5 pr-1.5 pl-1.5 text-on-night shadow-lg"
    >
        <img x-bind:src="url" alt="" class="size-9 shrink-0 rounded-full bg-white object-cover">
        <p class="min-w-0 truncate text-sm">
            <span class="text-on-night-muted">Ihr Motiv:</span>
            <span x-text="name" class="font-medium"></span>
        </p>
        <button
            type="button" x-on:click="clear"
            class="inline-flex h-9 shrink-0 items-center rounded-full bg-on-night/15 px-3.5 text-sm font-medium hover:bg-on-night/25"
        >Entfernen</button>
    </div>
</div>
