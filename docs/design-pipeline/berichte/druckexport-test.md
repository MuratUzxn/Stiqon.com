# Spike: ein RGB-Master -> lieferantengerechte Druck-PDFs + Preflight (2026-11-27)

Machbarkeitsnachweis auf dieser Maschine, kein Produkt. Code und Re-Run-Anleitung: `spike/README.md`.

## 1. Was gebaut wurde

**Werkzeuge.** Vorhanden: Python 3.13.18, poppler, ImageMagick 6. Nachinstalliert (apt): Ghostscript 10.02.1, qpdf 11.9.0, icc-profiles-free, ArgyllCMS. venv: Pillow 13.0.0 (LittleCMS), reportlab 5.1.2, pikepdf 10.18.3, opencv-headless 5.1.0, shapely 2.2.0.

**ICC-Profile.** www.eci.org leitet per 301 auf eci.org um, das hier gesperrt ist; die echten ECI-Profile waren also nicht zu laden. Auf der Platte lagen nur Artifex-CMYK-Profile (SWOP) und die Fogra-Messdaten **FOGRA39L.ti3**. Daraus habe ich mit ArgyllCMS `colprof` zwei Ausgabeprofile gebaut (sRGB-Gamut-Mapping, GCR-Kurve `-kr`, K-Limit 98 %): **TAC 300 %** als Ersatz für PSO Coated v3 (Profil A) und **TAC 330 %** als Ersatz für ISO Coated v2 (Profil B). Beide beruhen auf FOGRA39. Deshalb trägt der OutputIntent ehrlich `FOGRA39` und nicht FOGRA51.

**Synthetische „KI-Ausgabe“:** Flyer RGB 1287×1795 px (Verlauf, Neon-Grün-Scheibe bis in den Anschnitt, gerasterter Schwarztext, Messfelder Neon-Grün 57/255/20, Blau 0/90/255, Hautorange 224/150/110, Schwarz); Sticker RGBA 900×900 px (Stern mit Biss, dünnem Stachel, Antialias-Kante).

**Vier Lieferantenprofile (JSON, synthetisch):**

| Profil | Format | Anschnitt / Safe | Stil | ICC (Ersatz) | Kontur |
|---|---|---|---|---|---|
| flyer_A | 105×148 mm | 2 / 3 mm | PDF/X-4, PDF 1.6, Transparenz erlaubt | TAC 300 | – |
| flyer_B | 105×148 mm | 3 / 4 mm | PDF/X-1a:2003, PDF 1.4, keine Transparenz | TAC 330 | – |
| sticker_A | Kontur max. 60 mm | 2 mm ab Kontur / 2 mm | X-4 | TAC 300 | „CutContour“ 0,25 pt, Überdrucken |
| sticker_B | Kontur max. 60 mm | 3 mm ab Kontur / 3 mm | X-1a:2003 | TAC 330 | „Stanze“ 0,5 pt, Überdrucken |

**Exporter** (`exporter.py`, reportlab zum Zeichnen, pikepdf zum Nachbearbeiten):
- **Seite** = Endformat + 2× Anschnitt. MediaBox = BleedBox, TrimBox = Endformat bzw. Kontur-Bounding-Box. CropBox und ArtBox werden entfernt.
- **Anschnitt füllen.** Flyer: *scale-to-cover* mit zentriertem Beschnitt. Der Master deckt bei A die 109×152 mm mit 299,9 ppi genau ab. Bei B (111×154 mm) bleiben nur 294,5 ppi, also skaliert der Exporter mit Lanczos um ×1,0194 auf 300 ppi hoch und protokolliert das. Sticker: *Kanten-Extrapolation*. Jeder transparente Pixel erhält die Farbe des nächsten deckenden Pixels (OpenCV `distanceTransformWithLabels`), außerhalb von Kontur + Anschnitt Papierweiß. Gespiegelte Kanten sind nicht umgesetzt.
- **Farbe.** sRGB (ungetaggter Master gilt als sRGB, ein eingebettetes iCCP wird genutzt) wird per LittleCMS nach CMYK gewandelt, **relativ farbmetrisch + Tiefenkompensierung**. Das Bild landet als DeviceCMYK, 8 bit, Flate (verlustfrei).
- **OutputIntent** `/GTS_PDFX` mit eingebettetem Profil (N=4), RegistryName color.org. Info-Dict: Title, Creator, Producer, CreationDate, ModDate, `Trapped /False`, `GTS_PDFXVersion` („PDF/X-4“ bzw. „PDF/X-1a:2003“). XMP: dc:title, xmp-Daten, pdf:Trapped, xmpMM:DocumentID/InstanceID/VersionID/RenditionClass, pdfxid:GTS_PDFXVersion. Trailer-/ID setzt qpdf. Die PDF-Version wird erzwungen, Objekt-Streams sind aus.
- **Live-Text** in 100 K (DeviceCMYK 0/0/0/1) mit Überdrucken (op, OPM 1) und eingebetteter Untermenge von DejaVu Sans Bold (TrueType). Die Schriftgröße wird automatisch an die Safe-Zone des Profils angepasst: 12,73 pt bei A, 12,46 pt bei B.
- **Transparenz.** Hinter dem Text liegt ein weißes Feld mit 55 % Deckung. Bei X-4 bleibt es live (ExtGState `ca 0.55`). Bei X-1a rechnet der Exporter es exakt in die CMYK-Pixel ein (C' = C·0,45, entspricht Normal-Blending in DeviceCMYK).
- **Stanzkontur.** Alpha ≥ 128 ergibt eine OpenCV-Außenkontur, die als shapely-Polygon um den Profilabstand (1,5 mm) versetzt wird. Danach Closing und Opening mit 0,8 mm Radius (rundet Innenecken, entfernt Nadeln unter 1,6 mm), Vereinfachung mit 0,02 mm Toleranz und ein geschlossener Pfad (213 Knoten, nur Geraden). Gezeichnet wird er im Farbraum `/Separation /CutContour` bzw. `/Stanze` (Alternativfarbe 100 % M), mit Strich-Überdrucken (OP true, OPM 1). Maßstab: Kontur-Box max. 60 mm (Ergebnis 56,76×59,92 mm).

**Preflight** (`preflight.py`): liest jedes PDF mit pikepdf zurück, interpretiert den Content-Stream (CTM, Farbe, Überdrucken) und prüft die Punkte der Tabelle unten. Safe-Zone über `pdftotext -bbox`, TAC über Ghostscript-Render `tiff32nc` 150 dpi, Spot-Auszug über `gs -sDEVICE=tiffsep`.

## 2. Pass/Fail-Tabelle (Endlauf)

| Prüfung | flyer_A | flyer_B | sticker_A | sticker_B |
|---|---|---|---|---|
| PDF-Version | PASS 1.6 | PASS 1.4 | PASS 1.6 | PASS 1.4 |
| Media = BleedBox, Anschnitt je Seite | PASS 2,00 mm | PASS 3,00 mm | PASS 2,00 mm | PASS 3,00 mm |
| TrimBox | PASS 105,00×148,00 | PASS 105,00×148,00 | PASS 56,76×59,92 (= Kontur-Box, Abw. 0,000) | PASS dto. |
| GTS_PDFXVersion Info+XMP; Trapped/ID/Daten | PASS X-4 | PASS X-1a:2003 | PASS X-4 | PASS X-1a:2003 |
| OutputIntent + ICC = Profildatei | PASS | PASS | PASS | PASS |
| Bild DeviceCMYK 8 bit, deckt BleedBox, kein RGB | PASS | PASS | PASS | PASS |
| eff. ppi ≥ 300 | PASS 299,9×300,0 | PASS 300,2×300,0 (hochskaliert) | PASS 344,5 | PASS 344,5 |
| Fonts eingebettet; Text 100 K überdruckend; in Safe-Zone | PASS | PASS | PASS | PASS |
| Spot-Name / 0,25 bzw. 0,5 pt / OP / geschlossen | – | – | PASS CutContour | PASS Stanze |
| Transparenz | PASS (live, erlaubt) | PASS (keine) | PASS (keine) | PASS (keine) |
| TAC p99,9 / max | PASS 260 / 280 % (≤ 300) | PASS 265 / 281 % (≤ 330) | PASS 199 / 245 % | PASS 199 / 245 % |
| gs tiffsep: Spot-Auszug | – | – | PASS | PASS |
| **Ergebnis** | **16/16** | **16/16** | **20/20** | **20/20** |

**Fehler, die der Preflight auf dem Weg gefunden hat (und die behoben wurden):**
1. reportlab legt immer Helvetica (/F1, nicht eingebettet) an. Abhilfe: leere BT/ET-Blöcke und unbenutzte Fonts löschen.
2. `Trapped` landete wegen eines pikepdf-Namensfehlers als `/False_` im PDF.
3. Feste 13 pt Schrift lief bei Profil B (4 mm Safe) aus der Safe-Zone. Abhilfe: Größe an die Safe-Zone anpassen.
4. Ein Fehler im Preflight selbst: Pfadschluss mit `h` wurde nicht als geschlossen erkannt.

**Negativtests:** flyer_A.pdf gegen Profil B fällt durch bei PDF-Version, Anschnitt, X-Kennung, OutputIntent und Transparenz. sticker_A.pdf gegen B fällt durch bei Version, Anschnitt, X-Kennung, OutputIntent, Spot „Stanze“ und tiffsep. `qpdf --check` meldet keine Syntax- oder Stream-Fehler.

## 3. Messungen

| Datei | Export | Preflight | Größe | Bildpixel |
|---|---|---|---|---|
| flyer_A | 0,24 s | 0,35 s | 260 KB | 1287×1795 |
| flyer_B | 0,36 s | 0,23 s | 306 KB | 1312×1819 (×1,019) |
| sticker_A | 0,25 s | 0,49 s | 255 KB | 824×867 |
| sticker_B | 0,29 s | 0,44 s | 256 KB | 852×895 |

Einmalig dazu: Profilbau mit colprof ca. 2,5 min je Profil. Die ICC-Wandlung allein braucht 0,03–0,06 s, die Kanten-Extrapolation 0,06 s. Das eingebettete Profil ist roh 354 KB groß und in der PDF Flate-komprimiert.

**Farbverschiebung** (Master-sRGB → CMYK aus der PDF zurückgelesen, Mittel 20×20 px → Lab D50 über das Ausgabeprofil, ΔE2000; Lab 8 bit, Genauigkeit etwa ±0,5). A und B ergeben bei diesen Feldern identische Werte, weil beide Ersatzprofile auf FOGRA39 beruhen und nur das TAC-Limit verschieden ist.

| Farbe | CMYK im PDF | ΔE00 rel.+BPC | ΔE00 perzeptiv | Bemerkung |
|---|---|---|---|---|
| Neon-Grün 57/255/20 | 61/0/96/0 | **17,4** | 16,6 | weit außerhalb des Gamuts, L 88 → 68 |
| Electric Blue 0/90/255 | 87/56/0/0 | **8,0** | 10,8 | Farbton kippt Richtung Cyan (a* +31 → −1) |
| Hautorange 224/150/110 | 0/44/55/12 | 0,6 | 0,6 | im Gamut, praktisch exakt |
| Schwarz 0/0/0 gerastert | 84/62/18/97 (TAC 260) | 6,2 | 5,4 | 4-farbiges Tiefschwarz: Passerrisiko bei kleinem Text |
| Live-Text 100 K | 0/0/0/100 | 9,9 zu RGB-Schwarz | – | L* 16,1 statt 9,8, dafür passergenau |

## 4. Was bewiesen ist und was nicht

**Bewiesen** (auf dieser Maschine, nur mit freien Bibliotheken):
- Ein Master ergibt reproduzierbar zwei unterschiedliche Lieferanten-PDFs, gesteuert nur über JSON-Profile.
- Boxen, Anschnitt-Füllung, CMYK über ICC, OutputIntent mit eingebettetem Profil und X-Kennungen in Info und XMP lassen sich so herstellen. Ebenso live Vektortext mit Font-Einbettung, eine Separation-Kontur mit Überdrucken und X-1a-Abflachung einfacher Transparenz.
- Ein eigener Preflight findet Abweichungen; Negativtests und vier echte Fehler belegen das.
- Laufzeit unter 1 s pro Datei.

**Nicht bewiesen:**
- **PDF/X-Konformität:** Es gibt keinen freien PDF/X-Validator (veraPDF prüft nur PDF/A). callas pdfToolbox, Acrobat Preflight oder der Upload-Check des Lieferanten müssen bestätigen: XMP-Pflichtfelder und Info=XMP, X-1a:2003 vs. :2001, OutputCondition-Felder, Trapped, Font-Lizenzflags, ICC v2.2 im X-1a-OutputIntent.
- **Echte Profile.** Mit PSO Coated v3 (FOGRA51, Papierweiß mit optischen Aufhellern) verschieben sich die CMYK-Werte und ΔE. Die Ersatzprofile sind keine Abnahmegrundlage. Mit den ECI-Dateien sind nur `icc_file` und `output_condition_identifier` zu tauschen. ΔE ist rein rechnerisch (Softproof), kein Andruck.
- **Grenzen der Bibliotheken:** reportlab kennt keine Transparenzgruppen und keine PDF/X-Presets, legt eine Phantom-Helvetica an und bettet nur TrueType-Untermengen ein (OpenType-CFF ungetestet). Pillow-LAB hat nur 8 bit. Ghostscripts tiffsep ist ein Indiz, kein normativer Nachweis.
- **Schwierig bleibt:**
  - **Konturqualität:** 213 Geraden-Knoten. Plotter mögen Bezier-Kurven mit wenigen Knoten; Kurven-Fitting fehlt. Mindestradien, Stegbreiten und Innenecken legt jeder Lieferant anders fest. Löcher oder mehrteilige Motive (Kiss-Cut) sind nicht abgedeckt, und Halbtransparenz an der Kante (Schatten, Glow aus der KI) verfälscht die Silhouette.
  - **Fonts:** Text, der in KI-Bildern eingebrannt ist, wird zu 4-farbigem Tiefschwarz und ist nicht editierbar. Live-Text muss aus einer Vorlage kommen, mit lizenziertem und einbettbarem Font.
  - **Hochskalierung:** Schon bei 2 % Upscale besteht der Preflight die ppi-Prüfung, obwohl keine Information hinzukommt. Größere Formate (Poster, Rollup) brauchen KI-Upscaling, und das ist eine Qualitäts- und keine Formatfrage.
  - **Neon- und RGB-Farben** sind nicht druckbar (ΔE 8–17). Nur Sonderfarben oder Erwartungsmanagement per Softproof helfen.

## 5. Technische Referenz (was der Exporter umsetzt)

- **Anschnitt:** Motiv läuft über das Endformat hinaus, typisch 1–3 mm je Seite, lieferantenabhängig. Saxoprint: 2 mm Standard (F); spiegelt fehlenden Anschnitt notfalls selbst (F). Im PDF: TrimBox = Endformat, BleedBox = Endformat + Anschnitt.
- **Sicherheitsabstand:** Text und Logos 3–5 mm innerhalb des Endformats (K); beim Sticker innerhalb der Kontur.
- **Auflösung:** effektive ppi bei Endgröße. Saxoprint verlangt für Fotos 250 dpi (F); 300 ppi ist die übliche Offset-Faustregel (K). Großformat kommt mit weniger aus (Saxoprint: Plakate mit Text 150 dpi, F).
- **Farbe und ICC:**
  - Ausgabe in CMYK für die Druckbedingung des Lieferanten. Saxoprint nennt PSO Coated v3 – FOGRA51 (gestrichen) und PSO Uncoated v3 – FOGRA52 (F). Flyeralarm nennt „ISO Coated v2 300 % (ECI)“ für Offset (F, aus PDF).
  - TAC: PSO Coated v3 300 % (W); ISO Coated v2 ECI 330 % bzw. 300 %-Variante (K, Web-Suche ohne ECI-Bestätigung).
  - Rendering-Intent relativ farbmetrisch + BPC oder perzeptiv (K); Schwarzer Text als 100 K überdruckend (K).
- **PDF/X:**
  - X-1a (2001: PDF 1.3; 2003: PDF 1.4): nur CMYK und Spot, keine Transparenz, keine Ebenen, keine Verschlüsselung (W).
  - X-3: wie X-1a, aber ICC-basierte und Lab-Farben erlaubt (K).
  - X-4 (PDF 1.6): live Transparenz und Ebenen, ICC-Farben, XMP-Metadaten Pflicht, Ausgabeprofil immer eingebettet (W). Saxoprint bevorzugt „PDF/X-4 bzw. PDF 1.6“ (F).
  - Allen gemeinsam: OutputIntent, Fonts eingebettet, TrimBox/BleedBox, Trapped gesetzt (K/W).
- **Konturkonvention:** eigene Schmuckfarbe (Separation) als Haarlinie, oft 100 % Magenta als Ersatzfarbe, überdruckend, damit sie das Motiv nicht ausspart. Der Name muss exakt stimmen; „CutContour“ ist Roland-VersaWorks-Konvention und groß-/kleinschreibungssensitiv (W). Andere verwenden „Stanze“, „Kontur“, „Thru-cut“ usw.; Namen, Linienstärke und Ebene stehen im Datenblatt des Lieferanten (unbekannt je Lieferant, dort nachlesen). Flyeralarm: Vorlagenebenen mit Stanzkonturen dürfen nicht mit ins Druck-PDF (F, aus PDF).
- **Weiß:** Weiß ist keine Prozessfarbe. Für transparente Folien oder Weißunterdruck wird es als eigene Schmuckfarbe angelegt (bei Roland z. B. RDG_WHITE), Flächen ohne Farbe bleiben sonst klar (W). Der Name ist lieferantenspezifisch; im Spike nicht umgesetzt. Er wäre analog zur Kontur als zweite Separation aus dem Alpha-Kanal ableitbar.

## Belege (F = heute mit dem Helper gelesen)
- F: „Standard-Beschnittzugabe: 2 mm“ – file `downloads/saxoprint/001.bin`
- F (dieselbe Datei): „vor, Beschnitt mithilfe einer Spiegelung anzulegen.“, „Fotos, Bilder und Grafiken: 250 dpi“, „(PDF/X-4 bzw. PDF 1.6), alternativ JPG, TIFF.“, „Coated v3 - FOGRA51“, „Uncoated v3 - FOGRA52“
- F (PDF): „Wir verwenden für Offset-Produkte: ISO Coated v2 300 % (ECI)“ – file `downloads/flyeralarm-pro/009.pdf`
- W: PSO Coated v3 = FOGRA51, max. 300 % (ECI-Material, per Suche); X-1a/X-4-Eckdaten (pdfa.org, Library of Congress, per Suche); CutContour/RDG_WHITE (rolanddga.com, Roland-Handbücher, per Suche).
- Gesperrt: eci.org (Ziel der Umleitung von www.eci.org).

## Gegenprüfung (Skeptiker, 2026-11-27)

Arbeitsverzeichnis: `spike-check` (eigene Skripte `inspect.py`, `tac.py`, `bleed.py`; Re-Run in `rerun/`). Die Dateien des Spikes wurden nicht verändert.

### 1. Re-Run aus sauberer Shell
- Code, Profile-JSON und `.ti3` nach `spike-check/rerun/` kopiert. Beide ICC-Profile mit den `colprof`-Befehlen aus dem README neu gebaut, dann `run_all.sh` und `measure.py` unter `env -i PATH=/usr/bin:/bin` ausgeführt. Ergebnis: Exit 0, 4 PDFs, 76× PASS, 0× FAIL.
- **Reproduzierbar:** Die neu gebauten ICC-Dateien unterscheiden sich vom Original nur in Byte 34 und 36 (Erstellungszeit im Header). Bild-Streams und Content-Streams aller vier PDFs sind bitgleich (SHA-1). `measure.json` ist bis auf Zeiten und Größen identisch, die Negativtests ebenso.
- **Einschränkung:** Die venv wurde verlinkt, nicht neu gebaut. Die `pip install`-Zeile im README nennt **keine Versionen**. Ein Neuaufbau zu einem späteren Zeitpunkt ist daher nicht garantiert gleich. Abhilfe: Versionen pinnen (`requirements.txt` mit den im Dossier genannten Versionen).

### 2. Unabhängige Prüfung der PDFs (pdfinfo -box, pdfimages -list, pdffonts, eigener pikepdf-Code, gs ink_cov/inkcov/tiffsep)
| Punkt | Befund | Urteil |
|---|---|---|
| Boxen | flyer_A: Media = Bleed 109×152 mm, Trim 2 mm eingerückt (105×148). flyer_B: 111×154 mm, 3 mm. Sticker: Trim = Kontur-BBox 56,76×59,92 mm, Anschnitt 2 bzw. 3 mm | bestätigt |
| Bilder | Je 1 Bild, `/DeviceCMYK`, 4 Komponenten, 8 bit, Flate, kein SMask/Mask. pdfimages: 300/300/344/344 ppi | bestätigt |
| OutputIntent | `/GTS_PDFX`, ICC eingebettet (N=4, 354.120 B, ICC v2.2, Klasse prtr, CMYK→XYZ), OCI „FOGRA39“, RegistryName color.org | bestätigt |
| X-Kennung | Info `GTS_PDFXVersion` = „PDF/X-1a:2003“ bzw. „PDF/X-4“, XMP `pdfxid` gleich, Trapped /False, ID vorhanden. Mein Verdacht, X-1a:2003 brauche zusätzlich `GTS_PDFXConformance`, hat sich laut Websuche (W: GTS-Registry, PRONOM-Signatur `/GTS_PDFXVersion(PDF/X-1a:2003)`) nicht bestätigt | bestätigt (W) |
| Kontur | `/Separation /CutContour` bzw. `/Stanze`, Alternativfarbe DeviceCMYK, Tint 1. Pfad: 1× `m`, 212× `l`, `h`, `S`; 0,25 bzw. 0,5 pt; ExtGState OP true, op true, OPM 1. Mit shapely: gültiges, einfaches (nicht selbstschneidendes) Polygon, 1.781 mm². tiffsep erzeugt eigene Platte „CutContour“ bzw. „Stanze“ | bestätigt |
| Anschnitt Sticker | Pixel-Test gegen die Kontur: innerhalb der Kontur und bis Anschnitt − 0,15 mm **0 papierweiße Pixel**; jenseits Anschnitt + 0,3 mm **0 bedruckte Pixel**. Der Anschnitt ist echt vorhanden, aber nur als Farbverschmierung der Randpixel (Nearest-Pixel) | bestätigt, mit Vorbehalt (s. u.) |
| Anschnitt Flyer | Kein Strecken: B wird gleichmäßig ×1,0194 skaliert (300,2×300,0 ppi) und 9 px vertikal beschnitten. Der Anschnitt enthält echte Motivpixel | bestätigt |
| Fonts | DejaVuSans-Bold, TrueType-Untermenge, FontFile2, ToUnicode. pdftotext liefert „Größe 105×148 mm“ korrekt. Flags 262148 (Symbolic + ForceBold) | bestätigt |
| Transparenz | flyer_A: ExtGState `ca 0.55` (live, X-4). flyer_B, sticker_A/B: keine `ca/CA/SMask/BM/Group` | bestätigt |
| TAC (eigene Rechnung aus den Bildpixeln, ohne Rendern) | flyer_A max 280,0 %, p99,9 260,4 %. **flyer_B max 291,8 %** (Dossier: 281 %, gemessen am 150-dpi-Render). Sticker-Bild max 157 %; die 245 % im Dossier entstehen erst durch den 100-K-Text und die Magenta-Ersatzfarbe der Kontur im Render | Grenzwerte eingehalten; die Maxima im Dossier sind ungenau |
| Deckung (gs ink_cov, Mittel C/M/Y/K) | Flyer ≈ 64/58/24/6 %, Sticker ≈ 35–37/9/26–27/5 %. Passt zum dunkelblauen Motiv bzw. zum weißen Umfeld | plausibel |
| qpdf --check | keine Fehler | bestätigt |

### 3. Was der eigene Preflight nicht sieht oder überzeichnet
1. **„PDF/X“ ist eine Kennzeichnung, keine Konformität.** Das Dossier sagt das selbst in Abschnitt 4. Die PASS-Zeile „GTS_PDFXVersion … PASS X-4“ der Tabelle darf im Plan aber nicht als „PDF/X-konform“ erscheinen. Konformität bleibt **unbekannt**, bis callas pdfToolbox, Acrobat Preflight oder der Upload-Check des Lieferanten sie bestätigt.
2. **flyer_A (X-4 mit Live-Transparenz) hat keine Seiten-Transparenzgruppe (`/Group`).** Ob ein X-4-Validator das bemängelt, ist unbekannt. Prüfen lässt es sich mit einem echten X-4-Preflight.
3. **Leeres `/Trans`-Dict auf jeder Seite** (Artefakt von reportlab, eine Seitenübergangs-Angabe). Vermutlich harmlos; strenge Preflights könnten es melden (K). Der Exporter sollte es entfernen.
4. **Sticker-Rand ist verschmierte Farbe.** Die Kontur liegt 1,5 mm *außerhalb* der Motivkante. Der sichtbare 1,5-mm-Rand des fertigen Stickers besteht also vollständig aus extrapolierten Randpixeln, nicht nur der Anschnitt. Beim synthetischen Verlauf fällt das nicht auf; bei Fotos oder Texturen aus der KI gibt es radiale Streifen. Das Dossier nennt die Kanten-Extrapolation, verschweigt aber diese Folge für den sichtbaren Rand. Alternativen: weißer Rand per Weißfläche oder Schnitt direkt an der Motivkante.
5. **Mehrteilige Motive gehen still verloren.** `exporter.py` nimmt nur die flächengrößte Außenkontur (`max(cs, key=cv2.contourArea)`, nach dem Opening `max(cut.geoms …)`). Abgetrennte Teile werden ohne Warnung und ohne FAIL weiß überschrieben oder abgeschnitten. Das Dossier nennt die Lücke („nicht abgedeckt“), der Preflight erkennt sie aber nicht. Nötig ist ein harter Abbruch, wenn mehr als eine Insel bzw. ein Loch vorkommt.
6. **Lücke in den Negativtests.** Die Prüfung der Konturlinienstärke läuft nur, wenn der Spot-Name passt. Bei sticker_A gegen Profil B fehlt deshalb „0,25 statt 0,5 pt“ in der Fehlerliste; eine falsche Linienstärke wird nur indirekt über den Namen gefangen.
7. **TAC-Messung per Render** (150 dpi) mittelt Spitzen weg (flyer_B: 281 statt 291,8 %) und rechnet die Ersatzfarbe der Kontur sowie überdruckenden Text ein. Für die Grenzprüfung sollte zusätzlich das Bild pixelgenau geprüft werden.
8. **„1:1“ zwischen Lieferanten gilt nur eingeschränkt.** flyer_B ist gegenüber A um 1,94 % vergrößert und anders beschnitten. Elemente nahe der Safe-Zone verschieben sich. Außerdem wurde der synthetische Master exakt im Seitenverhältnis des Anschnittformats von Profil A erzeugt (1287/1795 = 0,7170 ≈ 109/152). Echte KI-Ausgaben haben feste Seitenverhältnisse (unbekannt je Higgsfield-Modell, in der API-Doku nachsehen). Dann schneidet *scale-to-cover* sichtbar mehr ab. Die Generierung muss Format, Anschnitt und Safe-Zone von Anfang an kennen.
9. **OutputConditionIdentifier „FOGRA39“ mit color.org-Registry, eingebettet aber ein selbstgebautes TAC-Profil.** Das ist formal vertretbar, da das Profil auf den FOGRA39-Messdaten beruht. Ein Lieferant, der PSO Coated v3/FOGRA51 verlangt, lehnt das trotzdem ab (im Dossier genannt). Für die Abnahme zählen nur die echten ECI-Profile.

### 4. Urteile zu den Kernaussagen
- Reproduzierbarer Re-Run nach README: **bestätigt** (mit nicht gepinnten pip-Versionen als Restrisiko).
- Boxen, Anschnitt, CMYK-Bild, OutputIntent mit eingebettetem ICC, Fonts eingebettet, 100 K überdruckend, Separation-Kontur geschlossen und überdruckend, X-1a ohne Transparenz: **bestätigt** mit unabhängigen Mitteln.
- ppi ≥ 300: **bestätigt, aber aussagearm.** Bei flyer_B entsteht der Wert durch Hochskalierung; das Dossier sagt das selbst.
- TAC-Zahlen: **ungenau** (Maximum flyer_B 291,8 statt 281 %), Grenzen eingehalten.
- PDF/X-Konformität: **nicht belegt.** Das Dossier sagt das korrekt; im Plan nur „PDF/X-gekennzeichnet, Validierung offen“ schreiben.
- Sticker-Anschnitt und -Rand: Anschnitt **echt, aber extrapoliert**. Der sichtbare 1,5-mm-Rand ist ebenfalls extrapoliert; das ist im Dossier **untertrieben dargestellt**.
- ΔE-Werte: nur als reproduzierbar geprüft (identisch im Re-Run), inhaltlich **nicht unabhängig nachgerechnet**.
