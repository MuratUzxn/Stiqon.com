<?php
/**
 * An area of the shop (Sticker, Visitenkarten, Plakate …), laid out like a
 * category page of mobbin.com: path, large centred title, a sentence, filters
 * as pills, then a dense grid of cards – one per product and per option that
 * can be seen in the drawing (a sticker's material, a flyer's paper, a shirt's colour).
 *
 * @var array{name: string, lead: string, category: string, other: array{label: string, url: string}|null} $args
 */

defined('ABSPATH') || exit;

$gallery = stiqon_gallery($args['category']);
$selection = $gallery['selection'];
$axis = $gallery['axis'];
$parameter = $axis['parameter'] ?? null;
$page = (string) get_permalink();

/** Address of this page with one filter changed; an empty value takes the filter away. */
$with = static function (string $filter, string $value) use ($selection, $parameter, $page): string {
    $query = array_filter([
        'produkt' => $filter === 'produkt' ? $value : $selection['produkt'],
        (string) $parameter => $filter === 'option' ? $value : $selection['option'],
    ], static fn (?string $chosen): bool => $chosen !== null && $chosen !== '');

    return $query === [] ? $page : add_query_arg(array_map('rawurlencode', $query), $page);
};

$matches = static fn (array $card): bool => ($selection['produkt'] === null || $card['slug'] === $selection['produkt'])
    && ($selection['option'] === null || $card['option'] === $selection['option']);

$total = count($gallery['cards']);
$shown = count(array_filter($gallery['cards'], $matches));
$count = $shown === $total ? sprintf('%d Ausführungen', $total) : sprintf('%d von %d Ausführungen', $shown, $total);
?>
<section class="wrap pt-8 pb-10 text-center lg:pt-14 lg:pb-12">
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
        <?php if ($args['other'] !== null) : ?>
            <?php echo stiqon_button(esc_html($args['other']['label']), ['variant' => 'secondary', 'href' => $args['other']['url']]); ?>
        <?php endif; ?>
    </div>
</section>

<?php if ($total === 0) : ?>
    <section class="wrap pb-16 lg:pb-28">
        <p class="mx-auto max-w-xl rounded-card bg-mist px-6 py-5 text-center text-muted">
            Hier erscheinen die Produkte, sobald sie im Shop angelegt sind.
        </p>
    </section>
<?php else : ?>
    <?php // The filters work as plain links; with the script they filter in place. ?>
    <section class="wrap pb-16 lg:pb-28" aria-labelledby="galerie" x-data="galleryFilter" data-page="<?php echo esc_url($page); ?>" data-option-parameter="<?php echo esc_attr((string) $parameter); ?>">
        <h2 id="galerie" class="sr-only">Alle Ausführungen</h2>

        <div class="flex flex-col items-center gap-3">
            <div class="flex flex-wrap items-center justify-center gap-2" role="group" aria-label="Nach Produkt filtern">
                <?php echo stiqon_chip('Alle', ['href' => $with('produkt', ''), 'active' => $selection['produkt'] === null, 'attributes' => ['data-filter' => 'produkt', 'data-value' => '', 'x-on:click' => 'choose']]); ?>
                <?php foreach ($gallery['products'] as $product) : ?>
                    <?php echo stiqon_chip($product['name'], ['href' => $with('produkt', $product['slug']), 'active' => $selection['produkt'] === $product['slug'], 'attributes' => ['data-filter' => 'produkt', 'data-value' => $product['slug'], 'x-on:click' => 'choose']]); ?>
                <?php endforeach; ?>
            </div>

            <?php if ($axis !== null) : ?>
                <div class="flex flex-wrap items-center justify-center gap-2" role="group" aria-label="<?php echo esc_attr('Nach '.$axis['label'].' filtern'); ?>">
                    <?php echo stiqon_chip('Jedes '.$axis['label'], ['href' => $with('option', ''), 'active' => $selection['option'] === null, 'attributes' => ['data-filter' => 'option', 'data-value' => '', 'x-on:click' => 'choose']]); ?>
                    <?php foreach ($axis['options'] as $option) : ?>
                        <?php echo stiqon_chip($option['name'], ['href' => $with('option', $option['slug']), 'active' => $selection['option'] === $option['slug'], 'attributes' => ['data-filter' => 'option', 'data-value' => $option['slug'], 'x-on:click' => 'choose']]); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <p class="text-label mt-6 text-center text-muted">
            <span role="status" data-count><?php echo esc_html($count); ?></span>
            · Preise inkl. MwSt., zzgl. Versand
        </p>

        <ul class="mt-8 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-4 md:grid-cols-3 lg:mt-10 lg:grid-cols-4" data-cards x-on:click="open">
            <?php foreach ($gallery['cards'] as $card) : ?>
                <?php echo stiqon_product_card($card, ! $matches($card)); ?>
            <?php endforeach; ?>
        </ul>

        <p class="mx-auto mt-10 max-w-xl rounded-card bg-mist px-6 py-5 text-center text-muted" data-empty<?php echo $shown > 0 ? ' hidden' : ''; ?>>
            Zu dieser Auswahl gibt es keine Ausführung.
            <a href="<?php echo esc_url($page); ?>" class="font-semibold text-ink underline underline-offset-4">Alle anzeigen</a>
        </p>

        <?php get_template_part('template-parts/quick-view'); ?>

        <?php if ($gallery['examples']) : ?>
            <p class="text-label mx-auto mt-12 max-w-xl text-center text-muted">
                Beispielsortiment mit Beispielpreisen. Auswahl und Preise kommen später vom Drucklieferanten.
            </p>
        <?php endif; ?>
    </section>
<?php endif; ?>
