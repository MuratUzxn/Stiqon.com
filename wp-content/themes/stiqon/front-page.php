<?php
/**
 * The home page. Its composition follows the landing page of mobbin.com: a
 * centred headline with small product views set between the words, a grey
 * showcase panel, a two-tone statement over a row of large cards, labelled
 * tiles, three cards on a grey band, questions and a closing call.
 */

defined('ABSPATH') || exit;

$sticker = home_url('/sticker/');
$flyer = home_url('/flyer/');

/**
 * A product view set into a line of headline type. Decorative: the words carry
 * the meaning. Without the plugin there is no view, and no gap in the line either.
 */
$inline = static function (array $spec): string {
    $view = stiqon_sticker($spec);

    return $view === '' ? '' : '<span class="inline-mockup" aria-hidden="true">'.$view.'</span>';
};

// What the shop will offer, as shown in the showcase panel.
$lists = [
    'Formen' => ['Rund', 'Quadratisch', 'Rechteckig', 'Oval', 'Konturschnitt'],
    'Materialien' => ['Weißes Vinyl', 'Transparente Folie', 'Holografische Folie', 'Kraftpapier'],
    'Flyer-Formate' => ['DIN A6', 'DIN A5', 'DIN A4', 'DIN lang', 'Quadrat'],
];

$showcase = [
    ['sticker' => ['shape' => 'circle', 'motif' => 'siegel']],
    ['flyer' => ['format' => 'a5', 'motif' => 'eroeffnung', 'stack' => true]],
    ['sticker' => ['shape' => 'contour', 'motif' => 'sprechblase']],
];

$row = [
    ['sticker' => ['shape' => 'rect', 'motif' => 'wellen']],
    ['flyer' => ['format' => 'a6', 'motif' => 'fest']],
    ['sticker' => ['shape' => 'circle', 'material' => 'kraft', 'finish' => 'matte', 'motif' => 'blatt']],
    ['sticker' => ['shape' => 'oval', 'motif' => 'danke']],
    ['flyer' => ['format' => 'a4', 'motif' => 'karte', 'heavy' => true]],
    ['sticker' => ['shape' => 'circle', 'material' => 'clear', 'motif' => 'monogramm']],
];

// Fixed example tiles for this stage of the build; the catalogue replaces them.
$tiles = [
    ['label' => 'Runde Sticker', 'url' => $sticker, 'sticker' => ['shape' => 'circle', 'motif' => 'aktion']],
    ['label' => 'Quadratische Sticker', 'url' => $sticker, 'sticker' => ['shape' => 'square', 'material' => 'holo', 'motif' => 'raster']],
    ['label' => 'Sticker mit Konturschnitt', 'url' => $sticker, 'sticker' => ['shape' => 'contour', 'motif' => 'stern']],
    ['label' => 'Flyer DIN A6', 'url' => $flyer, 'flyer' => ['format' => 'a6', 'motif' => 'fest']],
    ['label' => 'Flyer DIN lang', 'url' => $flyer, 'flyer' => ['format' => 'lang', 'motif' => 'prozent', 'gloss' => true]],
    ['label' => 'Flyer Quadrat', 'url' => $flyer, 'flyer' => ['format' => 'quadrat', 'motif' => 'kursplan', 'stack' => true]],
];

$steps = [
    ['title' => 'Produkt wählen', 'text' => 'Form, Größe und Material für Sticker – Format und Papier für Flyer.'],
    ['title' => 'Motiv hinzufügen', 'text' => 'Ein eigenes Bild verwenden oder eine Vorlage anpassen.'],
    ['title' => 'Vorschau prüfen', 'text' => 'Sie sehen Ihr Motiv auf dem Produkt, bevor es in den Druck geht.'],
];

$questions = [
    ['question' => 'Sehe ich mein Motiv, bevor ich bestelle?', 'answer' => 'Ja. Sobald Sie ein Bild wählen, erscheint es auf allen Stickern und Flyern dieser Seite – in jeder Form und auf jedem Material.'],
    ['question' => 'Wird mein Bild dabei hochgeladen?', 'answer' => 'Nein. Für die Vorschau bleibt das Bild in Ihrem Browser. Es verlässt Ihr Gerät nicht.'],
    ['question' => 'Welche Dateien eignen sich für die Vorschau?', 'answer' => 'Bilder im Format PNG, JPEG oder WebP. Ein freigestelltes PNG zeigt am besten, wie ein Sticker mit Konturschnitt wirkt.'],
    ['question' => 'Welche Formen und Materialien zeigt die Vorschau?', 'answer' => 'Runde, quadratische, rechteckige und ovale Sticker sowie Sticker mit Konturschnitt – auf weißem Vinyl, transparenter und holografischer Folie und auf Kraftpapier. Flyer von DIN A6 bis DIN A4, DIN lang und quadratisch.'],
    ['question' => 'Kann ich schon bestellen?', 'answer' => 'Noch nicht. Der Shop wird gerade aufgebaut; die Bestellung folgt.'],
];

$mockup = static fn (array $card, string $class = ''): string => isset($card['sticker'])
    ? stiqon_sticker($card['sticker'], $class)
    : stiqon_flyer($card['flyer'], $class);

get_header();
?>
<?php // Hero: the headline, one action ?>
<section class="wrap pt-12 pb-14 text-center lg:pt-24 lg:pb-20">
    <?php // Two lines on wide screens, three on a phone – with the views between the words either way. ?>
    <h1 class="text-display">
        Werbemittel,<br class="sm:hidden">
        <?php echo $inline(['shape' => 'circle', 'motif' => 'monogramm']); ?> die<br class="max-sm:hidden">
        haften <?php echo $inline(['shape' => 'contour', 'motif' => 'sprechblase']); ?><br class="sm:hidden">
        bleiben.
    </h1>
    <p class="text-lead mx-auto mt-7 max-w-md text-muted lg:mt-9">
        Wählen Sie Ihr Motiv und sehen Sie es sofort auf Stickern und Flyern.
    </p>
    <div class="mt-9 flex flex-wrap items-center justify-center gap-2 lg:mt-11">
        <?php echo stiqon_motif_button('Motiv wählen'); ?>
        <?php echo stiqon_button('Sticker ansehen', ['variant' => 'secondary', 'href' => $sticker]); ?>
        <?php echo stiqon_button('Flyer ansehen', ['variant' => 'secondary', 'href' => $flyer]); ?>
    </div>
    <p class="text-label mt-5 text-muted">Ihr Bild bleibt in Ihrem Browser – es wird nichts hochgeladen.</p>
</section>

<?php // Showcase: what the shop holds, on a grey panel that the cards run out of ?>
<section class="wrap pb-16 lg:pb-24" aria-labelledby="schaufenster">
    <h2 id="schaufenster" class="sr-only">Das Sortiment im Überblick</h2>
    <div class="overflow-hidden rounded-tile bg-mist px-3 pt-3 sm:px-6 sm:pt-6 lg:px-10 lg:pt-10">
        <div class="-mb-10 rounded-t-card bg-paper px-5 pt-5 sm:px-7 sm:pt-7 lg:-mb-16">
            <div class="flex items-center gap-1">
                <a href="<?php echo esc_url($sticker); ?>" class="text-action inline-flex h-11 items-center rounded-full px-3 transition-opacity hover:opacity-60">Sticker</a>
                <a href="<?php echo esc_url($flyer); ?>" class="text-action inline-flex h-11 items-center rounded-full px-3 text-muted transition-opacity hover:opacity-60">Flyer</a>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-x-6 gap-y-7 px-3 sm:grid-cols-3">
                <?php foreach ($lists as $title => $items) : ?>
                    <?php // The list with the longest entries gets a full row on a phone. ?>
                    <div class="<?php echo esc_attr($title === 'Materialien' ? 'max-sm:order-last max-sm:col-span-2' : ''); ?>">
                        <h3 class="text-label text-muted"><?php echo esc_html($title); ?></h3>
                        <ul class="mt-2 font-semibold leading-snug">
                            <?php foreach ($items as $item) : ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:mt-10 lg:grid-cols-3">
                <?php foreach ($showcase as $index => $card) : ?>
                    <?php echo stiqon_stage($mockup($card), 'mist', $index === 2 ? 'max-lg:hidden' : '', 'portrait'); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php // The promise, in two tones, over a row of large cards ?>
<section class="pb-16 lg:pb-28" aria-labelledby="versprechen" x-data="cardRow">
    <div class="wrap flex items-end justify-between gap-8">
        <div class="max-w-3xl">
            <h2 id="versprechen" class="text-title">Erst ansehen, dann bestellen.</h2>
            <p class="text-title text-muted">
                Ein Bild genügt. Es erscheint auf jedem Sticker und jedem Flyer – in jeder Form, auf jedem Material.
            </p>
        </div>
        <?php // Shown once the script runs; without it the row still scrolls by touch, wheel and keyboard. ?>
        <div class="flex shrink-0 gap-2 max-md:hidden" x-cloak>
            <?php echo stiqon_button(stiqon_icon('arrow-left'), ['variant' => 'secondary', 'class' => 'w-11 !px-0', 'attributes' => ['aria-label' => 'Vorherige Beispiele', 'x-on:click' => 'previous', 'x-bind:disabled' => 'atStart']]); ?>
            <?php echo stiqon_button(stiqon_icon('arrow-right'), ['variant' => 'secondary', 'class' => 'w-11 !px-0', 'attributes' => ['aria-label' => 'Weitere Beispiele', 'x-on:click' => 'next', 'x-bind:disabled' => 'atEnd']]); ?>
        </div>
    </div>
    <div
        class="bleed-row mt-10 gap-3 sm:gap-4 lg:mt-14"
        role="region" aria-label="Beispiele: Motive auf Stickern und Flyern" tabindex="0"
        x-ref="row" x-on:scroll.passive="update"
    >
        <?php foreach ($row as $card) : ?>
            <?php echo stiqon_stage($mockup($card), 'mist', 'w-[70vw] max-w-[22rem] shrink-0 snap-start'); ?>
        <?php endforeach; ?>
    </div>
</section>

<?php // A look at the assortment ?>
<section class="wrap pb-16 lg:pb-28" aria-labelledby="sortiment">
    <?php echo stiqon_section_heading(['id' => 'sortiment', 'title' => 'Formen, Materialien und Formate']); ?>
    <ul class="mt-10 grid grid-cols-2 gap-3 sm:gap-4 lg:mt-14 lg:grid-cols-3">
        <?php foreach ($tiles as $tile) : ?>
            <li>
                <a
                    href="<?php echo esc_url($tile['url']); ?>"
                    class="group flex aspect-[4/5] flex-col items-center overflow-hidden rounded-tile bg-mist px-4 pt-6 text-center transition-colors hover:bg-mist-deep sm:aspect-square sm:pt-8"
                >
                    <span class="text-heading"><?php echo esc_html($tile['label']); ?></span>
                    <span class="<?php echo esc_attr(isset($tile['sticker']) ? 'w-[92%]' : 'w-[74%]'); ?> mt-auto translate-y-[14%] transition-transform duration-300 ease-soft group-hover:translate-y-[9%]" aria-hidden="true">
                        <?php echo $mockup($tile); ?>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<?php // How it works ?>
<section class="bg-mist py-16 lg:py-28" aria-labelledby="ablauf">
    <div class="wrap">
        <?php echo stiqon_section_heading(['id' => 'ablauf', 'title' => 'In drei Schritten zum fertigen Werbemittel']); ?>
        <ol class="mt-10 grid gap-3 sm:gap-4 md:grid-cols-3 lg:mt-14">
            <?php foreach ($steps as $index => $step) : ?>
                <li class="flex flex-col justify-between gap-8 rounded-card bg-paper p-7 md:min-h-56">
                    <span class="text-action flex size-9 items-center justify-center rounded-full bg-ink text-sm text-paper" aria-hidden="true"><?php echo esc_html((string) ($index + 1)); ?></span>
                    <div>
                        <h3 class="text-heading"><?php echo esc_html($step['title']); ?></h3>
                        <p class="mt-2 text-muted"><?php echo esc_html($step['text']); ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<?php // Questions ?>
<section class="wrap py-16 lg:py-28" aria-labelledby="fragen">
    <?php echo stiqon_section_heading(['id' => 'fragen', 'title' => 'Gut zu wissen']); ?>
    <div class="mx-auto mt-10 grid max-w-3xl gap-2 lg:mt-14">
        <?php foreach ($questions as $item) : ?>
            <details class="group rounded-card bg-mist">
                <summary class="text-heading flex cursor-pointer list-none items-center justify-between gap-6 rounded-card px-6 py-5 [&::-webkit-details-marker]:hidden">
                    <?php echo esc_html($item['question']); ?>
                    <?php echo stiqon_icon('plus', 'transition-transform duration-200 ease-soft group-open:rotate-45'); ?>
                </summary>
                <p class="max-w-2xl px-6 pb-6 text-muted"><?php echo esc_html($item['answer']); ?></p>
            </details>
        <?php endforeach; ?>
    </div>
</section>

<?php // Closing call ?>
<section class="wrap pt-6 pb-20 text-center lg:pt-10 lg:pb-32" aria-labelledby="abschluss">
    <h2 id="abschluss" class="text-cta">Zeigen Sie uns<br>Ihr Motiv.</h2>
    <p class="mx-auto mt-6 max-w-sm text-muted">
        Ein Bild genügt, um zu sehen, wie es als Sticker und als Flyer wirkt.
    </p>
    <div class="mt-9 flex flex-wrap items-center justify-center gap-2">
        <?php echo stiqon_motif_button('Motiv wählen'); ?>
        <?php echo stiqon_button('Sticker ansehen '.stiqon_icon('arrow-right'), ['variant' => 'secondary', 'href' => $sticker]); ?>
    </div>
</section>
<?php
get_footer();
