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
stellt – der Shop kommt ohne Schrift-CDN, Tracker und externe Skripte aus.

## Aufbau

- `app/` – Anwendungscode; die Verwaltung liegt unter `app/Filament`
- `resources/views` – Blade-Ansichten des Shops, `resources/css` und `resources/js` – Oberfläche
- `database/` – Migrationen, Seeder, Factories
- `tests/Feature`, `tests/Unit` – PHPUnit; `tests/e2e` – Playwright
- `.github/workflows` – CI

Installations- und Go-Live-Anleitung folgen mit dem ersten Auslieferungspaket.
