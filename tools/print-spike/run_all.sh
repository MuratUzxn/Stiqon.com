#!/bin/sh
# re-run the whole spike (export + preflight); run from the spike directory
set -e
PY=./venv/bin/python
$PY -I make_masters.py masters
for p in flyer_A flyer_B; do $PY -I exporter.py masters/flyer_master_rgb.png profiles/$p.json out/$p.pdf; done
for p in sticker_A sticker_B; do $PY -I exporter.py masters/sticker_master_rgba.png profiles/$p.json out/$p.pdf; done
$PY -I preflight.py profiles/flyer_A.json out/flyer_A.pdf profiles/flyer_B.json out/flyer_B.pdf \
  profiles/sticker_A.json out/sticker_A.pdf profiles/sticker_B.json out/sticker_B.pdf
