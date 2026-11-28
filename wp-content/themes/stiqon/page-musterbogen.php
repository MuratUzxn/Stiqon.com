<?php
/**
 * Sample sheet ("Musterbogen"): logo versions, type, colours, controls and
 * mockups on one page, for reviewing the look. It belongs to the page with the
 * address /musterbogen/, which is kept private – only people who are logged in
 * to the administration can open it.
 */

defined('ABSPATH') || exit;

$type = [
    ['name' => 'Titelzeile', 'note' => 'bis 80 px, Stärke 650', 'class' => 'text-display', 'sample' => 'Werbemittel, die haften bleiben.'],
    ['name' => 'Abschluss', 'note' => 'bis 60 px', 'class' => 'text-cta', 'sample' => 'Zeigen Sie uns Ihr Motiv.'],
    ['name' => 'Abschnitt', 'note' => 'bis 44 px', 'class' => 'text-title', 'sample' => 'Formen, Materialien und Formate'],
    ['name' => 'Überschrift', 'note' => 'bis 21 px', 'class' => 'text-heading', 'sample' => 'Runde Sticker'],
    ['name' => 'Einleitung', 'note' => 'bis 20 px, gedämpft', 'class' => 'text-lead text-muted', 'sample' => 'Wählen Sie Ihr Motiv und sehen Sie es sofort auf Stickern und Flyern.'],
    ['name' => 'Fließtext', 'note' => '16 px', 'class' => '', 'sample' => 'Zahlen wie 1.250 Stück oder 105 × 148 mm bleiben gut lesbar.'],
    ['name' => 'Beschriftung', 'note' => '14 px, Stärke 500', 'class' => 'text-label text-muted', 'sample' => 'Ihr Bild bleibt in Ihrem Browser – es wird nichts hochgeladen.'],
];

$colours = [
    ['name' => 'Tinte', 'hex' => '#141414', 'class' => 'bg-ink', 'note' => 'Text, Hauptbuttons, Fuß'],
    ['name' => 'Tinte weich', 'hex' => '#404040', 'class' => 'bg-ink-soft', 'note' => 'Hauptbutton unter dem Mauszeiger'],
    ['name' => 'Gedämpft', 'hex' => '#6B6B6B', 'class' => 'bg-muted', 'note' => 'Zweiter Textton (5,3:1 auf Weiß)'],
    ['name' => 'Kontur', 'hex' => '#8A8A8A', 'class' => 'bg-control', 'note' => 'Ränder von Eingabefeldern (3,5:1)'],
    ['name' => 'Haarlinie', 'hex' => '#E8E8E8', 'class' => 'bg-line', 'note' => 'Trennlinien'],
    ['name' => 'Fläche', 'hex' => '#F3F3F3', 'class' => 'bg-mist', 'note' => 'Karten, Kacheln, Bänder'],
    ['name' => 'Papier', 'hex' => '#FFFFFF', 'class' => 'bg-paper ring-1 ring-line ring-inset', 'note' => 'Seitenhintergrund'],
    ['name' => 'Gold', 'hex' => '#D6B92C', 'class' => 'bg-gold', 'note' => 'Linie im Logo; sonst nur Tastaturfokus auf Dunkel'],
];

$mockups = [
    ['sticker' => ['shape' => 'circle', 'motif' => 'siegel']],
    ['sticker' => ['shape' => 'square', 'motif' => 'sonne', 'peel' => true]],
    ['sticker' => ['shape' => 'circle', 'motif' => 'monogramm', 'finish' => 'matte']],
    ['sticker' => ['shape' => 'rect', 'motif' => 'wellen']],
    ['sticker' => ['shape' => 'square', 'motif' => 'neu']],
    ['sticker' => ['shape' => 'oval', 'motif' => 'danke']],
    ['sticker' => ['shape' => 'circle', 'motif' => 'aktion']],
    ['sticker' => ['shape' => 'circle', 'material' => 'kraft', 'motif' => 'blatt', 'finish' => 'matte']],
    ['sticker' => ['shape' => 'square', 'material' => 'holo', 'motif' => 'raster']],
    ['sticker' => ['shape' => 'circle', 'material' => 'clear', 'motif' => 'monogramm']],
    ['sticker' => ['shape' => 'contour', 'motif' => 'sprechblase']],
    ['sticker' => ['shape' => 'contour', 'motif' => 'stern']],
    ['sticker' => ['shape' => 'circle', 'material' => 'holo', 'motif' => 'siegel']],
    ['sticker' => ['shape' => 'rect', 'motif' => 'punkt', 'peel' => true]],
    ['sticker' => ['shape' => 'circle', 'material' => 'kraft', 'motif' => 'siegel', 'finish' => 'matte']],
    ['flyer' => ['format' => 'a6', 'motif' => 'fest']],
    ['flyer' => ['format' => 'a5', 'motif' => 'eroeffnung', 'stack' => true]],
    ['flyer' => ['format' => 'a4', 'motif' => 'karte', 'heavy' => true]],
    ['flyer' => ['format' => 'lang', 'motif' => 'prozent', 'gloss' => true]],
    ['flyer' => ['format' => 'quadrat', 'motif' => 'kursplan', 'stack' => true]],
];

$brand = static fn (string $file): string => esc_url(get_theme_file_uri('assets/brand/'.$file));

stiqon_declare_lean_page();
get_header();
?>
<section class="wrap pt-8 pb-12 text-center lg:pt-14 lg:pb-16">
    <p class="text-label text-muted">Intern – nicht Teil des Shops</p>
    <h1 class="text-display mt-4">Musterbogen</h1>
    <p class="text-lead mx-auto mt-6 max-w-xl text-muted">
        Logo, Schrift, Farben, Bedienelemente und Mockups auf einer Seite – zur Abstimmung des Looks.
    </p>
</section>

<div class="wrap pb-16 lg:pb-28">
    <p class="mx-auto max-w-2xl rounded-card bg-mist px-6 py-5 text-center text-muted">
        Der Look folgt mobbin.com: weiße Seiten, eine einzige serifenlose Schrift, hellgraue Flächen mit großen Rundungen, schwarze und graue Pill-Buttons, schwarzer Fuß – ohne Rahmen und ohne Schatten an der Oberfläche.
    </p>

    <?php // Logo ?>
    <section class="mt-16 lg:mt-28" aria-labelledby="logo">
        <?php echo stiqon_section_heading([
            'id' => 'logo',
            'title' => 'Logo',
            'lead' => 'Das gelieferte Original und die daraus abgeleiteten Fassungen. Die Buchstabenformen sind nicht neu gezeichnet.',
        ]); ?>
        <div class="mt-10 grid gap-3 sm:gap-4 md:grid-cols-2 lg:mt-14">
            <figure>
                <div class="flex aspect-[2/1] items-center justify-center rounded-tile bg-mist p-8">
                    <img src="<?php echo $brand('logo-original-800.webp'); ?>" width="800" height="400" alt="Stiqon – geliefertes Original" class="h-auto w-full max-w-sm rounded-control">
                </div>
                <figcaption class="text-label mt-3 text-muted">Original, wie geliefert – mit weißem Hintergrund</figcaption>
            </figure>
            <figure>
                <div class="flex aspect-[2/1] items-center justify-center rounded-tile bg-mist p-8">
                    <?php echo stiqon_logo(['class' => 'h-auto w-full max-w-sm', 'sizes' => '(min-width: 48rem) 384px, 80vw']); ?>
                </div>
                <figcaption class="text-label mt-3 text-muted">Freigestellt, für helle Flächen</figcaption>
            </figure>
            <figure>
                <div class="flex aspect-[2/1] items-center justify-center rounded-tile bg-night p-8">
                    <?php echo stiqon_logo(['variant' => 'light', 'class' => 'h-auto w-full max-w-sm', 'sizes' => '(min-width: 48rem) 384px, 80vw']); ?>
                </div>
                <figcaption class="text-label mt-3 text-muted">Helle Fassung für dunkle Flächen – die Goldlinie bleibt</figcaption>
            </figure>
            <figure>
                <div class="flex aspect-[2/1] items-center justify-center gap-6 rounded-tile bg-mist p-8">
                    <img src="<?php echo $brand('icon-192.png'); ?>" width="192" height="192" alt="Symbol in 128 px" class="size-32">
                    <img src="<?php echo $brand('icon-180.png'); ?>" width="180" height="180" alt="Symbol in 64 px" class="size-16">
                    <img src="<?php echo $brand('icon-32.png'); ?>" width="32" height="32" alt="Symbol in 32 px" class="size-8">
                </div>
                <figcaption class="text-label mt-3 text-muted">Symbol aus dem „S“ für Browser-Tab und Startbildschirm</figcaption>
            </figure>
        </div>
        <p class="mx-auto mt-8 max-w-2xl text-center text-muted">
            Ausweichlösung, falls die abgeleiteten Fassungen nicht gefallen: das unveränderte Logo auf einer weißen Fläche. Eine Vektordatei des Logos würde alle Fassungen schärfer machen.
        </p>
    </section>

    <?php // Type ?>
    <section class="mt-16 lg:mt-28" aria-labelledby="schrift">
        <?php echo stiqon_section_heading([
            'id' => 'schrift',
            'title' => 'Schrift',
            'lead' => 'Eine Schrift für alles: Instrument Sans, frei lizenziert und vom eigenen Server geladen. Größen und Stärken sind am Vorbild gemessen.',
        ]); ?>
        <dl class="mt-10 grid gap-3 sm:gap-4 lg:mt-14">
            <?php foreach ($type as $style) : ?>
                <div class="grid items-baseline gap-x-8 gap-y-3 rounded-card bg-mist p-6 md:grid-cols-[11rem_1fr] lg:p-8">
                    <dt>
                        <span class="block font-semibold"><?php echo esc_html($style['name']); ?></span>
                        <span class="text-label block text-muted"><?php echo esc_html($style['note']); ?></span>
                    </dt>
                    <dd class="<?php echo esc_attr($style['class']); ?>"><?php echo esc_html($style['sample']); ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </section>

    <?php // Colour ?>
    <section class="mt-16 lg:mt-28" aria-labelledby="farben">
        <?php echo stiqon_section_heading([
            'id' => 'farben',
            'title' => 'Farben',
            'lead' => 'Fast-Schwarz, Weiß und ein neutrales Grau tragen die Seite. Farbe bringen die Motive mit.',
        ]); ?>
        <ul class="mt-10 grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-4 md:grid-cols-4 lg:mt-14">
            <?php foreach ($colours as $colour) : ?>
                <li>
                    <div class="aspect-[3/2] rounded-card <?php echo esc_attr($colour['class']); ?>"></div>
                    <p class="mt-3 font-semibold"><?php echo esc_html($colour['name']); ?> <span class="font-normal text-muted"><?php echo esc_html($colour['hex']); ?></span></p>
                    <p class="text-label text-muted"><?php echo esc_html($colour['note']); ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <?php // Controls ?>
    <section class="mt-16 lg:mt-28" aria-labelledby="bedienelemente">
        <?php echo stiqon_section_heading(['id' => 'bedienelemente', 'title' => 'Bedienelemente']); ?>
        <div class="mt-10 grid gap-3 sm:gap-4 md:grid-cols-2 lg:mt-14">
            <div class="flex flex-wrap items-center justify-center gap-2 rounded-tile bg-mist p-8">
                <?php echo stiqon_button('Hauptaktion'); ?>
                <?php echo stiqon_button('Zweite Aktion', ['variant' => 'secondary']); ?>
                <?php echo stiqon_button('Leise Aktion', ['variant' => 'quiet']); ?>
            </div>
            <div class="on-night flex flex-wrap items-center justify-center gap-2 rounded-tile bg-night p-8">
                <?php echo stiqon_button('Hauptaktion', ['variant' => 'on-night']); ?>
                <?php echo stiqon_button('Zweite Aktion', ['variant' => 'on-night-secondary']); ?>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-2 rounded-tile bg-mist p-8 md:col-span-2">
                <?php echo stiqon_chip('Alle', ['active' => true]); ?>
                <?php foreach (['Rund', 'Quadratisch', 'Konturschnitt', 'Transparent', 'Holografisch'] as $label) : ?>
                    <?php echo stiqon_chip($label); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php // Mockups ?>
    <section class="mt-16 lg:mt-28" aria-labelledby="mockups">
        <?php echo stiqon_section_heading([
            'id' => 'mockups',
            'title' => 'Mockups',
            'lead' => 'Alle Produktbilder sind gezeichnet, nicht fotografiert – dieselbe Darstellung zeigt später das Motiv der Kundschaft. Die Motive hier sind eigene Beispiele.',
        ]); ?>
        <div class="mt-8 flex justify-center">
            <?php echo stiqon_motif_button('Eigenes Motiv auf allen Karten zeigen', ['variant' => 'secondary']); ?>
        </div>
        <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:mt-12 lg:grid-cols-4">
            <?php foreach ($mockups as $card) : ?>
                <?php echo stiqon_stage(isset($card['sticker']) ? stiqon_sticker($card['sticker']) : stiqon_flyer($card['flyer'])); ?>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php
get_footer();
