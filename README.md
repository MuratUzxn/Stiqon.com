# Stiqon.com

Onlineshop für Werbemittel (Sticker und Flyer) – im Aufbau.

Der Shop ist eine Laravel-Anwendung (PHP ≥ 8.3) und läuft auf klassischem Webspace mit
MariaDB/MySQL ebenso wie auf einem eigenen Server. Node.js wird nur zum Bauen der Oberfläche
gebraucht, nicht im Betrieb.

| Bereich | Technik |
|---|---|
| Shop-Oberfläche | Blade, Livewire 4, Alpine.js, Tailwind CSS 4 |
| Verwaltung (`/admin`) | Filament 5, eigene Admin-Konten mit Pflicht-2FA |
| Kundenkonten | Laravel Fortify |
| Zahlung | Stripe Checkout und Vorkasse |
| Datenbank | MariaDB/MySQL im Betrieb, SQLite für Entwicklung und Tests |

## Entwicklung

Voraussetzungen: PHP ≥ 8.3 (intl, gd, zip, pdo_sqlite), Composer, Node.js 22.

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm run build
```

Lokaler Server (der eingebaute PHP-Server wird direkt gestartet):

```bash
cd public && php -S 127.0.0.1:8123 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

## Tests

```bash
php artisan test
```

```bash
npm run e2e
```

Die End-to-End-Tests (Playwright) starten den Server selbst mit einer frischen SQLite-Datenbank.
Jeder Test schlägt fehl, wenn eine Seite Konsolenfehler meldet oder Anfragen an fremde Server
stellt – der Shop kommt ohne Schrift-CDN, Tracker und externe Skripte aus. Dazu gehören eine
Barrierefreiheitsprüfung (axe, WCAG 2.2 AA) und ein Gewichtsbudget für den ersten Seitenaufruf auf
dem Handy (`tests/e2e/budget.spec.js`).

## Sicherheits-Header

`App\Http\Middleware\SecurityHeaders` setzt die Header für jede Antwort.

- **Shop:** strenge Content Security Policy. Skripte laufen nur von der eigenen Adresse und nur
  mit der Nonce der jeweiligen Anfrage; `unsafe-eval` ist nicht erlaubt. Dafür nutzt der Shop die
  CSP-sichere Fassung von Livewire und Alpine. Folge für die Entwicklung: Alpine-Ausdrücke in
  Shop-Ansichten bleiben einfach (Eigenschaft oder Methode einer in `resources/js/app.js`
  registrierten Komponente) – keine Inline-Logik in `x-`-Attributen.
- **Verwaltung:** Filament braucht die normale Alpine-Fassung und Inline-Skripte. `/admin` erhält
  deshalb nur die Regeln, die Skripte nicht betreffen.

Beide Livewire-Fassungen werden von `composer install` nach `public/vendor/livewire` kopiert
(Skript `post-autoload-dump`). Fehlen die Dateien, fällt der Shop auf die normale Fassung zurück
und erlaubt `unsafe-eval` – die Seite funktioniert dann weiter, die Richtlinie ist aber schwächer.
Der End-to-End-Test „security headers“ stellt sicher, dass das im geprüften Stand nicht der Fall ist.

## Gestaltung

- Farben, Schrift, Radien und Typo-Stile stehen als Tokens in `resources/css/app.css`.
- Sticker und Flyer zeichnet ein eigener Renderer als Inline-SVG
  (`app/View/Components/Mockup`, Beispielmotive in `resources/views/components/motif`). Ein vom
  Besucher gewähltes Bild ersetzt das Beispielmotiv auf allen Karten; es bleibt im Browser.
- `/musterbogen` zeigt Logo-Fassungen, Schriftvarianten, Farben, Bedienelemente und Mockups auf
  einer Seite. Die Seite existiert nur in der Entwicklung, nicht im Betrieb.
- Die Logo-Dateien unter `public/img/brand` entstehen aus `resources/brand/logo-original.webp`
  mit `python3 bin/brand/make-logo-assets.py` (nur nach einem Logo-Wechsel nötig).

## Aufbau

- `app/` – Anwendungscode; die Verwaltung liegt unter `app/Filament`
- `resources/views` – Blade-Ansichten des Shops, `resources/css` und `resources/js` – Oberfläche
- `lang/de` – deutsche Meldungen des Frameworks
- `database/` – Migrationen, Seeder, Factories
- `tests/Feature`, `tests/Unit` – PHPUnit; `tests/e2e` – Playwright
- `.github/workflows` – CI

Installations- und Go-Live-Anleitung folgen mit dem ersten Auslieferungspaket.
