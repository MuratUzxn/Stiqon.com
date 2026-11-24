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
Composer, `curl` und `unzip`.

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

- Heruntergeladene Pakete werden gegen die Prüfsummen in `tools/dev/versions.env` geprüft, WP-CLI
  gegen seine veröffentlichte Prüfsumme.
- WP-CLI läuft nie als root. Wo das Skript als root gestartet wird (Container), legt es dafür den
  Benutzer `wpdev` an.
- Die lokale Installation ruft nichts von außen ab (`WP_HTTP_BLOCK_EXTERNAL`) und aktualisiert
  sich nicht selbst.
- `tools/dev/wp.sh` führt WP-CLI in der lokalen Installation aus, zum Beispiel
  `tools/dev/wp.sh plugin list`.
- Nach Änderungen an Styles oder Skripten: `npm run build` (oder `npm run watch`).
- Die Datenbank liegt standardmäßig auf `127.0.0.1` mit dem Verwalter `root` über den lokalen
  Socket; anderes über `STIQON_DB_HOST`, `STIQON_DB_ADMIN_USER`, `STIQON_DB_ADMIN_PASSWORD`.

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
STIQON_SITE=e2e tests/integration/run.sh
```

- **Unit-Tests** (`tests/php`) prüfen die Logik des Plugins ohne WordPress.
- **Browser-Tests** (`tests/e2e`, Playwright) bauen für jeden Lauf eine frische Installation
  („e2e“, Port 8123) und prüfen sie am Desktop und am Handy. Jeder Test schlägt fehl, wenn eine
  Seite Konsolenfehler meldet oder Anfragen an fremde Server stellt.
- **Prüfungen in der Installation** (`tests/integration`) laufen über WP-CLI und sehen danach im
  Fehlerprotokoll nach, ob Theme oder Plugin Warnungen hinterlassen haben.

## Seiten

Das Theme bringt Vorlagen für Seiten mit festen Adressen mit. Das Einrichtungsskript legt die
Seiten an; auf einer echten Installation legt man sie in WordPress unter „Seiten“ mit genau dieser
Adresse an.

| Adresse | Vorlage | Hinweis |
|---|---|---|
| `/` | `front-page.php` | Startseite |
| `/sticker/`, `/flyer/` | `page-sticker.php`, `page-flyer.php` | Bereiche; die Produktgalerie folgt |
| `/musterbogen/` | `page-musterbogen.php` | Logo, Schrift, Farben und Mockups auf einer Seite. Die Seite ist „privat“: nur angemeldete Verwalter sehen sie |

Die Sticker- und Flyer-Ansichten zeichnet das Plugin (`src/Mockup`, Vorlagen unter `templates`).
Ist es abgeschaltet, erscheinen die Seiten ohne Ansichten, und die Verwaltung weist darauf hin.
Theme und Plugin lassen sich in beliebiger Reihenfolge aktivieren.

Beim Aktivieren setzt das Plugin Datenschutz-Voreinstellungen (`src/Setup/Defaults.php`): keine
Profilbilder von Gravatar, keine Kommentare, keine Herkunftsmessung von Bestellungen per Cookie,
keine Nutzungsdaten an WooCommerce.

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
- Die Logo-Dateien unter `wp-content/themes/stiqon/assets/brand` entstehen aus
  `tools/brand/logo-original.webp` mit `python3 tools/brand/make-logo-assets.py` (nur nach einem
  Logo-Wechsel nötig).
