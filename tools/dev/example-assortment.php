<?php
/**
 * The example assortment as data – read by tools/dev/example-catalogue.php,
 * which puts it into the shop.
 *
 * What is offered follows the online printer printworld.com (its calculators,
 * November 2026): the products a small business orders, with the options that
 * matter for them – format, paper, finish, number of pages, colour, quantity.
 * Fewer choices than there, so that every combination can be one variation.
 * Prices are invented, but shaped like real ones: more pieces cost less each,
 * larger formats and finer materials cost more. Gross prices, ending in ,90.
 *
 * areas       product category per area of the shop (slug => name); the theme
 *             has a page for each (stiqon_area_definitions())
 * attributes  WooCommerce attributes (slug => [label, [term slug => name]]),
 *             terms in the order they are offered
 * products    in the order the shop lists them:
 *               area, slug, name, summary, description,
 *               drawing  stored on the product for its drawing (type and what the name alone does not say)
 *               offers   attribute slug => term slugs, in the order the options are shown;
 *                        every combination becomes one variation
 *               price    base   => [auflage term => amount],
 *                        factor => one factor for the whole product (optional),
 *                        by     => [attribute slug => [term => factor]] (optional)
 *
 * Only the names of options say how a product is drawn – as a supplier's
 * products will arrive. A product's own drawing values go into "drawing".
 */

return [
    'areas' => [
        'sticker' => 'Sticker',
        'flyer' => 'Flyer',
        'visitenkarten' => 'Visitenkarten',
        'karten' => 'Karten',
        'briefpapier' => 'Briefpapier',
        'broschueren' => 'Broschüren',
        'plakate' => 'Plakate',
        'kalender' => 'Kalender',
        'werbetechnik' => 'Werbetechnik',
        'verpackungen' => 'Verpackungen',
        'textilien' => 'Textilien',
        'werbeartikel' => 'Werbeartikel',
    ],

    'attributes' => [
        'material' => ['Material', ['vinyl' => 'Weißes Vinyl', 'transparent' => 'Transparente Folie', 'holografisch' => 'Holografische Folie', 'kraftpapier' => 'Kraftpapier']],
        'groesse' => ['Größe', ['5-cm' => '5 cm', '7-5-cm' => '7,5 cm', '7x4-cm' => '7 × 4 cm', '10x6-cm' => '10 × 6 cm']],
        'papier' => ['Papier', [
            '135-g' => '135 g Bilderdruck',
            '250-g' => '250 g Bilderdruck',
            '300-g-matt' => '300 g Bilderdruck matt',
            '300-g-glaenzend' => '300 g Bilderdruck glänzend',
            '90-g-offset' => '90 g Offset',
            '120-g-offset' => '120 g Offset',
            '135-g-matt' => '135 g Bilderdruck matt',
            '135-g-glaenzend' => '135 g Bilderdruck glänzend',
        ]],
        'druck' => ['Druck', ['einseitig' => 'Einseitig bedruckt', 'beidseitig' => 'Beidseitig bedruckt']],
        'veredelung' => ['Veredelung', [
            'ohne' => 'ohne Veredelung',
            'folienkaschierung-matt' => 'Folienkaschierung matt',
            'soft-touch' => 'Soft-Touch-Folie',
            'cellophanierung-glaenzend' => 'Cellophanierung glänzend',
            'goldpraegung' => 'Heißfolienprägung Gold',
            'silberpraegung' => 'Heißfolienprägung Silber',
        ]],
        'format' => ['Format', [
            'din-a6' => 'DIN A6',
            'din-a5' => 'DIN A5',
            'din-a4' => 'DIN A4',
            'din-a3' => 'DIN A3',
            'din-a2' => 'DIN A2',
            'din-a1' => 'DIN A1',
            'din-a0' => 'DIN A0',
            'din-lang' => 'DIN lang',
            '85x200' => '85 × 200 cm',
            '100x200' => '100 × 200 cm',
            '120x200' => '120 × 200 cm',
            '200x100' => '200 × 100 cm',
            '300x100' => '300 × 100 cm',
            '400x100' => '400 × 100 cm',
            's' => 'S (25 × 18 × 10 cm)',
            'm' => 'M (35 × 25 × 15 cm)',
            'l' => 'L (45 × 35 × 20 cm)',
            'klein' => 'Klein (24 × 10 × 32 cm)',
            'gross' => 'Groß (32 × 12 × 41 cm)',
            '500-ml' => '500 ml',
            '750-ml' => '750 ml',
            'rund-107' => 'Rund 107 mm',
            'eckig-93' => 'Eckig 93 × 93 mm',
        ]],
        'umfang' => ['Umfang', ['8-seiten' => '8 Seiten', '12-seiten' => '12 Seiten', '16-seiten' => '16 Seiten', '24-seiten' => '24 Seiten', '48-seiten' => '48 Seiten', '64-seiten' => '64 Seiten', '96-seiten' => '96 Seiten']],
        'blatt' => ['Blattzahl', ['25-blatt' => '25 Blatt', '50-blatt' => '50 Blatt']],
        'fenster' => ['Fenster', ['mit-fenster' => 'mit Fenster', 'ohne-fenster' => 'ohne Fenster']],
        'modell' => ['Modell', ['basic' => 'Basic', 'premium' => 'Premium']],
        'farbe' => ['Farbe', ['weiss' => 'Weiß', 'schwarz' => 'Schwarz', 'navy' => 'Navy', 'grau-meliert' => 'Grau meliert', 'natur' => 'Natur', 'braun' => 'Braun', 'silber' => 'Silber']],
        'textilgroesse' => ['Größe', ['s' => 'S', 'm' => 'M', 'l' => 'L', 'xl' => 'XL', 'xxl' => 'XXL']],
        'auflage' => ['Auflage', [
            '1' => '1 Stück',
            '2' => '2 Stück',
            '5' => '5 Stück',
            '10' => '10 Stück',
            '12' => '12 Stück',
            '25' => '25 Stück',
            '36' => '36 Stück',
            '50' => '50 Stück',
            '72' => '72 Stück',
            '100' => '100 Stück',
            '144' => '144 Stück',
            '250' => '250 Stück',
            '500' => '500 Stück',
            '1000' => '1.000 Stück',
            '2500' => '2.500 Stück',
            '5000' => '5.000 Stück',
        ]],
    ],

    'products' => (static function (): array {
        $sticker = 'Auf weißem Vinyl, transparenter oder holografischer Folie oder auf Kraftpapier. Geliefert als einzelne Sticker.';
        $flyer = 'Farbig bedruckt auf Bilderdruckpapier, wahlweise in 135 g oder in 250 g mit festerem Griff.';
        $stickerOffers = static fn (array $sizes): array => ['material' => ['vinyl', 'transparent', 'holografisch', 'kraftpapier'], 'groesse' => $sizes, 'auflage' => ['100', '250', '500']];
        $stickerPrice = static fn (array $sizes, float $shape): array => [
            'base' => ['100' => 24.0, '250' => 39.0, '500' => 59.0],
            'by' => ['material' => ['vinyl' => 1.0, 'transparent' => 1.15, 'holografisch' => 1.45, 'kraftpapier' => 1.1], 'groesse' => [$sizes[0] => 1.0, $sizes[1] => 1.4]],
            'factor' => $shape,
        ];
        $flyerOffers = ['papier' => ['135-g', '250-g'], 'auflage' => ['100', '250', '500', '1000']];
        $flyerPrice = static fn (float $format): array => [
            'base' => ['100' => 19.0, '250' => 29.0, '500' => 39.0, '1000' => 54.0],
            'factor' => $format,
            'by' => ['papier' => ['135-g' => 1.0, '250-g' => 1.3]],
        ];
        $cardPapers = ['papier' => ['300-g-matt', '300-g-glaenzend']];
        $same = static fn (array $terms): array => array_fill_keys($terms, 1.0);

        return [
            // --- Sticker ---------------------------------------------------------------
            ['area' => 'sticker', 'slug' => 'runde-sticker', 'name' => 'Runde Sticker', 'summary' => 'Der Klassiker für Logo, Siegel und Verpackung.', 'description' => $sticker,
                'drawing' => ['type' => 'sticker', 'shape' => 'circle', 'motif' => 'siegel'], 'offers' => $stickerOffers(['5-cm', '7-5-cm']), 'price' => $stickerPrice(['5-cm', '7-5-cm'], 1.0)],
            ['area' => 'sticker', 'slug' => 'quadratische-sticker', 'name' => 'Quadratische Sticker', 'summary' => 'Mit weichen Ecken – für Motive, die Fläche brauchen.', 'description' => $sticker,
                'drawing' => ['type' => 'sticker', 'shape' => 'square', 'motif' => 'sonne'], 'offers' => $stickerOffers(['5-cm', '7-5-cm']), 'price' => $stickerPrice(['5-cm', '7-5-cm'], 1.0)],
            ['area' => 'sticker', 'slug' => 'rechteckige-sticker', 'name' => 'Rechteckige Sticker', 'summary' => 'Für Schriftzüge, Etiketten und Hinweise.', 'description' => $sticker,
                'drawing' => ['type' => 'sticker', 'shape' => 'rect', 'motif' => 'wellen'], 'offers' => $stickerOffers(['7x4-cm', '10x6-cm']), 'price' => $stickerPrice(['7x4-cm', '10x6-cm'], 1.0)],
            ['area' => 'sticker', 'slug' => 'ovale-sticker', 'name' => 'Ovale Sticker', 'summary' => 'Die freundliche Form für Dankeschön und Gruß.', 'description' => $sticker,
                'drawing' => ['type' => 'sticker', 'shape' => 'oval', 'motif' => 'danke'], 'offers' => $stickerOffers(['7x4-cm', '10x6-cm']), 'price' => $stickerPrice(['7x4-cm', '10x6-cm'], 1.0)],
            ['area' => 'sticker', 'slug' => 'sticker-mit-konturschnitt', 'name' => 'Sticker mit Konturschnitt', 'summary' => 'Geschnitten entlang Ihres Motivs, mit schmalem weißem Rand.', 'description' => $sticker,
                'drawing' => ['type' => 'sticker', 'shape' => 'contour', 'motif' => 'sprechblase'], 'offers' => $stickerOffers(['5-cm', '7-5-cm']), 'price' => $stickerPrice(['5-cm', '7-5-cm'], 1.2)],

            // --- Flyer und Faltblätter --------------------------------------------------
            ['area' => 'flyer', 'slug' => 'flyer-din-a6', 'name' => 'Flyer DIN A6', 'summary' => 'Postkartengröße – passt in jede Tasche.', 'description' => $flyer,
                'drawing' => ['type' => 'flyer', 'format' => 'a6', 'motif' => 'fest'], 'offers' => $flyerOffers, 'price' => $flyerPrice(1.0)],
            ['area' => 'flyer', 'slug' => 'flyer-din-a5', 'name' => 'Flyer DIN A5', 'summary' => 'Genug Platz für Angebot, Termin und Anfahrt.', 'description' => $flyer,
                'drawing' => ['type' => 'flyer', 'format' => 'a5', 'motif' => 'eroeffnung'], 'offers' => $flyerOffers, 'price' => $flyerPrice(1.35)],
            ['area' => 'flyer', 'slug' => 'flyer-din-a4', 'name' => 'Flyer DIN A4', 'summary' => 'Das große Blatt für Aushang und Speisekarte.', 'description' => $flyer,
                'drawing' => ['type' => 'flyer', 'format' => 'a4', 'motif' => 'karte'], 'offers' => $flyerOffers, 'price' => $flyerPrice(1.9)],
            ['area' => 'flyer', 'slug' => 'flyer-din-lang', 'name' => 'Flyer DIN lang', 'summary' => 'Schmal und hoch – für Aufsteller und Briefumschlag.', 'description' => $flyer,
                'drawing' => ['type' => 'flyer', 'format' => 'lang', 'motif' => 'prozent'], 'offers' => $flyerOffers, 'price' => $flyerPrice(1.2)],
            ['area' => 'flyer', 'slug' => 'flyer-quadrat', 'name' => 'Flyer Quadrat', 'summary' => 'Fällt auf, weil er anders ist.', 'description' => $flyer,
                'drawing' => ['type' => 'flyer', 'format' => 'quadrat', 'motif' => 'kursplan'], 'offers' => $flyerOffers, 'price' => $flyerPrice(1.3)],
            ['area' => 'flyer', 'slug' => 'faltblatt-wickelfalz', 'name' => 'Faltblatt mit Wickelfalz', 'summary' => 'DIN lang mit sechs Seiten – für Preisliste, Programm und Speisekarte.', 'description' => 'Zweimal nach innen gefalzt, auf 135 g oder auf festem 250 g Bilderdruck.',
                'drawing' => ['type' => 'leaflet', 'fold' => 'wickel', 'motif' => 'karte'], 'offers' => $flyerOffers, 'price' => ['base' => ['100' => 34.0, '250' => 49.0, '500' => 64.0, '1000' => 89.0], 'by' => ['papier' => ['135-g' => 1.0, '250-g' => 1.3]]]],
            ['area' => 'flyer', 'slug' => 'faltblatt-zickzackfalz', 'name' => 'Faltblatt mit Zickzackfalz', 'summary' => 'Sechs Seiten, die sich öffnen wie eine Ziehharmonika.', 'description' => 'DIN lang, im Zickzack gefalzt, auf 135 g oder auf festem 250 g Bilderdruck.',
                'drawing' => ['type' => 'leaflet', 'fold' => 'zickzack', 'motif' => 'kursplan'], 'offers' => $flyerOffers, 'price' => ['base' => ['100' => 34.0, '250' => 49.0, '500' => 64.0, '1000' => 89.0], 'factor' => 1.05, 'by' => ['papier' => ['135-g' => 1.0, '250-g' => 1.3]]]],

            // --- Visitenkarten ----------------------------------------------------------
            ['area' => 'visitenkarten', 'slug' => 'visitenkarten', 'name' => 'Visitenkarten', 'summary' => '85 × 55 mm auf 300 g Bilderdruck – matt oder glänzend.', 'description' => 'Das Standardformat, ein- oder beidseitig farbig bedruckt.',
                'drawing' => ['type' => 'card', 'format' => 'visitenkarte', 'motif' => 'cafe'], 'offers' => $cardPapers + ['druck' => ['einseitig', 'beidseitig'], 'auflage' => ['100', '250', '500', '1000']],
                'price' => ['base' => ['100' => 14.0, '250' => 19.0, '500' => 24.0, '1000' => 34.0], 'by' => ['druck' => ['einseitig' => 1.0, 'beidseitig' => 1.25]]]],
            ['area' => 'visitenkarten', 'slug' => 'visitenkarten-folienkaschierung', 'name' => 'Visitenkarten mit Folienkaschierung', 'summary' => 'Beidseitig kaschiert – matt oder mit samtiger Soft-Touch-Folie.', 'description' => '400 g Bilderdruck, beidseitig bedruckt und mit Folie geschützt.',
                'drawing' => ['type' => 'card', 'format' => 'visitenkarte', 'motif' => 'praxis'], 'offers' => ['veredelung' => ['folienkaschierung-matt', 'soft-touch'], 'auflage' => ['100', '250', '500', '1000']],
                'price' => ['base' => ['100' => 24.0, '250' => 34.0, '500' => 44.0, '1000' => 64.0], 'by' => ['veredelung' => ['folienkaschierung-matt' => 1.0, 'soft-touch' => 1.2]]]],
            ['area' => 'visitenkarten', 'slug' => 'visitenkarten-goldpraegung', 'name' => 'Visitenkarten mit Goldprägung', 'summary' => 'Ein Akzent in Gold oder Silber, der im Licht glänzt.', 'description' => '300 g Bilderdruck matt, beidseitig bedruckt, mit partieller Heißfolienprägung.',
                'drawing' => ['type' => 'card', 'format' => 'visitenkarte', 'motif' => 'kanzlei'], 'offers' => ['veredelung' => ['goldpraegung', 'silberpraegung'], 'auflage' => ['100', '250', '500', '1000']],
                'price' => ['base' => ['100' => 39.0, '250' => 54.0, '500' => 74.0, '1000' => 109.0]]],
            ['area' => 'visitenkarten', 'slug' => 'klappvisitenkarten', 'name' => 'Klappvisitenkarten', 'summary' => 'Vier Seiten im Visitenkartenformat – Platz für Leistungen und Öffnungszeiten.', 'description' => 'Gerillt und gefalzt, beidseitig bedruckt auf 300 g Bilderdruck.',
                'drawing' => ['type' => 'card', 'format' => 'visitenkarte', 'fold' => true, 'motif' => 'werkstatt'], 'offers' => $cardPapers + ['auflage' => ['100', '250', '500', '1000']],
                'price' => ['base' => ['100' => 29.0, '250' => 39.0, '500' => 54.0, '1000' => 79.0]]],

            // --- Karten -----------------------------------------------------------------
            ['area' => 'karten', 'slug' => 'postkarten', 'name' => 'Postkarten', 'summary' => 'DIN A6 im Querformat, ein- oder beidseitig bedruckt.', 'description' => 'Auf 300 g Bilderdruck, matt oder glänzend.',
                'drawing' => ['type' => 'card', 'format' => 'postkarte', 'motif' => 'atelier'], 'offers' => $cardPapers + ['druck' => ['einseitig', 'beidseitig'], 'auflage' => ['100', '250', '500', '1000']],
                'price' => ['base' => ['100' => 19.0, '250' => 24.0, '500' => 34.0, '1000' => 49.0], 'by' => ['druck' => ['einseitig' => 1.0, 'beidseitig' => 1.2]]]],
            ['area' => 'karten', 'slug' => 'quadratische-karten', 'name' => 'Quadratische Karten', 'summary' => '148 × 148 mm – für Grüße, die auffallen.', 'description' => 'Beidseitig bedruckt auf 300 g Bilderdruck, matt oder glänzend.',
                'drawing' => ['type' => 'card', 'format' => 'quadrat', 'motif' => 'praxis'], 'offers' => $cardPapers + ['auflage' => ['100', '250', '500', '1000']],
                'price' => ['base' => ['100' => 24.0, '250' => 34.0, '500' => 44.0, '1000' => 64.0]]],
            ['area' => 'karten', 'slug' => 'klappkarten', 'name' => 'Klappkarten', 'summary' => 'DIN A6, gerillt und gefalzt – vier Seiten für Einladung und Dank.', 'description' => 'Beidseitig bedruckt auf 300 g Bilderdruck, matt oder glänzend.',
                'drawing' => ['type' => 'card', 'format' => 'postkarte', 'fold' => true, 'motif' => 'atelier'], 'offers' => $cardPapers + ['auflage' => ['100', '250', '500', '1000']],
                'price' => ['base' => ['100' => 34.0, '250' => 49.0, '500' => 69.0, '1000' => 99.0]]],
            ['area' => 'karten', 'slug' => 'einladungskarten-din-lang', 'name' => 'Einladungskarten DIN lang', 'summary' => 'Schmal und elegant – passend zum Umschlag DIN lang.', 'description' => 'Beidseitig bedruckt auf 300 g Bilderdruck, matt oder glänzend.',
                'drawing' => ['type' => 'card', 'format' => 'lang', 'motif' => 'kanzlei'], 'offers' => $cardPapers + ['auflage' => ['100', '250', '500', '1000']],
                'price' => ['base' => ['100' => 29.0, '250' => 39.0, '500' => 54.0, '1000' => 79.0]]],

            // --- Briefpapier ------------------------------------------------------------
            ['area' => 'briefpapier', 'slug' => 'briefbogen', 'name' => 'Briefbogen', 'summary' => 'DIN A4 auf Offsetpapier – für Drucker im Büro geeignet.', 'description' => 'Ihr Briefkopf in Farbe, auf 90 g oder auf 120 g Offsetpapier.',
                'drawing' => ['type' => 'letter', 'format' => 'a4', 'motif' => 'monogramm'], 'offers' => ['papier' => ['90-g-offset', '120-g-offset'], 'druck' => ['einseitig', 'beidseitig'], 'auflage' => ['250', '500', '1000', '2500']],
                'price' => ['base' => ['250' => 39.0, '500' => 54.0, '1000' => 79.0, '2500' => 149.0], 'by' => ['papier' => ['90-g-offset' => 1.0, '120-g-offset' => 1.15], 'druck' => ['einseitig' => 1.0, 'beidseitig' => 1.3]]]],
            ['area' => 'briefpapier', 'slug' => 'briefumschlaege-din-lang', 'name' => 'Briefumschläge DIN lang', 'summary' => 'Für den zweimal gefalzten A4-Bogen – mit oder ohne Fenster.', 'description' => '75 g Offsetpapier weiß, farbig bedruckt, nassklebend.',
                'drawing' => ['type' => 'envelope', 'format' => 'lang', 'motif' => 'monogramm'], 'offers' => ['fenster' => ['mit-fenster', 'ohne-fenster'], 'auflage' => ['250', '500', '1000', '2500']],
                'price' => ['base' => ['250' => 49.0, '500' => 69.0, '1000' => 99.0, '2500' => 199.0]]],
            ['area' => 'briefpapier', 'slug' => 'briefumschlaege-din-c5', 'name' => 'Briefumschläge DIN C5', 'summary' => 'Für den einmal gefalzten A4-Bogen – mit oder ohne Fenster.', 'description' => '90 g Offsetpapier weiß, farbig bedruckt, haftklebend.',
                'drawing' => ['type' => 'envelope', 'format' => 'c5', 'motif' => 'monogramm'], 'offers' => ['fenster' => ['mit-fenster', 'ohne-fenster'], 'auflage' => ['250', '500', '1000', '2500']],
                'price' => ['base' => ['250' => 69.0, '500' => 99.0, '1000' => 149.0, '2500' => 299.0]]],
            ['area' => 'briefpapier', 'slug' => 'notizbloecke', 'name' => 'Notizblöcke', 'summary' => 'Mit Leimbindung oben – Ihr Logo auf jedem Blatt.', 'description' => 'Je Block 25 oder 50 Blatt, 90 g Offsetpapier mit Graupappe als Rücken.',
                'drawing' => ['type' => 'notepad', 'motif' => 'monogramm'], 'offers' => ['format' => ['din-a5', 'din-a6'], 'blatt' => ['25-blatt', '50-blatt'], 'auflage' => ['10', '25', '50', '100']],
                'price' => ['base' => ['10' => 29.0, '25' => 49.0, '50' => 79.0, '100' => 129.0], 'by' => ['format' => ['din-a5' => 1.0, 'din-a6' => 0.7], 'blatt' => ['25-blatt' => 1.0, '50-blatt' => 1.5]]]],
            ['area' => 'briefpapier', 'slug' => 'praesentationsmappen', 'name' => 'Präsentationsmappen', 'summary' => 'DIN A4 mit Lasche und Schlitz für die Visitenkarte.', 'description' => '300 g Bilderdruck, außen bedruckt, auf Wunsch glänzend cellophaniert.',
                'drawing' => ['type' => 'folder', 'format' => 'a4', 'motif' => 'eroeffnung'], 'offers' => ['veredelung' => ['ohne', 'cellophanierung-glaenzend'], 'auflage' => ['50', '100', '250', '500']],
                'price' => ['base' => ['50' => 119.0, '100' => 159.0, '250' => 249.0, '500' => 389.0], 'by' => ['veredelung' => ['ohne' => 1.0, 'cellophanierung-glaenzend' => 1.2]]]],

            // --- Broschüren -------------------------------------------------------------
            ['area' => 'broschueren', 'slug' => 'broschueren-rueckstichheftung', 'name' => 'Broschüren mit Rückstichheftung', 'summary' => 'Mit zwei Klammern geheftet, von 8 bis 24 Seiten.', 'description' => 'Umschlag und Inhalt auf 135 g Bilderdruck, durchgehend farbig.',
                'drawing' => ['type' => 'brochure', 'binding' => 'staple', 'motif' => 'kursplan'], 'offers' => ['format' => ['din-a5', 'din-a4', 'din-lang'], 'umfang' => ['8-seiten', '12-seiten', '16-seiten', '24-seiten'], 'auflage' => ['50', '100', '250', '500']],
                'price' => ['base' => ['50' => 89.0, '100' => 129.0, '250' => 219.0, '500' => 349.0], 'by' => ['format' => ['din-a5' => 1.0, 'din-a4' => 1.6, 'din-lang' => 0.9], 'umfang' => ['8-seiten' => 1.0, '12-seiten' => 1.3, '16-seiten' => 1.6, '24-seiten' => 2.2]]]],
            ['area' => 'broschueren', 'slug' => 'broschueren-klebebindung', 'name' => 'Broschüren mit Klebebindung', 'summary' => 'Mit geradem Rücken, von 48 bis 96 Seiten – wie ein Buch.', 'description' => 'Umschlag 300 g Bilderdruck, Inhalt 115 g Bilderdruck, durchgehend farbig.',
                'drawing' => ['type' => 'brochure', 'binding' => 'perfect', 'motif' => 'fest'], 'offers' => ['format' => ['din-a5', 'din-a4'], 'umfang' => ['48-seiten', '64-seiten', '96-seiten'], 'auflage' => ['25', '50', '100', '250']],
                'price' => ['base' => ['25' => 149.0, '50' => 219.0, '100' => 349.0, '250' => 649.0], 'by' => ['format' => ['din-a5' => 1.0, 'din-a4' => 1.6], 'umfang' => ['48-seiten' => 1.0, '64-seiten' => 1.25, '96-seiten' => 1.7]]]],

            // --- Plakate ----------------------------------------------------------------
            ['area' => 'plakate', 'slug' => 'plakate', 'name' => 'Plakate', 'summary' => 'Von DIN A3 bis DIN A0, matt oder glänzend – ab einem Stück.', 'description' => 'Einseitig farbig bedruckt auf 135 g Bilderdruck.',
                'drawing' => ['type' => 'poster', 'motif' => 'eroeffnung'], 'offers' => ['format' => ['din-a3', 'din-a2', 'din-a1', 'din-a0'], 'papier' => ['135-g-matt', '135-g-glaenzend'], 'auflage' => ['1', '5', '10', '25', '50']],
                'price' => ['base' => ['1' => 9.0, '5' => 24.0, '10' => 34.0, '25' => 59.0, '50' => 89.0], 'by' => ['format' => ['din-a3' => 1.0, 'din-a2' => 1.6, 'din-a1' => 2.4, 'din-a0' => 3.8]]]],
            ['area' => 'plakate', 'slug' => 'poster-auf-fotopapier', 'name' => 'Poster auf Fotopapier', 'summary' => 'Seidenglänzendes Fotopapier für Bilder mit Tiefe.', 'description' => '200 g Fotopapier, im Digitaldruck – schon ab einem Stück.',
                'drawing' => ['type' => 'poster', 'gloss' => true, 'motif' => 'fest'], 'offers' => ['format' => ['din-a3', 'din-a2', 'din-a1'], 'auflage' => ['1', '5', '10']],
                'price' => ['base' => ['1' => 19.0, '5' => 69.0, '10' => 119.0], 'by' => ['format' => ['din-a3' => 1.0, 'din-a2' => 1.7, 'din-a1' => 2.6]]]],

            // --- Kalender ---------------------------------------------------------------
            ['area' => 'kalender', 'slug' => 'wandkalender', 'name' => 'Wandkalender', 'summary' => 'Zwölf Monatsblätter und ein Deckblatt, mit Wire-O-Bindung und Aufhänger.', 'description' => '170 g Bilderdruck, jeder Monat mit Ihrem Bild.',
                'drawing' => ['type' => 'calendar', 'variant' => 'wall', 'motif' => 'wellen'], 'offers' => ['format' => ['din-a4', 'din-a3'], 'auflage' => ['10', '25', '50', '100']],
                'price' => ['base' => ['10' => 149.0, '25' => 249.0, '50' => 399.0, '100' => 649.0], 'by' => ['format' => ['din-a4' => 1.0, 'din-a3' => 1.4]]]],
            ['area' => 'kalender', 'slug' => 'tischkalender', 'name' => 'Tischkalender', 'summary' => 'Steht auf jedem Schreibtisch – zwölf Monate im Querformat.', 'description' => 'Mit Aufsteller aus Graupappe und Wire-O-Bindung.',
                'drawing' => ['type' => 'calendar', 'variant' => 'desk', 'motif' => 'sonne'], 'offers' => ['auflage' => ['10', '25', '50', '100']],
                'price' => ['base' => ['10' => 119.0, '25' => 199.0, '50' => 329.0, '100' => 529.0]]],
            ['area' => 'kalender', 'slug' => 'jahresplaner', 'name' => 'Jahresplaner', 'summary' => 'Das ganze Jahr auf einem Blatt – für Büro und Werkstatt.', 'description' => 'Einseitig bedruckt auf 170 g Bilderdruck, im Querformat.',
                'drawing' => ['type' => 'calendar', 'variant' => 'planner', 'motif' => 'monogramm'], 'offers' => ['format' => ['din-a2', 'din-a1'], 'auflage' => ['10', '25', '50']],
                'price' => ['base' => ['10' => 89.0, '25' => 149.0, '50' => 229.0], 'by' => ['format' => ['din-a2' => 1.0, 'din-a1' => 1.5]]]],

            // --- Werbetechnik -----------------------------------------------------------
            ['area' => 'werbetechnik', 'slug' => 'roll-ups', 'name' => 'Roll-ups', 'summary' => 'Mit Kassette und Tasche – in einer Minute aufgebaut.', 'description' => 'Bedruckte Polyesterfolie mit Aluminiumkassette, Stange und Tragetasche.',
                'drawing' => ['type' => 'rollup', 'motif' => 'prozent'], 'offers' => ['format' => ['85x200', '100x200', '120x200'], 'modell' => ['basic', 'premium'], 'auflage' => ['1', '2', '5']],
                'price' => ['base' => ['1' => 79.0, '2' => 149.0, '5' => 349.0], 'by' => ['format' => ['85x200' => 1.0, '100x200' => 1.2, '120x200' => 1.45], 'modell' => ['basic' => 1.0, 'premium' => 1.6]]]],
            ['area' => 'werbetechnik', 'slug' => 'pvc-banner', 'name' => 'PVC-Banner', 'summary' => 'Wetterfeste Plane mit Ösen ringsum – für Zaun, Fassade und Bühne.', 'description' => '500 g Frontlit-Plane, gesäumt, mit Ösen alle 50 cm.',
                'drawing' => ['type' => 'banner'], 'offers' => ['format' => ['200x100', '300x100', '400x100'], 'auflage' => ['1', '2', '5']],
                'price' => ['base' => ['1' => 49.0, '2' => 89.0, '5' => 199.0], 'by' => ['format' => ['200x100' => 1.0, '300x100' => 1.4, '400x100' => 1.8]]]],
            ['area' => 'werbetechnik', 'slug' => 'kundenstopper', 'name' => 'Kundenstopper', 'summary' => 'Der Aufsteller für den Gehweg – mit Klapprahmen und Plakat.', 'description' => 'Aluminium-Klapprahmen, beidseitig mit wetterfestem Plakat bestückt.',
                'drawing' => ['type' => 'standee'], 'offers' => ['format' => ['din-a1', 'din-a0'], 'auflage' => ['1', '2', '5']],
                'price' => ['base' => ['1' => 99.0, '2' => 189.0, '5' => 449.0], 'by' => ['format' => ['din-a1' => 1.0, 'din-a0' => 1.5]]]],

            // --- Verpackungen -----------------------------------------------------------
            ['area' => 'verpackungen', 'slug' => 'versandkartons', 'name' => 'Versandkartons', 'summary' => 'Wellpappe mit Ihrem Logo – in drei Größen.', 'description' => 'Einwellige Wellpappe, außen einfarbig oder farbig bedruckt, flach geliefert.',
                'drawing' => ['type' => 'box'], 'offers' => ['farbe' => ['braun', 'weiss'], 'format' => ['s', 'm', 'l'], 'auflage' => ['50', '100', '250', '500']],
                'price' => ['base' => ['50' => 89.0, '100' => 139.0, '250' => 289.0, '500' => 489.0], 'by' => ['farbe' => ['braun' => 1.0, 'weiss' => 1.15], 'format' => ['s' => 1.0, 'm' => 1.35, 'l' => 1.8]]]],
            ['area' => 'verpackungen', 'slug' => 'papiertragetaschen', 'name' => 'Papiertragetaschen', 'summary' => 'Mit gedrehten Papierkordeln – für Laden, Messe und Geschenk.', 'description' => 'Kraftpapier in Braun oder Weiß, mit Ihrem Logo auf der Vorderseite.',
                'drawing' => ['type' => 'bag'], 'offers' => ['farbe' => ['braun', 'weiss'], 'format' => ['klein', 'gross'], 'auflage' => ['50', '100', '250', '500']],
                'price' => ['base' => ['50' => 69.0, '100' => 109.0, '250' => 219.0, '500' => 379.0], 'by' => ['farbe' => ['braun' => 1.0, 'weiss' => 1.1], 'format' => ['klein' => 1.0, 'gross' => 1.3]]]],

            // --- Textilien --------------------------------------------------------------
            ['area' => 'textilien', 'slug' => 't-shirts', 'name' => 'T-Shirts', 'summary' => 'Gekämmte Baumwolle, bedruckt auf der Brust.', 'description' => 'Rundhals, 180 g/m², im Direktdruck mit Ihrem Motiv.',
                'drawing' => ['type' => 'shirt', 'cut' => 'tshirt'], 'offers' => ['farbe' => ['weiss', 'schwarz', 'navy'], 'textilgroesse' => ['s', 'm', 'l', 'xl', 'xxl'], 'auflage' => ['10', '25', '50']],
                'price' => ['base' => ['10' => 129.0, '25' => 279.0, '50' => 499.0], 'by' => ['farbe' => ['weiss' => 1.0, 'schwarz' => 1.1, 'navy' => 1.1], 'textilgroesse' => ['s' => 1.0, 'm' => 1.0, 'l' => 1.0, 'xl' => 1.0, 'xxl' => 1.1]]]],
            ['area' => 'textilien', 'slug' => 'hoodies', 'name' => 'Hoodies', 'summary' => 'Mit Kapuze und Kängurutasche, bedruckt auf der Brust.', 'description' => 'Baumwolle mit Polyester, 280 g/m², innen angeraut.',
                'drawing' => ['type' => 'shirt', 'cut' => 'hoodie'], 'offers' => ['farbe' => ['schwarz', 'grau-meliert'], 'textilgroesse' => ['s', 'm', 'l', 'xl', 'xxl'], 'auflage' => ['10', '25', '50']],
                'price' => ['base' => ['10' => 299.0, '25' => 679.0, '50' => 1249.0], 'by' => ['textilgroesse' => ['s' => 1.0, 'm' => 1.0, 'l' => 1.0, 'xl' => 1.0, 'xxl' => 1.08]]]],
            ['area' => 'textilien', 'slug' => 'baumwolltaschen', 'name' => 'Baumwolltaschen', 'summary' => 'Mit langen Henkeln, auf einer Seite bedruckt.', 'description' => '38 × 42 cm, 140 g/m² Baumwolle.',
                'drawing' => ['type' => 'tote', 'handles' => 'long'], 'offers' => ['farbe' => ['natur', 'schwarz'], 'auflage' => ['25', '50', '100', '250']],
                'price' => ['base' => ['25' => 99.0, '50' => 169.0, '100' => 289.0, '250' => 599.0], 'by' => ['farbe' => ['natur' => 1.0, 'schwarz' => 1.15]]]],

            // --- Werbeartikel -----------------------------------------------------------
            ['area' => 'werbeartikel', 'slug' => 'tassen', 'name' => 'Tassen', 'summary' => 'Keramik, 330 ml, spülmaschinenfest bedruckt.', 'description' => 'Rundum bedruckbar, einzeln im Karton verpackt.',
                'drawing' => ['type' => 'mug', 'style' => 'classic'], 'offers' => ['farbe' => ['weiss', 'schwarz'], 'auflage' => ['12', '36', '72', '144']],
                'price' => ['base' => ['12' => 79.0, '36' => 179.0, '72' => 319.0, '144' => 569.0], 'by' => ['farbe' => ['weiss' => 1.0, 'schwarz' => 1.15]]]],
            ['area' => 'werbeartikel', 'slug' => 'trinkflaschen', 'name' => 'Trinkflaschen', 'summary' => 'Edelstahl, doppelwandig – Ihr Logo bleibt mit unterwegs.', 'description' => 'Hält Getränke 12 Stunden warm und 24 Stunden kalt.',
                'drawing' => ['type' => 'bottle'], 'offers' => ['farbe' => ['silber', 'schwarz', 'weiss'], 'format' => ['500-ml', '750-ml'], 'auflage' => ['25', '50', '100']],
                'price' => ['base' => ['25' => 299.0, '50' => 529.0, '100' => 949.0], 'by' => ['farbe' => ['silber' => 1.0, 'schwarz' => 1.05, 'weiss' => 1.05], 'format' => ['500-ml' => 1.0, '750-ml' => 1.2]]]],
            ['area' => 'werbeartikel', 'slug' => 'bierdeckel', 'name' => 'Bierdeckel', 'summary' => 'Saugfähiger Karton, beidseitig bedruckt.', 'description' => '1,4 mm Holzschliffpappe, rund oder eckig.',
                'drawing' => ['type' => 'coaster'], 'offers' => ['format' => ['rund-107', 'eckig-93'], 'auflage' => ['500', '1000', '2500', '5000']],
                'price' => ['base' => ['500' => 79.0, '1000' => 119.0, '2500' => 229.0, '5000' => 389.0], 'by' => ['format' => $same(['rund-107', 'eckig-93'])]]],
        ];
    })(),
];
