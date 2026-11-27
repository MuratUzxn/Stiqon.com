#!/usr/bin/env bash
# Runs the checks in this directory inside a local site and then reads the site's
# error log.
#
#   tests/integration/run.sh                    the "e2e" site, after the browser tests
#   STIQON_SITE=dev tests/integration/run.sh
#
# The whole log is read, not just what this run adds: the "e2e" site is built anew
# for every run of the browser tests, so its log is exactly what those tests and
# these checks left behind. Lines known to come from elsewhere are listed, with
# their reason, in log-noise.txt; every other line fails the run.
set -euo pipefail

export STIQON_SITE="${STIQON_SITE:-e2e}"

. "$(dirname "$0")/../../tools/dev/lib.sh"

[ -f "$SITE_DIR/wp-config.php" ] || fail "Site \"$SITE_NAME\" does not exist yet. Run tools/dev/setup-wordpress.sh first."

# The installation as the setup script left it comes first: the later checks
# switch things off and on.
for check in environment templates catalogue supplier-catalogue buying search setup-problems defaults activation-order; do
    wp eval-file "$REPO/tests/integration/$check.php" "$WORDPRESS_VERSION" "$WOOCOMMERCE_VERSION" "$GERMANIZED_VERSION"
done

# The theme while a plugin it builds on is switched off – for this one command only.
wp --skip-plugins=stiqon-shop eval-file "$REPO/tests/integration/plugins-off.php" shop-plugin
# WooCommerce gone, the shop plugin still on – as WordPress leaves it after a failed update.
wp --skip-plugins=woocommerce,woocommerce-germanized eval-file "$REPO/tests/integration/plugins-off.php" woocommerce

log="$SITE_DIR/wp-content/debug.log"
noise="$(grep -v -E '^[[:space:]]*(#|$)' "$REPO/tests/integration/log-noise.txt")"

if [ -f "$log" ] && grep -v -E -f <(printf '%s\n' "$noise") "$log"; then
    fail "The lines above are in $log and are not known noise (tests/integration/log-noise.txt). If they are from earlier work on a long-lived site, empty the log and run again."
fi

say "Nothing unexpected in the error log."
