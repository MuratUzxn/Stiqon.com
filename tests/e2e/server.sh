#!/usr/bin/env bash
# Builds a fresh WordPress site for the browser tests and serves it. Used as
# Playwright's webServer command, so every test run starts from a known state.
set -euo pipefail

cd "$(dirname "$0")/../.."

export STIQON_SITE=e2e

. tools/dev/lib.sh

bash tools/dev/setup-wordpress.sh --fresh --port "${E2E_PORT:-8123}"

# The web server logs every request; that goes to a file next to the site.
exec bash tools/dev/serve.sh > "$STIQON_WP_ROOT/sites/$SITE_NAME.server.log" 2>&1
