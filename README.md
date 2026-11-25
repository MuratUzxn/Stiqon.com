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

WordPress selbst, WooCommerce und Germanized gehören nicht ins Repository. Welche Versionen
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
Installation auf: WordPress, WooCommerce und Germanized in den festgelegten Versionen, auf Deutsch,
mit Theme und Plugin aus diesem Repository als Verknüpfung. Der Shop läuft danach unter
`http://127.0.0.1:8200`. Benutzer und Passwort der lokalen Verwaltung stehen in
`sites/dev.env` neben der Installation – nie im Repository.

Gut zu wissen:

- WordPress, WooCommerce, Germanized und WP-CLI kommen von festen Adressen und werden gegen die
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

## Seiten

Das Theme bringt Vorlagen für Seiten mit festen Adressen mit. Das Einrichtungsskript legt die
Seiten an; auf einer echten Installation legt man sie in WordPress unter „Seiten“ mit genau dieser
Adresse an.

| Adresse | Vorlage | Hinweis |
|---|---|---|
| `/` | `front-page.php` | Startseite |
| `/sticker/`, `/flyer/` | `page-sticker.php`, `page-flyer.php` | Bereiche; die Produktgalerie folgt |
| `/musterbogen/` | `page-musterbogen.php` | Logo, Schrift, Farben und Mockups auf einer Seite. Nur wer das Erscheinungsbild der Website verwalten darf, sieht sie; alle anderen bekommen „nicht gefunden“ – auch wenn die Seite versehentlich veröffentlicht wurde |

Fehlt eine der beiden Bereichsseiten oder fehlen dem Theme seine gebauten Dateien, weist die
Verwaltung darauf hin (`inc/checks.php`).

Die Sticker- und Flyer-Ansichten zeichnet das Plugin (`src/Mockup`, Vorlagen unter `templates`).
Ist es abgeschaltet, erscheinen die Seiten ohne Ansichten, und die Verwaltung weist darauf hin.
Theme und Plugin lassen sich in beliebiger Reihenfolge aktivieren. Kennt das Plugin eine verlangte
Form, ein Motiv oder eine Kartenfarbe nicht, bleibt in der Entwicklung die Seite stehen, damit der
Fehler auffällt; im Live-Betrieb fehlt nur diese eine Ansicht, und der Grund steht im
Fehlerprotokoll.

Beim ersten Aktivieren setzt das Plugin Datenschutz-Voreinstellungen (`src/Setup/Defaults.php`):
keine Profilbilder von Gravatar, neue Inhalte ohne Kommentare, keine Herkunftsmessung von
Bestellungen per Cookie, keine Nutzungsdaten an WooCommerce. Jede davon wird genau einmal gesetzt;
was die Inhaberin oder der Inhaber später ändert, bleibt auch nach erneutem Aktivieren bestehen.

## Druckanbieter

Unter **Produkte → Druckanbieter** stehen die Produktfamilien von elf Lieferanten mit Links
zum jeweiligen Anbieter und, soweit verfügbar, zur Schnittstelle. „Entwürfe anlegen“ legt
je Familie einen ausgeblendeten WooCommerce-Entwurf an. Erneutes Anlegen überspringt bereits
vorhandene Produkte. Lieferantenartikelnummern, Preise und Varianten werden beim Import nicht
gesetzt. Der Import nutzt die im Plugin hinterlegte Übersicht der öffentlichen Sortimente.
Ein Live-Abgleich und die Auftragsübergabe brauchen eine separate Anbindung.

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
- Verhalten im Browser liegt in `assets/src/js/app.js` als benannte Alpine-Komponenten; Vorlagen
  verweisen nur auf deren Eigenschaften und Methoden. Das Menü für kleine Bildschirme kommt ohne
  Skript aus.
- Die Hilfsklasse für den Seitenrand heißt `wrap`, nicht `page`: WordPress setzt die Klasse `page`
  auf den `body` jeder Seite.
- Vorlagen, die ganz aus eigenem Markup bestehen (Startseite, Bereiche, 404, Musterbogen), rufen
  vor dem Kopf `stiqon_declare_lean_page()` auf. Nur solche Seiten lassen die Stylesheets für
  Editor-Inhalte und die Skripte von WooCommerce weg. Die Entscheidung hängt bewusst an der Vorlage
  und nicht an der Adresse: WooCommerce zeigt im Modus „Demnächst verfügbar“ unter denselben
  Adressen eine Seite aus Blöcken.
- Links in Karten und Kacheln umschließen nur den Titel und sind über die ganze Fläche gespannt.
  Um die Zeichnung herumgelegt, würde die Beschriftung des Beispielmotivs zum Linktext.
- Die Logo-Dateien unter `wp-content/themes/stiqon/assets/brand` entstehen aus
  `tools/brand/logo-original.webp` mit `python3 tools/brand/make-logo-assets.py` (nur nach einem
  Logo-Wechsel nötig).
