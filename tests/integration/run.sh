#!/usr/bin/env bash
# Runs the checks in this directory inside a local site (default "dev") and then
# looks for PHP warnings and notices that this project's code left in the log.
#
#   tests/integration/run.sh
#   STIQON_SITE=e2e tests/integration/run.sh     after the browser tests
set -euo pipefail

. "$(dirname "$0")/../../tools/dev/lib.sh"

[ -f "$SITE_DIR/wp-config.php" ] || fail "Site \"$SITE_NAME\" does not exist yet. Run tools/dev/setup-wordpress.sh first."

for check in "$REPO"/tests/integration/*.php; do
    wp eval-file "$check" "$WOOCOMMERCE_VERSION" "$GERMANIZED_VERSION"
done

log="$SITE_DIR/wp-content/debug.log"

if [ -f "$log" ] && grep -E 'themes/stiqon|plugins/stiqon-shop' "$log"; then
    fail "The lines above from wp-content/debug.log come from this project's code."
fi

say "No warnings or notices from this project's code in the log."
