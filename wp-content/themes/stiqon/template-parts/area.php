<?php
/**
 * An area of the shop (Sticker, Flyer), laid out like a category page of
 * mobbin.com: path, large centred title, a sentence, then the cards. For now
 * the cards are examples; the product gallery replaces them.
 *
 * @var array{name: string, lead: string, other: array{label: string, url: string}, cards: list<array{title: string, meta: string, sticker?: array, flyer?: array}>} $args
 */

defined('ABSPATH') || exit;
?>
<section class="wrap pt-8 pb-12 text-center lg:pt-14 lg:pb-16">
    <nav aria-label="Pfad" class="text-label text-muted">
        <ol class="flex items-center justify-center gap-1.5">
            <li><a href="<?php echo esc_url(home_url('/')); ?>" class="rounded-sm transition-colors hover:text-ink">Start</a></li>
            <li aria-hidden="true">›</li>
            <li aria-current="page" class="text-ink"><?php echo esc_html($args['name']); ?></li>
        </ol>
    </nav>
    <h1 class="text-display mt-4"><?php echo esc_html($args['name']); ?></h1>
    <p class="text-lead mx-auto mt-6 max-w-xl text-muted"><?php echo esc_html($args['lead']); ?></p>
    <div class="mt-8 flex flex-wrap items-center justify-center gap-2">
        <?php echo stiqon_motif_button('Motiv wählen'); ?>
        <?php echo stiqon_button(esc_html($args['other']['label']), ['variant' => 'secondary', 'href' => $args['other']['url']]); ?>
    </div>
</section>

<section class="wrap pb-16 lg:pb-28">
    <p class="mx-auto max-w-xl rounded-card bg-mist px-6 py-5 text-center text-muted">
        Die Galerie mit allen Produkten entsteht gerade. Die Karten unten zeigen bereits, wie Ihr Motiv wirken wird.
    </p>

    <ul class="mt-8 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-4 lg:mt-12 lg:grid-cols-3" aria-label="Beispiele">
        <?php foreach ($args['cards'] as $card) : ?>
            <li>
                <?php
                $mockup = isset($card['sticker']) ? stiqon_sticker($card['sticker']) : stiqon_flyer($card['flyer']);

                echo stiqon_stage($mockup);
                ?>
                <p class="mt-3 font-semibold"><?php echo esc_html($card['title']); ?></p>
                <p class="text-label text-muted"><?php echo esc_html($card['meta']); ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
