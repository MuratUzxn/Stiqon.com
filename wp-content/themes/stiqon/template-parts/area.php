<?php
/**
 * An area of the shop (Sticker, Flyer). For now it shows what the area will
 * contain; the product gallery replaces the example cards.
 *
 * @var array{name: string, lead: string, cards: list<array{tone: string, sticker?: array, flyer?: array}>} $args
 */

defined('ABSPATH') || exit;
?>
<section class="wrap pt-10 pb-16 lg:pt-16 lg:pb-24">
    <p class="text-overline text-muted">Bereich</p>
    <h1 class="text-display mt-5"><?php echo esc_html($args['name']); ?></h1>
    <p class="text-lead mt-6 max-w-2xl text-ink-soft"><?php echo esc_html($args['lead']); ?></p>
    <div class="mt-9 flex flex-wrap items-center gap-3">
        <?php echo stiqon_motif_button('Motiv wählen', ['size' => 'lg']); ?>
    </div>

    <p class="mt-12 rounded-card border border-line p-6 text-ink-soft">
        Die Galerie mit allen Produkten entsteht gerade. Die Karten unten zeigen bereits, wie Ihr Motiv wirken wird.
    </p>

    <ul class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4" aria-label="Beispiele">
        <?php foreach ($args['cards'] as $card) : ?>
            <li>
                <?php
                $mockup = isset($card['sticker']) ? stiqon_sticker($card['sticker']) : stiqon_flyer($card['flyer']);

                echo stiqon_stage($mockup, $card['tone']);
                ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
