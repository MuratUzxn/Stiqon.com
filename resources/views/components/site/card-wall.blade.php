@php
    // Three columns of mockups. Each column is rendered twice in a row so the
    // upward or downward drift can loop without a visible seam.
    $columns = [
        ['direction' => 'animate-wall-up', 'cards' => [
            ['kind' => 'sticker', 'tone' => 'mist', 'shape' => 'circle', 'material' => 'vinyl', 'motif' => 'siegel'],
            ['kind' => 'flyer', 'tone' => 'sand', 'format' => 'a5', 'motif' => 'eroeffnung', 'stack' => true],
            ['kind' => 'sticker', 'tone' => 'sky', 'shape' => 'rect', 'material' => 'vinyl', 'motif' => 'wellen'],
            ['kind' => 'sticker', 'tone' => 'mist', 'shape' => 'square', 'material' => 'holo', 'motif' => 'raster'],
        ]],
        ['direction' => 'animate-wall-down', 'cards' => [
            ['kind' => 'sticker', 'tone' => 'sage', 'shape' => 'circle', 'material' => 'vinyl', 'motif' => 'monogramm'],
            ['kind' => 'sticker', 'tone' => 'mist', 'shape' => 'contour', 'material' => 'vinyl', 'motif' => 'sprechblase'],
            ['kind' => 'flyer', 'tone' => 'mist', 'format' => 'a6', 'motif' => 'fest', 'stack' => false],
            ['kind' => 'sticker', 'tone' => 'blush', 'shape' => 'oval', 'material' => 'vinyl', 'motif' => 'danke'],
        ]],
        ['direction' => 'animate-wall-up', 'cards' => [
            ['kind' => 'sticker', 'tone' => 'sand', 'shape' => 'square', 'material' => 'vinyl', 'motif' => 'sonne', 'peel' => true],
            ['kind' => 'sticker', 'tone' => 'mist', 'shape' => 'circle', 'material' => 'vinyl', 'motif' => 'aktion'],
            ['kind' => 'flyer', 'tone' => 'sky', 'format' => 'quadrat', 'motif' => 'kursplan', 'stack' => true],
            ['kind' => 'sticker', 'tone' => 'sand', 'shape' => 'circle', 'material' => 'kraft', 'motif' => 'blatt'],
        ]],
    ];
@endphp
{{-- Decorative: a slowly drifting wall of mockups. It only moves while on screen and
     stands still for visitors who prefer reduced motion. --}}
<div
    x-data="cardWall" x-bind:data-paused="paused" aria-hidden="true"
    {{ $attributes->class(['group wall-fade grid h-[38rem] grid-cols-3 gap-4 overflow-hidden']) }}
>
    @foreach ($columns as $column)
        <div class="flex flex-col gap-4 will-change-transform group-data-paused:[animation-play-state:paused] {{ $column['direction'] }}">
            @foreach ([1, 2] as $pass)
                @foreach ($column['cards'] as $card)
                    <x-mockup.stage :tone="$card['tone']" class="shrink-0">
                        @if ($card['kind'] === 'sticker')
                            <x-mockup.sticker
                                 :shape="$card['shape']" :material="$card['material']" :motif="$card['motif']"
                                :peel="$card['peel'] ?? false" :finish="$card['material'] === 'kraft' ? 'matte' : 'gloss'"
                            />
                        @else
                            <x-mockup.flyer :format="$card['format']" :motif="$card['motif']" :stack="$card['stack']" />
                        @endif
                    </x-mockup.stage>
                @endforeach
            @endforeach
        </div>
    @endforeach
</div>
