@props(['variant' => 'primary', 'size' => 'md'])
{{-- Lets the visitor choose an image. It is only shown on the mockups; nothing is uploaded.
     Classes passed in apply to the wrapper, so callers can hide or stretch the whole control. --}}
<span x-data="motifPicker" {{ $attributes->class(['inline-flex']) }}>
    <x-ui.button :variant="$variant" :size="$size" x-on:click="pick" class="w-full">
        <x-ui.icon name="image" />
        {{ $slot->isEmpty() ? 'Eigenes Motiv ausprobieren' : $slot }}
    </x-ui.button>
    <input
        x-ref="file" x-on:change="chosen" type="file" accept="image/png,image/jpeg,image/webp"
        class="sr-only" tabindex="-1" aria-hidden="true"
    >
</span>
