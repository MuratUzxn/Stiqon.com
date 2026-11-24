#!/usr/bin/env bash
# Starts the application for end-to-end tests on 127.0.0.1:8123 (or E2E_PORT)
# with a fresh SQLite database. Used as Playwright's webServer command.
#
# The PHP built-in server is started directly instead of `artisan serve`:
# artisan does not pass ini settings on to the server process it spawns.
set -euo pipefail

cd "$(dirname "$0")/../.."

PORT="${E2E_PORT:-8123}"

export APP_ENV=local
export APP_DEBUG=true
export APP_URL="http://127.0.0.1:$PORT"
export DB_CONNECTION=sqlite
export DB_DATABASE="$PWD/database/e2e.sqlite"
export SESSION_DRIVER=file
export CACHE_STORE=file
export QUEUE_CONNECTION=deferred
export MAIL_MAILER=log

rm -f "$DB_DATABASE"
touch "$DB_DATABASE"
php artisan migrate --force --seed --no-interaction >/dev/null

cd public
exec env PHP_CLI_SERVER_WORKERS=4 php -S "127.0.0.1:$PORT" \
    ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
