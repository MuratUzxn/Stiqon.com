@props(['title', 'overline' => null, 'id' => null, 'level' => 2])
{{-- Overline, title and an optional lead paragraph (the slot) for a page section. --}}
<div {{ $attributes->class(['max-w-3xl']) }}>
    @if ($overline)
        <p class="text-overline text-muted">{{ $overline }}</p>
    @endif
    <h{{ $level }} @if ($id) id="{{ $id }}" @endif @class(['text-title', 'mt-3' => $overline])>{{ $title }}</h{{ $level }}>
    @if (! $slot->isEmpty())
        <p class="text-lead mt-4 text-ink-soft">{{ $slot }}</p>
    @endif
</div>
