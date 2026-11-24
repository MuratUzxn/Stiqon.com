<?php
/**
 * Decorative: a slowly drifting wall of mockups. It only moves while on screen
 * and stands still for visitors who prefer reduced motion.
 *
 * @var array{class?: string} $args
 */

defined('ABSPATH') || exit;

// Three columns of mockups. Each column is rendered twice in a row so the
// upward or downward drift can loop without a visible seam.
$columns = [
    ['direction' => 'animate-wall-up', 'cards' => [
        ['tone' => 'mist', 'sticker' => ['shape' => 'circle', 'material' => 'vinyl', 'motif' => 'siegel']],
        ['tone' => 'sand', 'flyer' => ['format' => 'a5', 'motif' => 'eroeffnung', 'stack' => true]],
        ['tone' => 'sky', 'sticker' => ['shape' => 'rect', 'material' => 'vinyl', 'motif' => 'wellen']],
        ['tone' => 'mist', 'sticker' => ['shape' => 'square', 'material' => 'holo', 'motif' => 'raster']],
    ]],
    ['direction' => 'animate-wall-down', 'cards' => [
        ['tone' => 'sage', 'sticker' => ['shape' => 'circle', 'material' => 'vinyl', 'motif' => 'monogramm']],
        ['tone' => 'mist', 'sticker' => ['shape' => 'contour', 'material' => 'vinyl', 'motif' => 'sprechblase']],
        ['tone' => 'mist', 'flyer' => ['format' => 'a6', 'motif' => 'fest', 'stack' => false]],
        ['tone' => 'blush', 'sticker' => ['shape' => 'oval', 'material' => 'vinyl', 'motif' => 'danke']],
    ]],
    ['direction' => 'animate-wall-up', 'cards' => [
        ['tone' => 'sand', 'sticker' => ['shape' => 'square', 'material' => 'vinyl', 'motif' => 'sonne', 'peel' => true]],
        ['tone' => 'mist', 'sticker' => ['shape' => 'circle', 'material' => 'vinyl', 'motif' => 'aktion']],
        ['tone' => 'sky', 'flyer' => ['format' => 'quadrat', 'motif' => 'kursplan', 'stack' => true]],
        ['tone' => 'sand', 'sticker' => ['shape' => 'circle', 'material' => 'kraft', 'motif' => 'blatt', 'finish' => 'matte']],
    ]],
];
?>
<div
    x-data="cardWall" x-bind:data-paused="paused" aria-hidden="true"
    class="<?php echo esc_attr(stiqon_classes(['group wall-fade grid h-[38rem] grid-cols-3 gap-4 overflow-hidden', $args['class'] ?? ''])); ?>"
>
    <?php foreach ($columns as $column) : ?>
        <div class="flex flex-col gap-4 will-change-transform group-data-paused:[animation-play-state:paused] <?php echo esc_attr($column['direction']); ?>">
            <?php foreach ([1, 2] as $pass) : ?>
                <?php foreach ($column['cards'] as $card) : ?>
                    <?php
                    $mockup = isset($card['sticker']) ? stiqon_sticker($card['sticker']) : stiqon_flyer($card['flyer']);

                    echo stiqon_stage($mockup, $card['tone'], 'shrink-0');
                    ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
