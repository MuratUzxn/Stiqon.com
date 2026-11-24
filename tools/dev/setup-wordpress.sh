#!/usr/bin/env bash
# Builds a local WordPress installation for this project: WordPress, WooCommerce
# and Germanized in pinned versions, in German, with the theme and the plugin of
# this repository linked in and the shop set up for Germany.
#
#   tools/dev/setup-wordpress.sh [--site dev] [--port 8200] [--fresh]
#
#   --site NAME   which installation to build (default "dev"); each has its own
#                 directory and database, so a preview and a test run can coexist
#   --port PORT   port the site is served on (default 8200)
#   --fresh       throw the site's files and database away first
#
# Running it again without --fresh only repairs what is missing.
#
# Environment:
#   STIQON_WP_ROOT             where WordPress lives (default /srv/stiqon-wp as root,
#                              otherwise ~/.stiqon-wp) – never inside the repository
#   STIQON_DB_HOST             database server (default 127.0.0.1)
#   STIQON_DB_ADMIN_USER       its administrator (default root)
#   STIQON_DB_ADMIN_PASSWORD   the administrator's password; empty means the local
#                              socket of this machine's own server
set -euo pipefail

SITE_ARG=""
PORT=8200
FRESH=0

while [ $# -gt 0 ]; do
    case "$1" in
        --site) SITE_ARG="$2"; shift 2 ;;
        --port) PORT="$2"; shift 2 ;;
        --fresh) FRESH=1; shift ;;
        *) echo "Unknown option: $1" >&2; exit 1 ;;
    esac
done

[ -n "$SITE_ARG" ] && export STIQON_SITE="$SITE_ARG"

. "$(dirname "$0")/lib.sh"

case "$SITE_NAME" in
    *[!a-z0-9]*|"") fail "The site name may only contain lowercase letters and digits." ;;
esac

URL="http://127.0.0.1:$PORT"
DB_NAME="stiqon_$SITE_NAME"
DB_USER="stiqon_$SITE_NAME"

for tool in php curl unzip sha256sum sha512sum; do
    command -v "$tool" >/dev/null || fail "Required tool not found: $tool"
done

command -v mariadb >/dev/null || command -v mysql >/dev/null || fail "No MariaDB/MySQL client found."

if ! db_admin -e 'SELECT 1' >/dev/null 2>&1; then
    # On a machine of our own (root, local server) the server may simply not be started yet.
    if [ "$(id -u)" = "0" ] && [ -z "${STIQON_DB_ADMIN_PASSWORD:-}" ] && command -v service >/dev/null; then
        say "Starting the local database server"
        service mariadb start >/dev/null 2>&1 || service mysql start >/dev/null 2>&1 || true
        sleep 3
    fi

    db_admin -e 'SELECT 1' >/dev/null 2>&1 || fail "Cannot reach the database server as $DB_ADMIN_USER. Is it running?"
fi

# --- Directories and the user that owns WordPress --------------------------------

if [ "$(id -u)" = "0" ]; then
    id "$WP_USER" >/dev/null 2>&1 || useradd --system --create-home --shell /usr/sbin/nologin "$WP_USER"
    mkdir -p "$STIQON_WP_ROOT"
    chown "$WP_USER": "$STIQON_WP_ROOT"
fi

as_site_user mkdir -p "$DOWNLOADS" "$STIQON_WP_ROOT/sites"

# Downloads run as the calling user (who has the machine's proxy and certificate
# settings) and are then handed to the user that owns WordPress.
download() { # url target
    curl -fsSL -o "$2.part" "$1" || { rm -f "$2.part"; fail "Download failed: $1"; }
    mv "$2.part" "$2"

    if [ "$(id -u)" = "0" ]; then
        chown "$WP_USER": "$2"
    fi
}

# --- WP-CLI, verified against its published checksum -----------------------------

if [ ! -f "$WP_CLI" ]; then
    say "Downloading WP-CLI"
    builds=https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar
    download "$builds/wp-cli.phar" "$WP_CLI.unverified"
    expected="$(curl -fsSL "$builds/wp-cli.phar.sha512" | tr -d '[:space:]')"
    actual="$(sha512sum "$WP_CLI.unverified" | cut -d' ' -f1)"
    [ "$expected" = "$actual" ] || { rm -f "$WP_CLI.unverified"; fail "WP-CLI does not match its published checksum."; }
    mv "$WP_CLI.unverified" "$WP_CLI"
fi

# --- Packages ---------------------------------------------------------------------
# WordPress' own downloader cannot be relied on here (it does not use an HTTP proxy
# and is switched off in the local configuration), so packages are fetched with curl
# and installed from the file.

fetch_plugin() { # slug version sha256
    local file="$DOWNLOADS/$1.$2.zip"

    if [ ! -f "$file" ]; then
        say "Downloading $1 $2"
        download "https://downloads.wordpress.org/plugin/$1.$2.zip" "$file"
    fi

    [ "$(sha256sum "$file" | cut -d' ' -f1)" = "$3" ] || fail "$file does not match the checksum in tools/dev/versions.env."
}

# Language packs are revised by their translators without a new version number,
# so they carry no pinned checksum.
install_language() { # type slug version locale target-directory
    local file="$DOWNLOADS/lang-$2-$3-$4.zip" path="translation/$1/$2/$3/$4.zip"

    if [ "$1" = "core" ]; then
        path="translation/core/$3/$4.zip"
    fi

    if [ ! -f "$file" ]; then
        download "https://downloads.wordpress.org/$path" "$file"
    fi

    as_site_user unzip -o -q "$file" -d "$5"
}

fetch_plugin woocommerce "$WOOCOMMERCE_VERSION" "$WOOCOMMERCE_SHA256"
fetch_plugin woocommerce-germanized "$GERMANIZED_VERSION" "$GERMANIZED_SHA256"

# --- Start over, if asked ---------------------------------------------------------

if [ "$FRESH" = "1" ]; then
    say "Removing site \"$SITE_NAME\""
    db_admin -e "DROP DATABASE IF EXISTS \`$DB_NAME\`"
    rm -rf "$SITE_DIR" "$SITE_ENV"
fi

# --- Passwords: generated once per site, kept next to it, never in the repository --

if [ ! -f "$SITE_ENV" ]; then
    random() { head -c 24 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | cut -c1-24; }
    as_site_user sh -c "umask 077 && cat > '$SITE_ENV'" <<EOF
SITE_URL=$URL
SITE_PORT=$PORT
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASSWORD=$(random)
ADMIN_USER=admin
ADMIN_PASSWORD=$(random)
EOF
fi

# Port and address follow the latest call.
as_site_user sed -i "s|^SITE_URL=.*|SITE_URL=$URL|; s|^SITE_PORT=.*|SITE_PORT=$PORT|" "$SITE_ENV"

# shellcheck disable=SC1090
. "$SITE_ENV"

# --- Database ---------------------------------------------------------------------

db_admin <<EOF
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
CREATE USER IF NOT EXISTS '$DB_USER'@'%' IDENTIFIED BY '$DB_PASSWORD';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
ALTER USER '$DB_USER'@'%' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'%';
EOF

# --- WordPress --------------------------------------------------------------------

if [ ! -f "$SITE_DIR/wp-load.php" ]; then
    say "Installing WordPress $WORDPRESS_VERSION"
    as_site_user mkdir -p "$SITE_DIR"
    wp core download --version="$WORDPRESS_VERSION" --locale=de_DE --quiet
fi

if [ ! -f "$SITE_DIR/wp-config.php" ]; then
    wp config create --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="$DB_PASSWORD" --dbhost="$DB_HOST" \
        --locale=de_DE --quiet --extra-php <<'PHP'
// A development and test installation: no calls to the outside, no self-updates,
// errors go to wp-content/debug.log where the test run reads them.
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
PHP
fi

# WordPress asks wordpress.org for updates whenever a plugin is installed or an admin
# page is opened. Those calls are blocked here and would fill the output and the log
# with warnings, so a local-only helper answers them with "nothing new".
as_site_user mkdir -p "$SITE_DIR/wp-content/mu-plugins"
as_site_user tee "$SITE_DIR/wp-content/mu-plugins/local-environment.php" >/dev/null <<'PHP'
<?php
/**
 * Local installation only (written by tools/dev/setup-wordpress.sh).
 */
add_filter('pre_http_request', static function ($response, $arguments, $url) {
    $host = (string) parse_url((string) $url, PHP_URL_HOST);

    if ($host === 'wordpress.org' || str_ends_with($host, '.wordpress.org')) {
        return ['headers' => [], 'body' => '', 'response' => ['code' => 200, 'message' => 'OK'], 'cookies' => [], 'filename' => null];
    }

    return $response;
}, 10, 3);
PHP

if ! wp core is-installed 2>/dev/null; then
    wp core install --url="$URL" --title="Stiqon" --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASSWORD" \
        --admin_email=admin@example.test --skip-email --quiet
fi

# The installer derives the address from the directory layout; state it explicitly.
wp option update siteurl "$URL" --quiet
wp option update home "$URL" --quiet

# Formal German ("Sie") for the shop.
as_site_user mkdir -p "$SITE_DIR/wp-content/languages/plugins"
install_language core core "$WORDPRESS_VERSION" de_DE_formal "$SITE_DIR/wp-content/languages"
wp site switch-language de_DE_formal --quiet

# --- Plugins ----------------------------------------------------------------------

install_plugin() { # slug version
    if [ "$(wp plugin get "$1" --field=version 2>/dev/null || true)" != "$2" ]; then
        say "Installing $1 $2"
        wp plugin install "$DOWNLOADS/$1.$2.zip" --force --quiet
    fi

    for locale in de_DE de_DE_formal; do
        install_language plugin "$1" "$2" "$locale" "$SITE_DIR/wp-content/languages/plugins"
    done

    wp plugin is-active "$1" || wp plugin activate "$1" --quiet
}

install_plugin woocommerce "$WOOCOMMERCE_VERSION"
install_plugin woocommerce-germanized "$GERMANIZED_VERSION"

# --- This repository's theme and plugin -------------------------------------------

as_site_user ln -sfn "$REPO/wp-content/themes/stiqon" "$SITE_DIR/wp-content/themes/stiqon"
as_site_user ln -sfn "$REPO/wp-content/plugins/stiqon-shop" "$SITE_DIR/wp-content/plugins/stiqon-shop"

[ -f "$REPO/wp-content/themes/stiqon/assets/build/manifest.json" ] || fail "The theme is not built yet. Run: npm ci && npm run build"

wp plugin is-active stiqon-shop || wp plugin activate stiqon-shop --quiet
wp theme activate stiqon --quiet

# --- Shop settings ----------------------------------------------------------------

wp option update woocommerce_default_country DE --quiet
wp option update woocommerce_currency EUR --quiet
wp option update woocommerce_calc_taxes yes --quiet
wp option update woocommerce_prices_include_tax yes --quiet
wp option update woocommerce_coming_soon no --quiet
wp rewrite structure '/%postname%/' --quiet
wp wc tool run install_pages --user="$ADMIN_USER" --quiet

# Not for search engines: this is a development installation.
wp option update blog_public 0 --quiet

# --- Pages the theme has templates for ----------------------------------------------

ensure_page() { # slug title status
    if [ -z "$(wp post list --post_type=page --name="$1" --post_status=any --field=ID)" ]; then
        wp post create --post_type=page --post_name="$1" --post_title="$2" --post_status="$3" --quiet
    fi
}

ensure_page sticker Sticker publish
ensure_page flyer Flyer publish
# The sample sheet is a working document: private, so only logged-in administrators see it.
ensure_page musterbogen Musterbogen private

wp rewrite flush --quiet

say ""
say "Site \"$SITE_NAME\" is ready: $URL"
say "  serve it:        STIQON_SITE=$SITE_NAME tools/dev/serve.sh"
say "  admin login:     $URL/wp-login.php  (user and password: $SITE_ENV)"
