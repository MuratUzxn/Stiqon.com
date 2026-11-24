#!/usr/bin/env bash
# Runs WP-CLI for a local site of this project.
#
#   tools/dev/wp.sh plugin list
#   STIQON_SITE=e2e tools/dev/wp.sh option get home
set -euo pipefail

. "$(dirname "$0")/lib.sh"

[ -f "$SITE_DIR/wp-config.php" ] || fail "Site \"$SITE_NAME\" does not exist yet. Run tools/dev/setup-wordpress.sh first."

wp "$@"
