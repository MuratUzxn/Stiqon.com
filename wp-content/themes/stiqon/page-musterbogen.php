<?php
/**
 * Sample sheet ("Musterbogen"): colours, type, logo versions and mockups on one
 * page, for reviewing the look. It belongs to the page with the address
 * /musterbogen/, which is kept private – only people who are logged in to the
 * administration can open it.
 */

defined('ABSPATH') || exit;

$colours = [
    ['name' => 'Tinte', 'hex' => '#0A0A0A', 'class' => 'bg-ink', 'note' => 'Text, Hauptbuttons'],
    ['name' => 'Tinte weich', 'hex' => '#3D3D3A', 'class' => 'bg-ink-soft', 'note' => 'Fließtext zweiter Ordnung'],
    ['name' => 'Gedämpft', 'hex' => '#6F6F6A', 'class' => 'bg-muted', 'note' => 'Kleinster Textton (5,0:1)'],
    ['name' => 'Kontur', 'hex' => '#8A8A85', 'class' => 'bg-control', 'note' => 'Ränder von Chips und Feldern (3,5:1)'],
    ['name' => 'Haarlinie', 'hex' => '#E4E4DF', 'class' => 'bg-line', 'note' => 'Trennlinien ruhiger Flächen'],
    ['name' => 'Kartenfläche', 'hex' => '#F5F5F3', 'class' => 'bg-mist', 'note' => 'Bühne hinter den Mockups'],
    ['name' => 'Papier', 'hex' => '#FFFFFF', 'class' => 'border border-line bg-paper', 'note' => 'Seitenhintergrund'],
    ['name' => 'Gold', 'hex' => '#D6B92C', 'class' => 'bg-gold', 'note' => 'Nur auf dunklen Flächen'],
];

$accents = [
    ['key' => 'A', 'name' => 'Instrument Serif', 'note' => 'leicht und elegant – aktuell eingestellt', 'style' => "font-family:'Instrument Serif',serif;font-style:italic;font-weight:400;letter-spacing:-0.02em;font-size:1.06em"],
    ['key' => 'B', 'name' => 'Playfair Display', 'note' => 'kräftiger, näher am Gewicht des Logos', 'style' => "font-family:'Playfair Display Variable',serif;font-style:italic;font-weight:700;letter-spacing:-0.03em"],
    ['key' => 'C', 'name' => 'Ohne Serife', 'note' => 'durchgehend Instrument Sans', 'style' => ''],
];

$mockups = [
    ['tone' => 'mist', 'sticker' => ['shape' => 'circle', 'motif' => 'siegel']],
    ['tone' => 'sand', 'sticker' => ['shape' => 'square', 'motif' => 'sonne', 'peel' => true]],
    ['tone' => 'sage', 'sticker' => ['shape' => 'circle', 'motif' => 'monogramm', 'finish' => 'matte']],
    ['tone' => 'sky', 'sticker' => ['shape' => 'rect', 'motif' => 'wellen']],
    ['tone' => 'mist', 'sticker' => ['shape' => 'square', 'motif' => 'neu']],
    ['tone' => 'blush', 'sticker' => ['shape' => 'oval', 'motif' => 'danke']],
    ['tone' => 'mist', 'sticker' => ['shape' => 'circle', 'motif' => 'aktion']],
    ['tone' => 'sand', 'sticker' => ['shape' => 'circle', 'material' => 'kraft', 'motif' => 'blatt', 'finish' => 'matte']],
    ['tone' => 'mist', 'sticker' => ['shape' => 'square', 'material' => 'holo', 'motif' => 'raster']],
    ['tone' => 'sky', 'sticker' => ['shape' => 'circle', 'material' => 'clear', 'motif' => 'monogramm']],
    ['tone' => 'mist', 'sticker' => ['shape' => 'contour', 'motif' => 'sprechblase']],
    ['tone' => 'sage', 'sticker' => ['shape' => 'contour', 'motif' => 'stern']],
    ['tone' => 'mist', 'sticker' => ['shape' => 'circle', 'material' => 'holo', 'motif' => 'siegel']],
    ['tone' => 'blush', 'sticker' => ['shape' => 'rect', 'motif' => 'punkt', 'peel' => true]],
    ['tone' => 'mist', 'sticker' => ['shape' => 'circle', 'material' => 'kraft', 'motif' => 'siegel', 'finish' => 'matte']],
    ['tone' => 'mist', 'flyer' => ['format' => 'a6', 'motif' => 'fest']],
    ['tone' => 'sand', 'flyer' => ['format' => 'a5', 'motif' => 'eroeffnung', 'stack' => true]],
    ['tone' => 'sage', 'flyer' => ['format' => 'a4', 'motif' => 'karte', 'heavy' => true]],
    ['tone' => 'mist', 'flyer' => ['format' => 'lang', 'motif' => 'prozent', 'gloss' => true]],
    ['tone' => 'sky', 'flyer' => ['format' => 'quadrat', 'motif' => 'kursplan', 'stack' => true]],
];

$brand = static fn (string $file): string => esc_url(get_theme_file_uri('assets/brand/'.$file));

get_header();
?>
<div class="wrap py-12 lg:py-16">
    <p class="text-overline text-muted">Intern – nicht Teil des Shops</p>
    <h1 class="text-display mt-5">Musterbogen</h1>
    <p class="text-lead mt-6 max-w-2xl text-ink-soft">
        Farben, Schrift, Logo-Fassungen und Mockups auf einer Seite – zur Abstimmung des Looks.
    </p>

    <?php // Logo ?>
    <section class="mt-16" aria-labelledby="logo">
        <?php echo stiqon_section_heading([
            'id' => 'logo',
            'overline' => '1',
            'title' => 'Logo',
            'lead' => 'Links das gelieferte Original. Daneben die daraus abgeleiteten Fassungen: freigestellt für helle Flächen und mit hellen Buchstaben für dunkle Flächen. Die Buchstabenformen sind nicht neu gezeichnet.',
        ]); ?>
        <div class="mt-8 grid gap-4 md:grid-cols-2">
            <figure>
                <div class="flex aspect-[2/1] items-center justify-center rounded-card border border-line bg-white p-8">
                    <img src="<?php echo $brand('logo-original-800.webp'); ?>" width="800" height="400" alt="Stiqon – geliefertes Original" class="h-auto w-full max-w-md">
                </div>
                <figcaption class="mt-3 text-sm text-muted">Original (wie geliefert, mit weißem Hintergrund)</figcaption>
            </figure>
            <figure>
                <div class="flex aspect-[2/1] items-center justify-center rounded-card bg-mist p-8">
                    <?php echo stiqon_logo(['class' => 'h-auto w-full max-w-md', 'sizes' => '(min-width: 48rem) 448px, 80vw']); ?>
                </div>
                <figcaption class="mt-3 text-sm text-muted">Freigestellt, hier auf der Kartenfläche</figcaption>
            </figure>
            <figure>
                <div class="flex aspect-[2/1] items-center justify-center rounded-card bg-night p-8">
                    <?php echo stiqon_logo(['variant' => 'light', 'class' => 'h-auto w-full max-w-md', 'sizes' => '(min-width: 48rem) 448px, 80vw']); ?>
                </div>
                <figcaption class="mt-3 text-sm text-muted">Helle Fassung für dunkle Flächen (Buchstaben hell, Goldlinie bleibt)</figcaption>
            </figure>
            <figure>
                <div class="flex aspect-[2/1] items-center justify-center gap-6 rounded-card bg-mist p-8">
                    <img src="<?php echo $brand('icon-192.png'); ?>" width="192" height="192" alt="Symbol 192 px" class="size-32">
                    <img src="<?php echo $brand('icon-180.png'); ?>" width="180" height="180" alt="Symbol 64 px" class="size-16">
                    <img src="<?php echo $brand('icon-32.png'); ?>" width="32" height="32" alt="Symbol 32 px" class="size-8">
                </div>
                <figcaption class="mt-3 text-sm text-muted">Symbol aus dem „S“ für Browser-Tab und Startbildschirm</figcaption>
            </figure>
        </div>
        <p class="mt-6 max-w-2xl text-ink-soft">
            Ausweichlösung, falls die abgeleiteten Fassungen nicht gefallen: das unveränderte Logo auf einer weißen Fläche. Eine Vektordatei des Logos würde alle Fassungen schärfer machen.
        </p>
    </section>

    <?php // Type ?>
    <section class="mt-20" aria-labelledby="schrift">
        <?php echo stiqon_section_heading([
            'id' => 'schrift',
            'overline' => '2',
            'title' => 'Schrift',
            'lead' => 'Die Oberfläche ist in Instrument Sans gesetzt. Zur Wahl steht, ob ein einzelnes Wort in großen Überschriften kursiv in einer Serife erscheint – als Anklang an das Logo.',
        ]); ?>
        <div class="mt-8 grid gap-4">
            <?php foreach ($accents as $accent) : ?>
                <div class="rounded-card border border-line p-7 lg:p-10">
                    <p class="text-overline text-muted">Variante <?php echo esc_html($accent['key'].' · '.$accent['name'].' – '.$accent['note']); ?></p>
                    <p class="text-display mt-5">Werbemittel, die <span<?php echo $accent['style'] !== '' ? ' style="'.esc_attr($accent['style']).'"' : ''; ?>>haften bleiben.</span></p>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-card border border-line p-7">
                <p class="text-overline text-muted">Überschrift</p>
                <p class="text-title mt-3">Formen, Materialien und Formate</p>
                <p class="text-lead mt-4 text-ink-soft">Einleitender Absatz in etwas größerer Schrift, der einen Abschnitt eröffnet.</p>
            </div>
            <div class="rounded-card border border-line p-7">
                <p class="text-overline text-muted">Fließtext</p>
                <p class="text-heading mt-3">Runde Sticker</p>
                <p class="mt-2 text-ink-soft">Fließtext in normaler Größe. Zahlen wie 1.250 Stück oder 105 × 148 mm bleiben gut lesbar.</p>
                <p class="mt-2 text-sm text-muted">Kleiner Hinweistext im gedämpften Ton.</p>
            </div>
        </div>
    </section>

    <?php // Colour ?>
    <section class="mt-20" aria-labelledby="farben">
        <?php echo stiqon_section_heading([
            'id' => 'farben',
            'overline' => '3',
            'title' => 'Farben',
            'lead' => 'Aus dem Logo gemessen: Schwarz und Weiß tragen die Seite. Gold erscheint sparsam und nur auf dunklen Flächen, weil es auf Weiß zu wenig Kontrast hat.',
        ]); ?>
        <ul class="mt-8 grid grid-cols-2 gap-4 md:grid-cols-4">
            <?php foreach ($colours as $colour) : ?>
                <li>
                    <div class="aspect-[3/2] rounded-card <?php echo esc_attr($colour['class']); ?>"></div>
                    <p class="mt-3 font-medium"><?php echo esc_html($colour['name']); ?> <span class="font-normal text-muted"><?php echo esc_html($colour['hex']); ?></span></p>
                    <p class="text-sm text-muted"><?php echo esc_html($colour['note']); ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <?php // Controls ?>
    <section class="mt-20" aria-labelledby="bedienelemente">
        <?php echo stiqon_section_heading(['id' => 'bedienelemente', 'overline' => '4', 'title' => 'Bedienelemente']); ?>
        <div class="mt-8 grid gap-4 md:grid-cols-2">
            <div class="flex flex-wrap items-center gap-3 rounded-card border border-line p-7">
                <?php echo stiqon_button('Hauptaktion'); ?>
                <?php echo stiqon_button('Zweite Aktion', ['variant' => 'secondary']); ?>
                <?php echo stiqon_button('Leise Aktion', ['variant' => 'quiet']); ?>
            </div>
            <div class="on-night flex flex-wrap items-center gap-3 rounded-card bg-night p-7">
                <?php echo stiqon_button('Hauptaktion', ['variant' => 'on-night']); ?>
                <?php echo stiqon_button('Zweite Aktion', ['variant' => 'on-night-outline']); ?>
            </div>
            <div class="flex flex-wrap items-center gap-2 rounded-card border border-line p-7 md:col-span-2">
                <?php echo stiqon_chip('Alle', ['active' => true]); ?>
                <?php foreach (['Rund', 'Quadratisch', 'Konturschnitt', 'Transparent', 'Holografisch'] as $label) : ?>
                    <?php echo stiqon_chip($label); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php // Mockups ?>
    <section class="mt-20" aria-labelledby="mockups">
        <?php echo stiqon_section_heading([
            'id' => 'mockups',
            'overline' => '5',
            'title' => 'Mockups',
            'lead' => 'Alle Produktbilder sind gezeichnet, nicht fotografiert – dieselbe Darstellung zeigt später das Motiv der Kundschaft. Die Motive hier sind eigene Beispiele.',
        ]); ?>
        <div class="mt-8">
            <?php echo stiqon_motif_button('Eigenes Motiv auf allen Karten zeigen', ['variant' => 'secondary']); ?>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
            <?php foreach ($mockups as $card) : ?>
                <?php
                $mockup = isset($card['sticker']) ? stiqon_sticker($card['sticker']) : stiqon_flyer($card['flyer']);

                echo stiqon_stage($mockup, $card['tone']);
                ?>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php
get_footer();
