# Stiqon.com

Onlineshop für Werbemittel – von Sticker und Visitenkarte über Plakat und Kalender bis Roll-up, T-Shirt und Tasse – auf WordPress und WooCommerce, im Aufbau.

Dieses Repository enthält, was den Shop von einer Standard-Installation unterscheidet:

| Verzeichnis | Inhalt |
|---|---|
| `wp-content/themes/stiqon` | das Theme: Aussehen aller Seiten, Vorlagen für die Shop-Seiten |
| `wp-content/plugins/stiqon-shop` | das Plugin „Stiqon Shop-Funktionen“: gezeichnete Produktansichten, Motiv-Vorschau und weitere Funktionen |
| `tools/dev` | Skripte, die eine lokale WordPress-Umgebung aufbauen |
| `tools/brand` | Logo-Original und das Skript, das daraus die Logo-Dateien des Themes erzeugt |
| `tests` | Browser-Tests (`e2e`), Unit-Tests des Plugins (`php`), Prüfungen in der Installation (`integration`) |

WordPress selbst, WooCommerce, Germanized und Mollie gehören nicht ins Repository. Welche Versionen
verwendet und getestet werden, steht in `tools/dev/versions.env`.

Die frühere Fassung des Shops als eigene Laravel-Anwendung liegt in der Git-Historie (bis Commit
`3cde78a`).

## Entwicklung

Voraussetzungen: PHP ≥ 8.1 mit `mysqli`, ein laufender MariaDB- oder MySQL-Server, Node.js 22,
Composer, `curl`, `unzip` sowie Bash ≥ 4.4 und die GNU-Werkzeuge `sha256sum`, `sha512sum` und
`sed` (unter Linux vorhanden; auf macOS über Homebrew: `bash`, `coreutils`, `gnu-sed`).

```bash
npm ci
```

```bash
npm run build
```

```bash
tools/dev/setup-wordpress.sh
```

```bash
tools/dev/serve.sh
```

Das Einrichtungsskript baut unter `~/.stiqon-wp` (als root: `/srv/stiqon-wp`) eine vollständige
Installation auf: WordPress, WooCommerce, Germanized und Mollie in den festgelegten Versionen, auf Deutsch,
mit Theme und Plugin aus diesem Repository als Verknüpfung. Der Shop läuft danach unter
`http://127.0.0.1:8200`. Benutzer und Passwort der lokalen Verwaltung stehen in
`sites/dev.env` neben der Installation – nie im Repository.

Gut zu wissen:

- WordPress, WooCommerce, Germanized, Mollie und WP-CLI kommen von festen Adressen und werden gegen die
  Prüfsummen in `tools/dev/versions.env` geprüft; ein Paket, das nicht passt, wird gelöscht und
  abgelehnt. Nicht festlegbar sind die Sprachpakete: Sie werden ohne neue Versionsnummer
  überarbeitet.
- WP-CLI läuft nie als root. Wo das Skript als root gestartet wird (Container), legt es dafür den
  Benutzer `wpdev` an. Ihm gehören nur die Installationen selbst; die Datei mit Adresse und
  Passwörtern (`sites/<name>.env`) und die heruntergeladenen Pakete gehören root, und die Skripte
  lesen die Datei als Daten ein, statt sie auszuführen.
- Ein zweiter Aufruf ohne `--fresh` behält Inhalte und Passwörter, bringt WordPress und Plugins auf
  die festgelegten Versionen und setzt Adresse, Sprache, Theme und die Grundeinstellungen des
  Shops erneut.
- Die lokale Installation ruft nichts von außen ab (`WP_HTTP_BLOCK_EXTERNAL`) und aktualisiert
  sich nicht selbst.
- Sie verschickt auch keine E-Mails: Was WordPress senden würde, liegt als je eine JSON-Datei in
  `sites/<name>.mails` neben der Installation (`tools/dev/mu-plugins/stiqon-dev-mail.php`). Dort
  lesen die Tests – und wer sehen will, was eine Kundin bekäme.
- `tools/dev/shop-settings.php` richtet den Shop zum Kaufen ein: Lieferung innerhalb Deutschlands
  zu einem Preis, Vorkasse, Kundenkonto auf Wunsch, die klassischen Seiten für Warenkorb und
  Kasse, Rechtsseiten. Beträge, Bankverbindung, Adresse und Rechtstexte darin sind Beispiele und
  als solche beschriftet. Auf dem echten Shop sind das Einstellungen des Inhabers.
- `tools/dev/wp.sh` führt WP-CLI in der lokalen Installation aus, zum Beispiel
  `tools/dev/wp.sh plugin list`.
- Nach Änderungen an Styles oder Skripten: `npm run build` (oder `npm run watch`).
- Datenbank: Ohne weitere Angaben meldet sich das Skript als `root` über den lokalen Socket an –
  das funktioniert, wenn es selbst als root läuft (üblich in Containern unter Debian/Ubuntu).
  Sonst den Zugang angeben: `STIQON_DB_HOST`, `STIQON_DB_ADMIN_USER`, `STIQON_DB_ADMIN_PASSWORD`.

## Tests

```bash
composer install
```

```bash
composer test
```

```bash
npm run e2e
```

```bash
tests/integration/run.sh
```

- **Unit-Tests** (`tests/php`) prüfen die Logik des Plugins ohne WordPress.
- **Browser-Tests** (`tests/e2e`, Playwright) bauen für jeden Lauf eine frische Installation
  („e2e“, Port 8123) und prüfen sie am Desktop und am Handy. Ist der Port schon belegt, bricht der
  Lauf ab, statt eine alte Installation mitzubenutzen. Jeder Test schlägt fehl, wenn eine Seite
  Konsolenfehler meldet oder Anfragen an fremde Server stellt.
- **Prüfungen in der Installation** (`tests/integration`) laufen über WP-CLI in der Installation
  „e2e“, also nach den Browser-Tests. Danach wird ihr Fehlerprotokoll gelesen: Jede Zeile, die
  nicht in `tests/integration/log-noise.txt` als bekannte Meldung von WordPress, WooCommerce oder
  Germanized begründet ist, lässt den Lauf scheitern.
- **PHP-Versionen:** Die CI prüft den Shop auf PHP 8.1 – der ältesten Version, die Theme und
  Plugin zusagen – und auf PHP 8.3, jeweils so, wie PHP ausgeliefert wird: ohne den
  JIT-Übersetzer (`opcache.jit_buffer_size=0`). Mit eingeschaltetem JIT stürzt PHP 8.1.34 ab,
  während es den Shop ausliefert („Segmentation fault“ im übersetzten Code); das ist ein Fehler
  in PHP 8.1, das keine Korrekturen mehr bekommt. Auf PHP 8.3 lief derselbe Testlauf im
  November 2026 auch mit JIT durch. Für den Betrieb heißt das: den JIT ausgeschaltet lassen
  (PHPs Voreinstellung und die der meisten Hoster) – und PHP 8.3 nehmen, wo es zur Wahl steht.

## Seiten

Das Theme bringt Vorlagen für Seiten mit festen Adressen mit. Das Einrichtungsskript legt die
Seiten an; auf einer echten Installation legt man sie in WordPress unter „Seiten“ mit genau dieser
Adresse an.

| Adresse | Vorlage | Hinweis |
|---|---|---|
| `/` | `front-page.php` | Startseite |
| `/sticker/`, `/flyer/`, `/visitenkarten/`, `/karten/`, `/briefpapier/`, `/broschueren/`, `/plakate/`, `/kalender/`, `/werbetechnik/`, `/verpackungen/`, `/textilien/`, `/werbeartikel/` | `page-bereich.php` | Bereiche mit Galerie: je Produkt und je Wert der Option, die man in der Zeichnung sieht (Material, Papier, Farbe …), eine Karte. Die Filter sind Links und funktionieren ohne Skript; am Desktop öffnet eine Karte groß über der Galerie. Welche Bereiche es gibt, steht an einer Stelle: `stiqon_area_definitions()` in `inc/site.php` |
| `/produkt/…` | `woocommerce/…` | Produktseite auf den Vorlagen von WooCommerce, damit sich Germanized und weitere Plugins einhängen können |
| `/?s=…` | `search.php` | Ergebnisseite der Suche |
| `/warenkorb/`, `/kasse/`, `/mein-konto/` | `page.php` | Die Seiten, die WooCommerce zeichnet – siehe „Kaufen“ |
| `/agb/`, `/impressum/` … | `page.php` | Geschriebene Seiten aus dem Editor. Welche davon AGB, Widerruf, Versand, Datenschutz und Impressum sind, steht in den Einstellungen von WooCommerce und Germanized; der Fuß verlinkt sie von dort |
| `/referenzen/` | `archive-stiqon_referenz.php` | Referenzen: gelieferte Arbeiten mit Foto, Name und ein, zwei Sätzen (Inhaltstyp des Plugins, in der Verwaltung unter „Referenzen“). Die Seite und alle Links darauf gibt es erst, wenn eine Referenz veröffentlicht ist und ein Foto hat |
| `/musterbogen/` | `page-musterbogen.php` | Logo, Schrift, Farben und Mockups auf einer Seite. Nur wer das Erscheinungsbild der Website verwalten darf, sieht sie; alle anderen bekommen „nicht gefunden“ – auch wenn die Seite versehentlich veröffentlicht wurde |

Fehlt eine der Bereichsseiten oder fehlen dem Theme seine gebauten Dateien, weist die
Verwaltung darauf hin (`inc/checks.php`).

Alle Produktansichten zeichnet das Plugin (`src/Mockup`, Vorlagen unter `templates`) – Fotos gibt es
nur auf der Startseite. Jede Art von Produkt ist eine Klasse, die `Mockup\Kind` erfüllt: Sticker,
Flyer, Faltblatt, Karte (Visiten-, Post-, Klappkarte), Briefbogen, Briefumschlag, Notizblock,
Präsentationsmappe, Broschüre, Plakat, Kalender, Roll-up, Banner, Kundenstopper, Versandkarton,
Papiertragetasche, Baumwolltasche, Textil (T-Shirt, Hoodie), Tasse, Trinkflasche und Bierdeckel.
`Mockup\Kinds` listet sie mit den Wörtern, an denen man sie erkennt; die Reihenfolge zählt, weil
das Deutsche Wörter aus Wörtern baut („Plakatständer“ ist ein Kundenstopper, kein Plakat). Was ein
Produkt ist, welche Form oder welches Format es hat und wie seine Optionen aussehen, liest das
Plugin aus Namen und Kategorie (`src/Catalog`): zuerst aus dem Namen des Produkts, dann aus seinen
Kategorien. In der Verwaltung zeigt der Reiter „Vorschau“ am Produkt, was dabei herauskommt, und lässt
Art, Form bzw. Format und Beispielmotiv festlegen (`src/Admin/ProductPreview.php`).
Jede Zeichnung druckt über `templates/mockup/parts/motif.php`; so erscheint das Motiv der Kundschaft
auf allen Arten gleich. Beispielmotive füllen die Druckfläche; ein eigenes Motiv bleibt auf Bannern und
Roll-ups ganz sichtbar, statt zu einem Streifen beschnitten zu werden (`ownFit`). `php tools/dev/mockup-sheet.php > blatt.html` zeigt alle Zeichnungen auf
einer Seite, ohne WordPress (`--own` mit einem Kundenmotiv, Arten als Argumente: `card mug`).
Eine neue Art braucht eine Klasse und eine Vorlage, einen Eintrag in `Mockup\Kinds` und einen
Unit-Test wie die vorhandenen (`tests/php/Mockup`).
Ist es abgeschaltet, erscheinen die Seiten ohne Ansichten, und die Verwaltung weist darauf hin.
Theme und Plugin lassen sich in beliebiger Reihenfolge aktivieren. Kennt das Plugin eine verlangte
Form, ein Motiv oder eine Kartenfarbe nicht, bleibt in der Entwicklung die Seite stehen, damit der
Fehler auffällt; im Live-Betrieb fehlt nur diese eine Ansicht, und der Grund steht im
Fehlerprotokoll.

Angeboten wird, was sich kaufen lässt: Ein Material, das am Produkt angehakt ist, aber keine
Variante mit Preis hat, bekommt keine Karte, keine Ansicht und keinen Auswahlknopf. Nicht jede
Kombination von Optionen muss es geben. Passt eine gewählte Option nicht zum Rest der Auswahl,
wandert der Rest zur nächsten Kombination, die es gibt – die Seite sagt das unter den Optionen –,
und auch eine Adresse mit nur einer Option öffnet eine vorhandene Kombination. Server und Browser
folgen dabei derselben Regel (`Catalog\Choice::closest()` im Plugin, `variation-match.js` im
Theme; gemeinsame Fälle in `tests/fixtures/choice-cases.json`).

Ein Bereich ist eine Produktkategorie der obersten Ebene, für die das Theme eine Seite hat; ein
Produkt gehört zum Bereich über seinen Kategorien. Welche Seite das ist, sagt das Theme dem Plugin
über den Filter `stiqon_shop_area_url` (leer für Kategorien ohne Seite). Ein Produkt mit Passwort
erscheint weder in der Galerie noch in der Suche – es ist für die gedacht, die seine Adresse
bekommen haben.

Beim ersten Aktivieren setzt das Plugin Datenschutz-Voreinstellungen (`src/Setup/Defaults.php`):
keine Profilbilder von Gravatar, neue Inhalte ohne Kommentare, keine Herkunftsmessung von
Bestellungen per Cookie, keine Nutzungsdaten an WooCommerce. Jede davon wird genau einmal gesetzt;
was die Inhaberin oder der Inhaber später ändert, bleibt auch nach erneutem Aktivieren bestehen.

## Sortiment

Der Shop hat zwölf Bereiche, nach dem Vorbild der Online-Druckerei printworld.com (November 2026):
Sticker, Flyer (mit Faltblättern), Visitenkarten, Karten, Briefpapier (mit Umschlägen, Notizblöcken
und Präsentationsmappen), Broschüren, Plakate, Kalender, Werbetechnik (Roll-ups, Banner,
Kundenstopper), Verpackungen, Textilien und Werbeartikel. Kopf, Fuß, Startseite und Einrichtung lesen
sie aus `stiqon_area_definitions()`; ein Bereich braucht dort einen Eintrag, eine Seite mit derselben
Adresse und eine Produktkategorie mit demselben Slug. Die Startseite zeigt jeden Bereich mit dem Foto,
das Stiqon geliefert hat, oder – wo keins da ist – mit einer Zeichnung.

Das Beispielsortiment (`tools/dev/example-assortment.php`, angelegt von
`tools/dev/example-catalogue.php`) hat 43 Produkte mit 641 Varianten: Optionen wie bei printworld
(Format, Papier, Veredelung, Umfang, Farbe, Größe, Auflage), aber weniger davon, damit jede
Kombination eine Variante sein kann. Die Preise sind erfunden und als Beispiele beschriftet; die echten
kommen vom Lieferanten oder vom Inhaber. Kein Lieferant wird im Shop genannt.

## Kaufen

Warenkorb, Kasse, Bestellbestätigung und Kundenkonto sind die klassischen Seiten von WooCommerce
(Kurzbefehle `[woocommerce_cart]`, `[woocommerce_checkout]`, `[woocommerce_my_account]`), nicht
die aus Blöcken: Die wogen je rund 1,5 MB und bringen ihr eigenes Aussehen mit. Nutzt eine der
Seiten doch den Block, weist die Verwaltung darauf hin.

- **Markup bleibt, Aussehen kommt vom Theme.** Das Theme überschreibt hier keine Vorlagen:
  Zahlungs-Plugins und das Plugin des Lieferanten erwarten WooCommerces Markup und seine Skripte.
  Die Vorlage `page.php` ruft für diese Seiten `stiqon_declare_shop_page()` auf – WooCommerces
  Skripte bleiben, seine Stylesheets und die von Germanized entfallen, und
  `assets/src/css/shop.css` gestaltet das Markup über dessen Klassennamen.
- **Zeichnung statt Produktfoto:** Im Warenkorb und in der Übersicht der Kasse steht die Zeichnung
  genau der gewählten Variante. In der Kasse druckt WooCommerce den Produktnamen durch einen
  Filter, der Zeichnungen zerlegt; dort hängt sie deshalb an der Stelle der Anzahl
  (`inc/shop.php`).
- **Meldungen:** Die Fehlerliste bleibt die Liste, die WooCommerce druckt
  (`woocommerce/notices/error.php`) – sein Skript macht aus jeder Zeile einen Link zum Feld und
  wiederholt die Meldung dort.
- **Recht:** Bestellknopf, Pflichtangaben, Checkboxen, Widerruf und die Rechtstexte in E-Mails
  kommen von Germanized. Der Fuß jeder Seite verlinkt die Rechtsseiten und trägt den Knopf
  „Vertrag widerrufen“; den zweiten, den Germanized selbst anhängen würde, lässt das Theme weg.
  Die Rechtsseiten der lokalen Installation sind Platzhalter – die echten Texte liefert der
  Inhaber, geprüft von Anwältin, Anwalt oder Rechtstext-Dienst.
- **E-Mails** nutzen WooCommerces Gestaltung mit dem Logo des Themes im Kopf
  (`assets/brand/logo-mail-320.png`), solange unter WooCommerce › Einstellungen › E-Mails kein
  eigenes Kopfbild gewählt ist. Produktbilder zeigen sie nicht: Eine Zeichnung lässt sich nicht
  verschicken, und übrig bliebe WooCommerces grauer Platzhalter.
- **Vor der Eröffnung** kündigt WooCommerce den Shop an („Demnächst verfügbar“), auf der ganzen
  Website oder nur auf den Seiten des Shops. Wer die Ankündigung sieht, entscheidet WooCommerce;
  wie sie aussieht, das Theme (`inc/announcement.php`): im eigenen Look statt mit WooCommerces
  Platzhalter-Seite. Zu den Seiten des Shops zählen hier auch die Bereichsseiten. Impressum und
  Datenschutzerklärung bleiben immer erreichbar. WooCommerces privater Link („Mit Link
  teilen“) lässt seinen Inhaber auch dann herein, wenn nur die Shop-Seiten angekündigt sind: Er
  führt auf WooCommerces Shop-Seite, und das Theme reicht den Schlüssel von dort an eine
  Bereichsseite weiter (`inc/shop.php`).
- **Was die Ankündigung nicht verbirgt:** Sie ersetzt Seiten, nicht Schnittstellen. WooCommerce
  beantwortet auch während der Ankündigung Anfragen an seine Programmierschnittstellen
  (`/wp-json/wc/store/v1/products`, `/wp-json/wp/v2/product`) mit Sortiment und Preisen, legt per
  Adresse (`?add-to-cart=…`) in den Warenkorb und nimmt über die Store-API Bestellungen an. Das
  ist WooCommerces Verhalten, nicht das des Themes. Wer Sortiment und Preise bis zur Eröffnung
  wirklich zurückhalten will, lässt die Produkte bis dahin unveröffentlicht. Ebenso offen bleiben
  WordPress' Suche (`/wp-json/wp/v2/search`), Kategorien, Merkmale und Preisspanne
  (`/wp-json/wp/v2/product_cat`, `/wp-json/wc/store/v1/products/collection-data` und
  Verwandte), oEmbed und Feed einzelner Produkte, die Weiterleitung von `/?p=<id>` und – solange
  Suchmaschinen zugelassen sind – WordPress' Sitemap mit den Produktadressen; wenn nur die
  Shop-Seiten angekündigt sind, außerdem Kundenkonto und „Vertrag widerrufen“. Was das Theme
  schließen kann, schließt es: Eine Seite, an deren Stelle die Ankündigung steht, bittet
  Suchmaschinen, sie nicht aufzunehmen, und heißt „Stiqon öffnet bald“; sind nur die
  Shop-Seiten angekündigt, bieten die übrigen Seiten keinen Warenkorb an, und die Suche sagt,
  dass der Shop bald öffnet.
- **Anmeldung:** Eine fehlgeschlagene Anmeldung sagt nicht, ob es zu Adresse oder Name ein Konto
  gibt (`src/Security/LoginMessages.php` im Plugin). Registrierung und „Passwort vergessen“
  verraten es weiterhin – so arbeitet WooCommerce. Meldet sich ein Kunde an, während schon
  Artikel im Warenkorb liegen, und kommen dabei Artikel eines früheren Besuchs dazu, sagt der
  Shop es ihm.
- **„Vertrag widerrufen“** füllt Name und E-Mail-Adresse aus, wenn die Seite mit dem Link aus
  einer Bestellmail geöffnet wird (Germanized; der Schlüssel der Bestellung genügt). Das Theme
  hält die Seite deshalb aus Caches und Suchmaschinen heraus.
- **Einstellungen ohne Wirkung:** Germanizeds Darstellungsoptionen (etwa „Produkttabelle
  Farbe“) wirken mit diesem Theme nicht – Germanizeds Stylesheet entfällt. WooCommerces Wahl,
  Pflichtfelder nicht mit Sternchen zu markieren, gilt dagegen. Bewertungen zeigt das Theme
  nicht; unter WooCommerce › Einstellungen › Produkte sollten sie aus sein (die Einrichtung
  der lokalen Seiten schaltet sie ab).
- **Zähler im Kopf:** Die Zahl der Artikel schreibt der Server in jede Seite. Eine Seite kann
  älter sein als der Warenkorb – von der Zurück-Taste aus dem Speicher des Browsers geholt oder
  von einem Seiten-Cache ausgeliefert. Jede Seite trägt deshalb WooCommerces Prüfsumme des
  Warenkorbs, mit dem sie entstand; weicht die des Browsers ab, holt das Skript die Zahl neu
  (`assets/src/js/app.js`).
- **Solange die ganze Website angekündigt ist,** stehen Impressum und Datenschutzerklärung im
  schmalen Rahmen der Ankündigung (`header-announcement.php`, `footer-announcement.php`): Der
  Kopf des Shops böte Bereiche, Suche und Warenkorb an, die alle zur Ankündigung zurückführen.
- **Geschriebene Seiten** – die Rechtstexte – laden nichts von WooCommerce, solange ihr Text
  keinen Kurzbefehl und keinen Block von WooCommerce oder Germanized enthält
  (`stiqon_declare_written_page()` in `inc/cleanup.php`). Die Ankündigung der ganzen Website
  ebenso. Auf den Seiten des Shops lädt die Ankündigung dagegen weiter, was WooCommerce dort
  lädt: Ihr Kopf ist schon gedruckt, wenn feststeht, dass sie erscheint.
- **Ohne das Plugin „Stiqon Shop-Funktionen“** zeigen Warenkorb und Kasse ihre Zeilen ohne
  Zeichnung; **ohne WooCommerce** nennt der Fuß weiter Impressum, Datenschutzerklärung und die
  übrigen Rechtsseiten, und die Bereichsseiten zeigen keine Karten statt eines Fehlers – auch
  wenn das Plugin dabei eingeschaltet bleibt, wie WordPress es nach einem misslungenen Update
  von WooCommerce lässt. Beides prüft `tests/integration/plugins-off.php`.
- **Vorlagen aktuell halten:** Jede Vorlage unter `woocommerce/` nennt im Kopf (`@version`) die
  Fassung von WooCommerces Vorlage, aus der sie entstand. `tests/integration/templates.php`
  meldet, wenn WooCommerce eine davon geändert hat.

## Suche

Die Suche durchsucht nicht Beiträge und Seiten, sondern ein eigenes Verzeichnis des Sortiments:
Bereiche, Produkte und deren sichtbare Varianten (`src/Search` im Plugin). Ein Produkt steht für
alle seine Varianten; eine Variante erscheint, wenn nach ihr gefragt ist („kraftpapier“).

- Das Suchfeld im Kopf ist ein Link auf die Ergebnisseite. Mit Skript öffnet es die Suche über der
  Seite (auch mit Strg + K bzw. ⌘ K) und zeigt Treffer schon beim Tippen. Skript und Verzeichnis
  werden erst geladen, wenn jemand die Suche öffnet (`/wp-json/stiqon/v1/suchindex`).
- Die Ergebnisseite rechnet auf dem Server, die Soforttreffer im Browser – nach denselben Regeln.
  Dass beide Seiten übereinstimmen, prüfen gemeinsame Fälle in `tests/fixtures/search-cases.json`
  (PHP: `tests/php/Search`, Skript: `tests/e2e/search-logic.spec.js`). Wer die Regeln ändert,
  ändert `Search\Text`/`Search\Matcher` im Plugin und `assets/src/js/search-match.js` im Theme
  zusammen.
- Das Verzeichnis wird zwischengespeichert und verworfen, sobald sich ein Produkt, ein Begriff
  oder eine Adresse ändert – auch wenn ein Produkt in den Papierkorb wandert oder ein geplantes
  erscheint. Es gilt für alle: Seine Preise sind die, die ein Besucher ohne Konto sieht, gleich
  in wessen Anfrage es entsteht.
- Eine Suche nur nach Produkten (`/?s=…&post_type=product`, das Suchfeld von WooCommerce) führt auf
  dieselbe Ergebnisseite.
- Solange WooCommerce den Shop als „Demnächst verfügbar“ ankündigt, findet die Suche für Besucher
  nichts; wer den Shop verwaltet, sucht normal.

## Druckanbieter

Unter **Produkte → Druckanbieter** stehen die Produktfamilien von elf Lieferanten mit Links
zum jeweiligen Anbieter und, soweit verfügbar, zur Schnittstelle. „Entwürfe anlegen“ legt
je Familie einen ausgeblendeten WooCommerce-Entwurf an. Erneutes Anlegen überspringt bereits
vorhandene Produkte. Lieferantenartikelnummern, Preise und Varianten werden beim Import nicht
gesetzt. Der Import nutzt die im Plugin hinterlegte Übersicht der öffentlichen Sortimente.
Ein Live-Abgleich und die Auftragsübergabe brauchen eine separate Anbindung.

Die Übersicht ist für die Verwaltung gedacht. Auf den Seiten des Shops steht sie nicht: Kunden
sollen nicht erfahren, bei welchen Druckereien eingekauft wird (`tests/e2e/home.spec.js` hält
das für die Startseite fest).

## Gestaltung

- Der Look folgt [mobbin.com](https://mobbin.com) (Stand 24.11.2026): weiße Seiten, zentrierte
  große Überschriften, eine einzige serifenlose Schrift, hellgraue Flächen mit großen Rundungen,
  schwarze und graue Pill-Buttons, schwarzer Fuß – ohne Rahmen und ohne Schatten an der
  Oberfläche. Größen, Stärken und Farben sind dort gemessen. Abweichungen: Instrument Sans statt
  der kostenpflichtigen Schrift des Vorbilds, und ein etwas dunkleres Grau für Nebentexte, damit
  der Kontrast auch auf grauen Flächen reicht.
- Farben, Schrift, Radien und Typo-Stile stehen als Tokens in
  `wp-content/themes/stiqon/assets/src/css/app.css`. Schriften sind gebündelt; weder der Build noch
  der Browser fragt ein Schrift-CDN.
- Eigene Typo-Klassen (`text-display`, `text-title`, `text-action` …) dürfen nicht wie eine Farbe
  heißen: `text-control` wäre zugleich die Textfarbe des Tokens `control`.
- Die Texte der Oberfläche stehen auf Deutsch direkt in den Vorlagen. Für eine zweite Sprache
  müssten sie durch Übersetzungsfunktionen ersetzt werden.
- Verhalten im Browser liegt in `assets/src/js` als benannte Alpine-Komponenten; Vorlagen
  verweisen nur auf deren Eigenschaften und Methoden. `app.js` lädt jede Seite; was nur eine
  Seitenart braucht (Galerie, Produktseite) oder erst auf Wunsch (Suche), liegt in eigenen Dateien
  und wird nur dort geholt. Das Menü für kleine Bildschirme kommt ohne Skript aus.
- Die Hilfsklasse für den Seitenrand heißt `wrap`, nicht `page`: WordPress setzt die Klasse `page`
  auf den `body` jeder Seite.
- Vorlagen, die ganz aus eigenem Markup bestehen (Startseite, Bereiche, 404, Musterbogen), rufen
  vor dem Kopf `stiqon_declare_lean_page()` auf. Nur solche Seiten lassen die Stylesheets für
  Editor-Inhalte und die Skripte von WooCommerce weg. Die Entscheidung hängt bewusst an der Vorlage
  und nicht an der Adresse: WooCommerce zeigt im Modus „Demnächst verfügbar“ unter denselben
  Adressen seine Ankündigung. Die Seiten, die WooCommerce zeichnet, erklären sich mit
  `stiqon_declare_shop_page()` (siehe „Kaufen“).
- Links in Karten und Kacheln umschließen nur den Titel und sind über die ganze Fläche gespannt.
  Um die Zeichnung herumgelegt, würde die Beschriftung des Beispielmotivs zum Linktext.
- Die Logo-Dateien unter `wp-content/themes/stiqon/assets/brand` entstehen aus
  `tools/brand/logo-original.webp` mit `python3 tools/brand/make-logo-assets.py` (nur nach einem
  Logo-Wechsel nötig). Darunter ist eine Fassung für E-Mails: als PNG und auf Weiß, weil nicht
  jedes Mailprogramm WebP zeigt und ein freigestelltes schwarzes Logo auf dunklem Grund
  verschwindet.
