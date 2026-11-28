# Stiqon.com

Onlineshop für Werbemittel (Sticker und Flyer) auf WordPress und WooCommerce – im Aufbau.

Dieses Repository enthält, was den Shop von einer Standard-Installation unterscheidet:

| Verzeichnis | Inhalt |
|---|---|
| `wp-content/themes/stiqon` | das Theme: Aussehen aller Seiten, Vorlagen für die Shop-Seiten |
| `wp-content/plugins/stiqon-shop` | das Plugin „Stiqon Shop-Funktionen“: Sticker- und Flyer-Ansichten, Motiv-Vorschau und weitere Funktionen |
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
- `tools/dev/example-designs.php` legt Beispieldesigns an (siehe „Designs“): sechs gezeigte, dazu
  eines ohne Freigabe und eines ohne Vorschaubild. Die Bilder zeichnet das Skript mit PHPs GD aus
  Formen; jeder Lauf macht dieselben Dateien.
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
| `/sticker/`, `/flyer/` | `page-sticker.php`, `page-flyer.php` | Bereiche mit Galerie: je Produkt und Material bzw. Papier eine Karte. Oben wechselt ein Umschalter „Sticker \| Flyer“ zwischen den Bereichen. Die Filter sind Links und funktionieren ohne Skript; am Desktop öffnet eine Karte groß über der Galerie |
| `/produkt/…` | `woocommerce/…` | Produktseite auf den Vorlagen von WooCommerce, damit sich Germanized und weitere Plugins einhängen können |
| `/?s=…` | `search.php` | Ergebnisseite der Suche |
| `/warenkorb/`, `/kasse/`, `/mein-konto/` | `page.php` | Die Seiten, die WooCommerce zeichnet – siehe „Kaufen“ |
| `/agb/`, `/impressum/` … | `page.php` | Geschriebene Seiten aus dem Editor. Welche davon AGB, Widerruf, Versand, Datenschutz und Impressum sind, steht in den Einstellungen von WooCommerce und Germanized; der Fuß verlinkt sie von dort |
| `/design/` | `archive-stiqon_design.php` | Die Designs (Inhaltstyp des Plugins, in der Verwaltung unter „Designs“): Übersicht nach Werbemittel und Anlass, Umschalter und Reiter als Filter, 24 Karten je Seite. Gefiltert wird über `?werbemittel=…` und `?anlass=…` – Links, die ohne Skript funktionieren. Ohne gezeigtes Design steht dort „Die ersten Designs entstehen gerade.“ |
| `/design/<name>/` | `single-stiqon_design.php` | Ein Design groß, Werbemittel und Anlass als Links zur gefilterten Liste, „Als Motiv ausprobieren“ und der Weg zum Bereich, darunter weitere Designs für dasselbe Werbemittel |
| `/referenzen/` | `archive-stiqon_referenz.php` | Referenzen: gelieferte Arbeiten mit Foto, Name und ein, zwei Sätzen (Inhaltstyp des Plugins, in der Verwaltung unter „Referenzen“). Die Seite und alle Links darauf gibt es erst, wenn eine Referenz veröffentlicht ist und ein Foto hat |
| `/musterbogen/` | `page-musterbogen.php` | Logo, Schrift, Farben und Mockups auf einer Seite. Nur wer das Erscheinungsbild der Website verwalten darf, sieht sie; alle anderen bekommen „nicht gefunden“ – auch wenn die Seite versehentlich veröffentlicht wurde |

Der Kopf jeder Seite nennt die zwei Register der Website, wie mobbin.com „Apps“ und „Sites“:
**Design** (`/design/`) und **Produkte** (`/`, die Startseite ist der Anfang des Sortiments).
Das Register der Seite steht in Schwarz, das andere in Grau, ohne Unterstrich
(`stiqon_registers()` in `inc/site.php`). Zu „Design“ gehören die Liste und die Seiten der
Designs; zu „Produkte“ Startseite, Bereiche, Produkte, Suche, Warenkorb, Kasse und Kundenkonto.
Rechtstexte, Referenzen, „nicht gefunden“ und die Ankündigung gehören zu keinem. Die Startseite
eines Registers trägt `aria-current="page"`, jede andere Seite darin `aria-current="true"`.
Bereiche und Referenzen stehen nicht mehr im Kopf, sondern im Fuß, im Menü für kleine Bildschirme
(unter den Registern) und – die Bereiche – im Umschalter oben auf jeder Bereichsseite. Unter
768 px passen die Register nicht mit 44 px hohen Flächen neben Suche, Warenkorb und Menü; dort
stehen sie oben im Menü. Ohne das Plugin gibt es keine Designs, und der Kopf nennt nur „Produkte“.

Fehlt eine der beiden Bereichsseiten oder fehlen dem Theme seine gebauten Dateien, weist die
Verwaltung darauf hin (`inc/checks.php`).

Die Sticker- und Flyer-Ansichten zeichnet das Plugin (`src/Mockup`, Vorlagen unter `templates`).
Ob ein Produkt ein Sticker oder ein Flyer ist, welche Form oder welches Format es hat und wie seine
Materialien aussehen, liest das Plugin aus Namen und Kategorie (`src/Catalog`). In der Verwaltung
zeigt der Reiter „Vorschau“ am Produkt, was dabei herauskommt, und lässt Art, Form bzw. Format und
Beispielmotiv festlegen (`src/Admin/ProductPreview.php`).
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

## Designs

Designs sind Vorlagen, die Besucher auf den Produkten ausprobieren: ein Bild, ein Werbemittel,
für das es gemacht ist, und ein Anlass. In der Verwaltung stehen sie unter **Designs**
(`src/Content/Designs.php` im Plugin).

- **Daten:** Inhaltstyp `stiqon_design` mit Titel, Beschreibung, Vorschaubild und Textauszug.
  Zwei Listen von Begriffen: **Werbemittel** (`stiqon_design_medium`) – die Kürzel sind die der
  Bereichsseiten, `sticker` und `flyer`; daran hängen „Zu den Stickern“ bzw. „Zu den Flyern“ und
  das Ziel von „Als Motiv ausprobieren“ – und **Anlass** (`stiqon_design_occasion`), frei, etwa
  „Eröffnung“. Begriffe haben keine eigenen Seiten; gefiltert wird die Liste.
- **Wann ein Design erscheint:** Es ist veröffentlicht, hat ein Vorschaubild, und genau dieses Bild
  ist freigegeben. Die Freigabe gilt dem geprüften Bild (gespeichert wird dessen Nummer): Ein neues
  oder ausgetauschtes Vorschaubild nimmt das Design aus dem Shop, bis jemand es geprüft und erneut
  freigegeben hat. Fehlt eines davon, steht es in keiner Liste, zählt in keinem Filter, und seine
  Adresse antwortet mit „nicht gefunden“ – auch als Einbettung, über `?p=<Nummer>`, in Feeds und
  über WordPress' Schnittstelle: die Designs selbst (`/wp-json/wp/v2/stiqon_design`), deren Suche,
  die Mediathek mit seinem Bild und Werbemittel und Anlässe, die kein gezeigtes Design trägt.
  Die Regel steht an einer Stelle (`Designs::shown()`, Unit-Tests in `tests/php/Content`); die
  Spalte „Freigabe“ der Verwaltung sagt je Design, ob es erscheint, und wenn nicht, warum. In der
  Sitemap von WordPress stehen Designs vorerst nicht.
- **Herkunft und Freigabe** (Kasten unter dem Text, nur in der Verwaltung): Higgsfield-
  Auftragsnummer; Vorbilder – je Zeile eine Adresse und ihre Klasse, A für eine Anregung in Stil
  oder Aufbau, B für ein enges Vorbild; Prüfnotiz; und das Häkchen „Freigegeben: Ähnlichkeits-
  und Markenprüfung erledigt“.
- **„Als Motiv ausprobieren“** holt das Vorschaubild von der Adresse des Shops und übergibt es dem
  Motiv des Besuchers, genau wie eine Datei, die er mit „Eigenes Motiv“ wählt – nichts wird
  hochgeladen (`assets/src/js/design.js`). Danach geht es zum Bereich des Werbemittels, wo alle
  Ansichten das Design zeigen. Behält der Browser das Motiv nicht (privates Fenster), bleibt die
  Seite stehen und sagt das.

So wird ein Design, das mit Higgsfield entstanden ist, zum Eintrag:

1. Unter **Designs → Hinzufügen** einen Titel und ein, zwei Sätze Beschreibung eingeben.
2. Das Bild als **Vorschaubild** hochladen: PNG, JPEG oder WebP; für Sticker am besten mit
   durchsichtigem Hintergrund, für Flyer im Format des Blatts.
3. **Werbemittel** (`sticker` oder `flyer`) und **Anlass** setzen.
4. Unter **Herkunft und Freigabe** die Auftragsnummer und die Vorbilder eintragen.
5. Veröffentlichen. Das Design erscheint noch nicht.
6. Prüfen, ob es einem Vorbild oder einer Marke zu nahe kommt, das Ergebnis als Prüfnotiz
   festhalten und erst dann **Freigegeben** anhaken und speichern.
7. Wird später das Vorschaubild ausgetauscht, verschwindet das Design wieder („neues
   Vorschaubild, nicht freigegeben“ in der Spalte „Freigabe“): das neue Bild prüfen und erneut
   freigeben.

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
  Seitenart braucht (Galerie, Produktseite, Seite eines Designs) oder erst auf Wunsch (Suche), liegt in eigenen Dateien
  und wird nur dort geholt. Das Menü für kleine Bildschirme kommt ohne Skript aus.
- Die Hilfsklasse für den Seitenrand heißt `wrap`, nicht `page`: WordPress setzt die Klasse `page`
  auf den `body` jeder Seite.
- Vorlagen, die ganz aus eigenem Markup bestehen (Startseite, Bereiche, Designs, 404, Musterbogen), rufen
  vor dem Kopf `stiqon_declare_lean_page()` auf. Nur solche Seiten lassen die Stylesheets für
  Editor-Inhalte und die Skripte von WooCommerce weg. Die Entscheidung hängt bewusst an der Vorlage
  und nicht an der Adresse: WooCommerce zeigt im Modus „Demnächst verfügbar“ unter denselben
  Adressen seine Ankündigung. Die Seiten, die WooCommerce zeichnet, erklären sich mit
  `stiqon_declare_shop_page()` (siehe „Kaufen“).
- Umschalter (`stiqon_segments()`: graue Spur, die gewählte Fläche weiß) und Reiter
  (`stiqon_tabs()`: die gewählte in Schwarz und unterstrichen) folgen dem Vorbild. Beide sind
  Links mit `aria-current="page"`, ihr Aussehen hängt an diesem Attribut.
- Links in Karten und Kacheln umschließen nur den Titel und sind über die ganze Fläche gespannt.
  Um die Zeichnung herumgelegt, würde die Beschriftung des Beispielmotivs zum Linktext.
- Die Logo-Dateien unter `wp-content/themes/stiqon/assets/brand` entstehen aus
  `tools/brand/logo-original.webp` mit `python3 tools/brand/make-logo-assets.py` (nur nach einem
  Logo-Wechsel nötig). Darunter ist eine Fassung für E-Mails: als PNG und auf Weiß, weil nicht
  jedes Mailprogramm WebP zeigt und ein freigestelltes schwarzes Logo auf dunklem Grund
  verschwindet.
