<?php
/**
 * The home page. Its composition follows the landing page of mobbin.com: a
 * centred headline with small product views set between the words, a grey
 * showcase panel, a two-tone statement over a row of large cards, labelled
 * tiles, three cards on a grey band, questions and a closing call.
 */

defined('ABSPATH') || exit;

$sticker = stiqon_page_url('sticker');
$flyer = stiqon_page_url('flyer');
$assortment = home_url('/#werbemittel');

// The newest work the shop has to show; empty until the first reference is published.
$references = stiqon_references(3);

/**
 * A product view set into a line of headline type. Decorative: the words carry
 * the meaning. Only motifs without lettering are used here – text inside the
 * drawing would become part of the heading's text for search engines, reader
 * views and copy and paste. Without the plugin there is no view, and no gap in
 * the line either.
 */
$inline = static function (array $spec): string {
    $view = stiqon_sticker($spec);

    return $view === '' ? '' : '<span class="inline-mockup" aria-hidden="true">'.$view.'</span>';
};

// What the shop offers, as shown in the showcase panel. The list with the longest
// entries ("full") gets a row of its own on a phone.
$lists = [
    'Drucksachen' => ['entries' => ['Visitenkarten', 'Flyer und Faltblätter', 'Karten', 'Briefpapier', 'Broschüren', 'Kalender']],
    'Groß und sichtbar' => ['entries' => ['Sticker', 'Plakate', 'Roll-ups', 'Banner', 'Kundenstopper']],
    'Zum Anfassen' => ['entries' => ['Versandkartons', 'Tragetaschen', 'T-Shirts und Hoodies', 'Tassen und Flaschen'], 'full' => true],
];

$showcase = [
    ['type' => 'card', 'spec' => ['format' => 'visitenkarte', 'stack' => true]],
    ['type' => 'flyer', 'spec' => ['format' => 'a5', 'motif' => 'eroeffnung', 'stack' => true]],
    ['type' => 'sticker', 'spec' => ['shape' => 'contour', 'motif' => 'sprechblase']],
];

$row = [
    ['type' => 'sticker', 'spec' => ['shape' => 'rect', 'motif' => 'wellen']],
    ['type' => 'card', 'spec' => ['format' => 'postkarte', 'fold' => true]],
    ['type' => 'shirt', 'spec' => ['cut' => 'tshirt', 'color' => 'black', 'motif' => 'sonne']],
    ['type' => 'calendar', 'spec' => ['variant' => 'wall', 'motif' => 'blatt']],
    ['type' => 'flyer', 'spec' => ['format' => 'a4', 'motif' => 'karte', 'heavy' => true]],
    ['type' => 'mug', 'spec' => ['style' => 'classic', 'motif' => 'monogramm']],
];

// A few products from different areas; each tile leads to its area.
$tiles = [
    ['label' => 'Runde Sticker', 'url' => $sticker, 'type' => 'sticker', 'spec' => ['shape' => 'circle', 'motif' => 'aktion'], 'width' => 'w-[92%]'],
    ['label' => 'Visitenkarten', 'url' => stiqon_page_url('visitenkarten'), 'type' => 'card', 'spec' => ['format' => 'visitenkarte', 'finish' => 'gold'], 'width' => 'w-[88%]'],
    ['label' => 'Flyer DIN lang', 'url' => $flyer, 'type' => 'flyer', 'spec' => ['format' => 'lang', 'motif' => 'prozent', 'gloss' => true], 'width' => 'w-[74%]'],
    ['label' => 'Plakate', 'url' => stiqon_page_url('plakate'), 'type' => 'poster', 'spec' => ['format' => 'a2', 'motif' => 'kursplan'], 'width' => 'w-[86%]'],
    ['label' => 'Roll-ups', 'url' => stiqon_page_url('werbetechnik'), 'type' => 'rollup', 'spec' => ['format' => '85x200', 'motif' => 'prozent'], 'width' => 'w-[86%]'],
    ['label' => 'Baumwolltaschen', 'url' => stiqon_page_url('textilien'), 'type' => 'tote', 'spec' => ['handles' => 'long', 'motif' => 'raster'], 'width' => 'w-[88%]'],
];

$steps = [
    ['title' => 'Produkt wählen', 'text' => 'Format, Material und Auflage – für Visitenkarte, Plakat, T-Shirt und alles dazwischen.'],
    ['title' => 'Motiv hinzufügen', 'text' => 'Ein eigenes Bild verwenden oder eine Vorlage anpassen.'],
    ['title' => 'Vorschau prüfen', 'text' => 'Sie sehen Ihr Motiv auf dem Produkt, bevor es in den Druck geht.'],
];

$questions = [
    ['question' => 'Sehe ich mein Motiv, bevor ich bestelle?', 'answer' => 'Ja. Sobald Sie ein Bild wählen, erscheint es auf allen Produkten dieser Seite – in jedem Format und auf jedem Material.'],
    ['question' => 'Wird mein Bild dabei hochgeladen?', 'answer' => 'Nein. Für die Vorschau bleibt das Bild in Ihrem Browser. Es verlässt Ihr Gerät nicht.'],
    ['question' => 'Welche Dateien eignen sich für die Vorschau?', 'answer' => 'Bilder im Format PNG, JPEG oder WebP. Ein freigestelltes PNG zeigt am besten, wie ein Sticker mit Konturschnitt wirkt.'],
    ['question' => 'Welche Produkte zeigt die Vorschau?', 'answer' => 'Alle: Sticker in fünf Formen und auf vier Materialien, Flyer und Faltblätter, Visitenkarten und Karten, Briefpapier, Broschüren, Plakate, Kalender, Roll-ups, Banner und Kundenstopper, Versandkartons und Tragetaschen, T-Shirts, Hoodies und Taschen, Tassen, Trinkflaschen und Bierdeckel.'],
];

// Asked only while WooCommerce announces the shop; once it is open, the answer is the shop.
if (get_option('woocommerce_coming_soon') === 'yes') {
    $questions[] = ['question' => 'Kann ich schon bestellen?', 'answer' => 'Noch nicht. Der Shop wird gerade aufgebaut; die Bestellung folgt.'];
}

$mockup = static fn (array $card, string $class = ''): string => stiqon_mockup($card['type'], $card['spec'], $class);

stiqon_declare_lean_page();
get_header();
?>
<?php // Hero: the headline, one action ?>
<section class="wrap pt-12 pb-14 text-center lg:pt-24 lg:pb-20">
    <?php // Two lines on wide screens, three on a phone – with the views between the words either way. ?>
    <h1 class="text-display">
        Werbemittel,<br class="sm:hidden">
        <?php echo $inline(['shape' => 'circle', 'motif' => 'sonne']); ?> die<br class="max-sm:hidden">
        haften <?php echo $inline(['shape' => 'square', 'motif' => 'wellen']); ?><br class="sm:hidden">
        bleiben.
    </h1>
    <p class="text-lead mx-auto mt-7 max-w-md text-muted lg:mt-9">
        Wählen Sie Ihr Motiv und sehen Sie es sofort auf jedem Produkt – vom Sticker bis zum Roll-up.
    </p>
    <div class="mt-9 flex flex-wrap items-center justify-center gap-2 lg:mt-11">
        <?php echo stiqon_motif_button('Motiv wählen'); ?>
        <?php echo stiqon_button('Sortiment ansehen', ['variant' => 'secondary', 'href' => $assortment]); ?>
    </div>
    <p class="text-label mt-5 text-muted" data-needs-script>Ihr Bild bleibt in Ihrem Browser – es wird nichts hochgeladen.</p>
</section>

<?php // Showcase: what the shop holds, on a grey panel that the cards run out of ?>
<section class="wrap pb-16 lg:pb-24" aria-labelledby="schaufenster">
    <h2 id="schaufenster" class="sr-only">Das Sortiment im Überblick</h2>
    <div class="overflow-hidden rounded-tile bg-mist px-3 pt-3 sm:px-6 sm:pt-6 lg:px-10 lg:pt-10">
        <div class="-mb-10 rounded-t-card bg-paper px-5 pt-5 sm:px-7 sm:pt-7 lg:-mb-16">
            <div class="flex flex-wrap items-center gap-1">
                <a href="<?php echo esc_url($assortment); ?>" class="text-action inline-flex h-11 items-center rounded-full px-3 transition-opacity hover:opacity-60">Sortiment</a>
                <a href="<?php echo esc_url(stiqon_page_url('visitenkarten')); ?>" class="text-action inline-flex h-11 items-center rounded-full px-3 text-muted transition-opacity hover:opacity-60">Visitenkarten</a>
                <a href="<?php echo esc_url($flyer); ?>" class="text-action inline-flex h-11 items-center rounded-full px-3 text-muted transition-opacity hover:opacity-60 max-sm:hidden">Flyer</a>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-x-6 gap-y-7 px-3 sm:grid-cols-3">
                <?php foreach ($lists as $title => $list) : ?>
                    <div class="<?php echo esc_attr(! empty($list['full']) ? 'max-sm:order-last max-sm:col-span-2' : ''); ?>">
                        <h3 class="text-label text-muted"><?php echo esc_html($title); ?></h3>
                        <ul class="mt-2 font-semibold leading-snug">
                            <?php foreach ($list['entries'] as $item) : ?>
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
    <div class="wrap flex flex-wrap items-end justify-between gap-x-8 gap-y-6">
        <div class="max-w-3xl">
            <h2 id="versprechen" class="text-title">Erst ansehen, dann bestellen.</h2>
            <p class="text-title text-muted">
                Ein Bild genügt. Es erscheint auf jedem Produkt – auf Visitenkarte und Plakat, T-Shirt und Tasse.
            </p>
        </div>
        <?php
        // A finger swipes the row. A mouse cannot – the row hides its scrollbar – so the
        // buttons are there for it at every window width. They are shown once the script
        // runs, and at either end they go out of use without losing the keyboard focus.
        ?>
        <div class="flex shrink-0 gap-2 pointer-coarse:hidden" x-cloak>
            <?php echo stiqon_button(stiqon_icon('arrow-left'), ['variant' => 'secondary', 'class' => 'w-11 !px-0', 'attributes' => ['aria-label' => 'Vorherige Beispiele', 'x-on:click' => 'previous', 'x-bind:aria-disabled' => 'atStart']]); ?>
            <?php echo stiqon_button(stiqon_icon('arrow-right'), ['variant' => 'secondary', 'class' => 'w-11 !px-0', 'attributes' => ['aria-label' => 'Weitere Beispiele', 'x-on:click' => 'next', 'x-bind:aria-disabled' => 'atEnd']]); ?>
        </div>
    </div>
    <div
        class="bleed-row mt-10 gap-3 sm:gap-4 lg:mt-14"
        role="region" aria-label="Beispiele: Motive auf unseren Produkten" tabindex="0"
        x-ref="row" x-on:scroll.passive="update"
    >
        <?php foreach ($row as $card) : ?>
            <?php echo stiqon_stage($mockup($card), 'mist', 'w-[70vw] max-w-[22rem] shrink-0 snap-start'); ?>
        <?php endforeach; ?>
    </div>
</section>

<?php // A look at the assortment ?>
<section class="wrap pb-16 lg:pb-28" aria-labelledby="sortiment">
    <?php echo stiqon_section_heading(['id' => 'sortiment', 'title' => 'Eine Auswahl aus dem Sortiment']); ?>
    <ul class="mt-10 grid grid-cols-2 gap-3 sm:gap-4 lg:mt-14 lg:grid-cols-3">
        <?php foreach ($tiles as $tile) : ?>
            <?php
            // The link is the title alone, stretched over the whole tile. Wrapped around
            // the drawing, it would carry the lettering of the sample motif as link text.
            ?>
            <li class="group relative flex aspect-[4/5] flex-col items-center overflow-hidden rounded-tile bg-mist px-4 pt-6 text-center transition-colors hover:bg-mist-deep sm:aspect-square sm:pt-8">
                <a
                    href="<?php echo esc_url($tile['url']); ?>"
                    class="text-heading outline-none after:absolute after:inset-0 after:z-10 after:rounded-tile focus-visible:after:outline-2 focus-visible:after:-outline-offset-4 focus-visible:after:outline-ink"
                ><?php echo esc_html($tile['label']); ?></a>
                <span class="<?php echo esc_attr($tile['width']); ?> mt-auto translate-y-[14%] transition-transform duration-300 ease-soft group-hover:translate-y-[9%]" aria-hidden="true">
                    <?php echo $mockup($tile); ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<?php // Every area of the shop, in the pictures Stiqon supplied ?>
<?php get_template_part('template-parts/assortment'); ?>

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

<?php // References – only once there are any to show ?>
<?php if ($references !== []) : ?>
    <section class="wrap pt-16 lg:pt-28" aria-labelledby="referenzen">
        <?php echo stiqon_section_heading(['id' => 'referenzen', 'title' => 'So sieht es bei anderen aus']); ?>
        <ul class="mt-10 flex flex-wrap justify-center gap-x-4 gap-y-10 lg:mt-14">
            <?php foreach ($references as $reference) : ?>
                <?php echo stiqon_reference_card($reference); ?>
            <?php endforeach; ?>
        </ul>
        <div class="mt-10 text-center">
            <?php echo stiqon_button('Alle Referenzen '.stiqon_icon('arrow-right'), ['variant' => 'secondary', 'href' => (string) stiqon_references_url()]); ?>
        </div>
    </section>
<?php endif; ?>

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
        Ein Bild genügt, um zu sehen, wie es auf Visitenkarte, Plakat oder T-Shirt wirkt.
    </p>
    <div class="mt-9 flex flex-wrap items-center justify-center gap-2">
        <?php echo stiqon_motif_button('Motiv wählen'); ?>
        <?php echo stiqon_button('Sortiment ansehen '.stiqon_icon('arrow-right'), ['variant' => 'secondary', 'href' => $assortment]); ?>
    </div>
</section>
<?php
get_footer();
