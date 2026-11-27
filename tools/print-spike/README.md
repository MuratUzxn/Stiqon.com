# Druckexport-Test

Machbarkeitstest, kein Produkt. Ein RGB-Master wird je Lieferantenprofil zu einem Druck-PDF:
Beschnitt, CMYK mit OutputIntent, Seitenboxen, Text als Vektor, Schneidekontur als Sonderfarbe.
Ein Preflight liest jedes PDF gegen sein Profil zurück. Ergebnisse und Grenzen stehen in
`docs/design-pipeline/berichte/druckexport-test.md`.

Die Profile hier sind synthetisch. Die echten Lieferantenvorgaben stehen in `docs/design-pipeline/profile`.

## Neu ausführen (Ubuntu 24.04, als root)

```sh
cd tools/print-spike
apt-get install -y ghostscript qpdf icc-profiles-free argyll poppler-utils fonts-dejavu-core
python3 -m venv venv
./venv/bin/pip install pillow reportlab pikepdf numpy opencv-python-headless shapely
# Ersatz-Farbprofile, weil die echten ECI-Profile nicht erreichbar waren (je etwa 2,5 min)
mkdir -p icc && cd icc
cp /usr/share/color/icc/FOGRA39L.ti3 f39a.ti3 && cp /usr/share/color/icc/FOGRA39L.ti3 f39b.ti3
colprof -qm -ax -kr -l300 -L98 -S /usr/share/color/icc/sRGB.icc \
  -D "Spike FOGRA39L TAC300 (stand-in PSO Coated v3)" -O stand_in_A_tac300.icc f39a
colprof -qm -ax -kr -l330 -L98 -S /usr/share/color/icc/sRGB.icc \
  -D "Spike FOGRA39L TAC330 (stand-in ISO Coated v2)" -O stand_in_B_tac330.icc f39b
cd ..
mkdir -p masters out
./run_all.sh
./venv/bin/python -I measure.py
```

Mit den echten ECI-Profilen: zum Beispiel `PSOcoated_v3.icc` und `ISOcoated_v2_eci.icc` nach `icc/`
legen und in `profiles/*.json` die Felder `icc_file` und `output_condition_identifier`
(FOGRA51 bzw. FOGRA39) ändern.

## Dateien

- `make_masters.py`: synthetisches KI-Motiv, Flyer RGB 1287 × 1795 px, Sticker RGBA 900 × 900 px
- `profiles/*.json`: flyer_A (PDF/X-4, 2 mm Beschnitt), flyer_B (PDF/X-1a, 3 mm), sticker_A (Kontur „CutContour“), sticker_B (Kontur „Stanze“)
- `exporter.py`: Master und Profil ergeben ein PDF
- `preflight.py`: liest die PDFs zurück und druckt eine Tabelle PASS/FAIL
- `measure.py`: Farbabweichung, Laufzeiten, Dateigrößen, Gegenproben
