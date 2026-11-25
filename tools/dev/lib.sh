#!/usr/bin/env bash
# Shared settings and helpers for the local WordPress tooling. Sourced, not run.
#
# Everything WordPress lives outside the repository, under STIQON_WP_ROOT:
#
#   tools/wp-cli.phar         the command line tool, in a pinned version
#   downloads/                WordPress, plugin and language packages
#   sites/<name>/             one WordPress installation per site ("dev", "e2e", ...)
#   sites/<name>.env          address, database name and generated passwords of that site
#
# The theme and the plugin of this repository are linked into each site.
#
# Who owns what, when the scripts run as root (containers): WordPress, and with it
# the code of every plugin, runs as an unprivileged user. That user owns the site
# directories and nothing else. The settings file, the downloads and the
# directories around them belong to root – the scripts read and run them as root,
# so nothing a plugin does may be able to change them.

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
# In a directory of its own, beside the sites: WordPress' installer takes the
# location of the running script for the web root when that is a directory above
# the site, and would work "/sites/<name>" into the site's address.
WP_CLI="$STIQON_WP_ROOT/tools/wp-cli.phar"

DB_HOST="${STIQON_DB_HOST:-127.0.0.1}"
DB_ADMIN_USER="${STIQON_DB_ADMIN_USER:-root}"

# WP-CLI refuses to run as root for a good reason: plugin code would run as root.
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
# through the local socket of the machine's own server (no password as root). A
# server named explicitly is used in either case.
db_admin() {
    local client
    client="$(command -v mariadb || command -v mysql)"

    if [ -n "${STIQON_DB_ADMIN_PASSWORD:-}" ]; then
        MYSQL_PWD="$STIQON_DB_ADMIN_PASSWORD" "$client" --protocol=tcp -h "$DB_HOST" -u "$DB_ADMIN_USER" "$@"
    elif [ -n "${STIQON_DB_HOST:-}" ]; then
        "$client" --protocol=tcp -h "$DB_HOST" -u "$DB_ADMIN_USER" "$@"
    else
        "$client" -u "$DB_ADMIN_USER" "$@"
    fi
}

# Reads a site's settings into the variables of the same names. The file is data
# and is never sourced as a script: only the seven lines setup-wordpress.sh
# writes are accepted, each with the kind of value that script produces.
read_site_env() {
    local line key value pattern

    [ -f "$SITE_ENV" ] && [ ! -L "$SITE_ENV" ] || fail "Site \"$SITE_NAME\" has no settings file. Run tools/dev/setup-wordpress.sh first."

    while IFS= read -r line || [ -n "$line" ]; do
        [ -n "$line" ] || continue

        key="${line%%=*}"
        value="${line#*=}"

        case "$key" in
            SITE_URL) pattern='^http://127\.0\.0\.1:[0-9]{2,5}$' ;;
            SITE_PORT) pattern='^[0-9]{2,5}$' ;;
            DB_NAME | DB_USER) pattern='^stiqon_[a-z0-9]+$' ;;
            DB_PASSWORD | ADMIN_PASSWORD) pattern='^[A-Za-z0-9]{16,64}$' ;;
            ADMIN_USER) pattern='^[a-z0-9_]{1,32}$' ;;
            *) fail "$SITE_ENV holds a line that setup-wordpress.sh does not write. Rebuild the site with --fresh." ;;
        esac

        [[ "$value" =~ $pattern ]] || fail "The value of $key in $SITE_ENV is not one setup-wordpress.sh writes."

        printf -v "$key" '%s' "$value"
    done < "$SITE_ENV"

    for key in SITE_URL SITE_PORT DB_NAME DB_USER DB_PASSWORD ADMIN_USER ADMIN_PASSWORD; do
        [ -n "${!key:-}" ] || fail "$SITE_ENV is missing $key."
    done
}

say() {
    printf '%s\n' "$*"
}

fail() {
    printf 'Error: %s\n' "$*" >&2
    exit 1
}
