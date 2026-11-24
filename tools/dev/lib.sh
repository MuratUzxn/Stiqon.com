#!/usr/bin/env bash
# Shared settings and helpers for the local WordPress tooling. Sourced, not run.
#
# Everything WordPress lives outside the repository, under STIQON_WP_ROOT:
#
#   wp-cli.phar               the command line tool, checksum-verified on download
#   downloads/                plugin and language packages
#   sites/<name>/             one WordPress installation per site ("dev", "e2e", ...)
#   sites/<name>.env          address, database name and generated passwords of that site
#
# The theme and the plugin of this repository are linked into each site.

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

# shellcheck source=versions.env
. "$REPO/tools/dev/versions.env"

if [ -z "${STIQON_WP_ROOT:-}" ]; then
    if [ "$(id -u)" = "0" ]; then
        STIQON_WP_ROOT=/srv/stiqon-wp
    else
        STIQON_WP_ROOT="$HOME/.stiqon-wp"
    fi
fi

SITE_NAME="${STIQON_SITE:-dev}"
SITE_DIR="$STIQON_WP_ROOT/sites/$SITE_NAME"
SITE_ENV="$STIQON_WP_ROOT/sites/$SITE_NAME.env"
DOWNLOADS="$STIQON_WP_ROOT/downloads"
WP_CLI="$STIQON_WP_ROOT/wp-cli.phar"

DB_HOST="${STIQON_DB_HOST:-127.0.0.1}"
DB_ADMIN_USER="${STIQON_DB_ADMIN_USER:-root}"

# WP-CLI refuses to run as root for a good reason: plugin code would run as root.
# Where we are root (containers), an unprivileged user owns and runs WordPress.
RUN_AS=()
if [ "$(id -u)" = "0" ]; then
    WP_USER="${STIQON_WP_USER:-wpdev}"
    RUN_AS=(runuser -u "$WP_USER" --)
fi

as_site_user() {
    "${RUN_AS[@]}" "$@"
}

wp() {
    as_site_user php "$WP_CLI" --path="$SITE_DIR" "$@"
}

# The database server's administrator: with a password over TCP (CI service), or
# through the local socket of the machine's own server (no password as root).
db_admin() {
    local client
    client="$(command -v mariadb || command -v mysql)"

    if [ -n "${STIQON_DB_ADMIN_PASSWORD:-}" ]; then
        MYSQL_PWD="$STIQON_DB_ADMIN_PASSWORD" "$client" --protocol=tcp -h "$DB_HOST" -u "$DB_ADMIN_USER" "$@"
    else
        "$client" -u "$DB_ADMIN_USER" "$@"
    fi
}

say() {
    printf '%s\n' "$*"
}

fail() {
    printf 'Error: %s\n' "$*" >&2
    exit 1
}
