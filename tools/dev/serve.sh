#!/usr/bin/env bash
# Serves a local site of this project with PHP's built-in web server.
#
#   tools/dev/serve.sh                 the "dev" site
#   STIQON_SITE=e2e tools/dev/serve.sh
#
# Several worker processes are needed: WooCommerce pages request many files at
# once, and a single process stalls on them.
set -euo pipefail

. "$(dirname "$0")/lib.sh"

[ -f "$SITE_ENV" ] || fail "Site \"$SITE_NAME\" does not exist yet. Run tools/dev/setup-wordpress.sh first."

# shellcheck disable=SC1090
. "$SITE_ENV"

cd "$SITE_DIR"

export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-6}"

exec "${RUN_AS[@]}" php "$WP_CLI" --path="$SITE_DIR" server --host=127.0.0.1 --port="$SITE_PORT" --docroot="$SITE_DIR"
