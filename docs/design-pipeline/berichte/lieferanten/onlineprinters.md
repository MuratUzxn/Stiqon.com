# Onlineprinters (onlineprinters.de) – Druckdaten, Datencheck, Reseller/API
Stand: 2026-11-27. Evidenz: F = heute auf onlineprinters.de gelesen, W = Websuche, K = eigenes Wissen. Ohne Kennzeichnung = F.

## A. Druckdaten-Anforderungen

**Allgemein (gilt „wenn nicht anders angegeben“; maßgeblich ist der Abschnitt „Druckdatenhinweise“ der jeweiligen Produktseite):**
- Dateiformate: PDF (bevorzugt), TIFF (eine Datei pro Seite, keine Multipage-TIFFs, Ebenen reduziert), JPEG (höchste Qualität, „Baseline (Standard)“).
- PDF-Standard: **PDF/X-4:2010**; keine Passwörter oder Sicherheitseinstellungen; Maßstab 1:1; alle Seiten gleich groß und gleich ausgerichtet.
- Farbmodus **CMYK**. **RGB wird automatisch nach CMYK konvertiert** (Farbverschiebungen möglich, kein Abbruch).
- ICC-Profile: gestrichene Papiere **PSO Coated v3** (Produktseite Flyer: „FOGRA51 (PSO Coated v3)“); ungestrichene Papiere (Offset-, Natur-, Recyclingpapier) **PSO Uncoated v3 FOGRA52**; andere Materialien (Fahnen, Banner, Displays) **PSO Coated v3**. Download bei ECI.
- Auflösung: mindestens **300 dpi**. Ausnahmen für Großformate sind nicht geprüft (Seite /c/druckdaten/aufloesung nicht gelesen).
- Beschnitt **2 mm** ringsum, Sicherheitsabstand **4 mm** zum Endformat. Hintergründe müssen bis an den Rand des Beschnitts laufen; der Beschnitt darf nicht weiß bleiben; Rahmen werden nicht empfohlen.
- Schriften: vollständig oder als Untergruppe einbetten oder in Pfade umwandeln, auch Standardschriften. Empfohlene Mindestgröße **6 pt**. Schwarzer Fließtext 0/0/0/100. **Schwarzer Text unter 12 pt wird automatisch auf Überdrucken gesetzt.** Tiefschwarz für Flächen: 40/20/20/100 oder 50/50/30/100.
- Linien: positiv mindestens **0,25 pt (0,09 mm)**, negativ mindestens **0,5 pt (0,18 mm)**.
- Maximaler Farbauftrag: **300 %** bei Standardproduktion, **260 %** bei Blitz/Express; Minimum ca. 3–5 % pro Kanal.
- Transparenzen: Live-Transparenzen werden verarbeitet; der Transparenzfarbraum muss einheitlich CMYK sein.
- Sonderfarben: Es gibt eine Anleitung (/c/faq/sonderfarbe), die nicht gelesen wurde → unbekannt.
- Maximale Dateigröße: **unbekannt**. Beim Support anfragen.

**Aufkleber/Etiketten mit Konturschnitt** (Seite /c/druckdaten/konturschnitt):
- Schnittkontur als zusätzliche **Volltonfarbe mit dem Namen „CutContour“**, z. B. 100 % Magenta. Sie dient als Hilfslinie und wird nicht gedruckt.
- Die Kontur muss ein **geschlossener Vektorpfad** mit möglichst wenigen Knoten sein. Keine filigranen Motive und keine spitzen Kanten; **Eckenradius mindestens 4 mm**.
- Abstand von Text und Grafik zur Kontur: allgemein mindestens **3 mm** (Schneidetoleranz ca. 1–2 mm); **bei Klebeetiketten mindestens 2 mm** (Toleranz ca. 1 mm). Dateiformat PDF.
- Nicht angegeben: Strichstärke, Überdrucken, eigene Ebene, und ob Standardformen (rund oder eckig) überhaupt eine Kontur brauchen → unbekannt.
- Weißdruck auf transparenter oder metallischer Folie: **unbekannt**. Eine Seite gibt es nur für „Weißdruck bei Werbeartikeln“, und die wurde nicht gelesen.
- Eine Produktseite für Aufkleber ließ sich nicht öffnen: die geratene URL leitete auf die Startseite um. Formate, Materialien und produktspezifischer Beschnitt sind deshalb unbekannt.

**Flyer DIN A5** (Produktseite /p/flyer-din-a5):
- Endformat 14,8 x 21 cm, Datenformat mit 2 mm Beschnitt 15,2 x 21,4 cm.
- 300 dpi; mindestens 4 mm Abstand zum Endformat.
- CMYK mit FOGRA51 (PSO Coated v3) für gestrichene bzw. FOGRA52 (PSO Uncoated v3 FOGRA52) für ungestrichene Papiere. Leserichtung beachten.
- Die Produktseite hat einen Reiter „Druckvorlagen“ mit einer PDF-Vorlage (printTemplates/...pdf).

**Banner/Fahnen/Displays:** Bestätigt ist nur das Profil PSO Coated v3. Beschnitt, Saum, Ösen und dpi stehen auf der jeweiligen Produktseite und wurden nicht gelesen.
**Visitenkarten, Plakate, Roll-ups:** Produktseiten nicht gelesen. Die Allgemeinwerte gelten, solange die Produktseite nichts anderes sagt.

## B. Datencheck
- **Beim Upload** wird automatisch auf die wichtigsten Anforderungen geprüft, etwa Format und Auflösung.
- **Datencheck als kostenpflichtige Zusatzoption** auf der Produktseite (Preis unbekannt, steht im Konfigurator). Geprüft werden Auflösung, Beschnitt, Format gegenüber dem bestellten Produkt, Kommentare/Formularfelder und Schriften. Rechtschreibung wird nicht geprüft.
- Korrekturen „falls möglich“: z. B. wird fehlender Beschnitt ergänzt, Kommentare werden verworfen, Formularfelder werden druckbar gemacht. Das kann die Lieferzeit verlängern.
- Sind die Daten nicht verwendbar, kommt eine **E-Mail**, und die Produktion startet erst nach der Korrektur durch den Kunden.
- Kurz nach dem Upload kann man die Daten noch ersetzen („Druckdaten löschen & neu hochladen“). Danach startet die Produktion zeitnah.

## C. Reseller-Programm und API (op.connect)
- Das Reseller-Programm steht allen Resellern offen: bis zu 25 % Preisvorteil nach Jahresumsatz, 30 Tage Zahlungsziel, persönlicher Ansprechpartner.
- **Neutraler Absender** ist im Liefergebiet ohne Zusatzkosten möglich. Bei vielen Produkten kann ein **eigener Lieferschein als PDF** beigelegt werden; bis zu 10 Lieferadressen.
- Reseller können jetzt bestellen und die **Druckdaten bis zu fünf Wochen später hochladen**.
- **op.connect** ist eine kostenfreie API zum Austausch von Produkt-, Preis-, Bestell- und Rechnungsdaten, auch über E-Procurement-Portale (Onventis, SAP/Ariba, Synertrade).
- Zugang **auf Anfrage**. Zielgruppe sind Wiederverkäufer und Großkunden mit **hohem Druckvolumen**. Eine „detaillierte API-Dokumentation“ gibt es nur nach Freischaltung; sie ist nicht öffentlich und war auch per Websuche (W) nicht auffindbar.
- **Unbekannt:** Authentifizierung, Endpunkte und Felder, wie die Druckdatei übergeben wird (Multipart, URL-Pull, eigener Upload-Schritt, je Seite), Proof oder Vorschau über die API, Webhooks oder Status-Callbacks, Sandbox. Das muss über das Formular „Jetzt anfragen“ bzw. den Reseller-Ansprechpartner geklärt werden.
- Im Shop gibt es eine Vorschau beim Upload vor dem Kauf, aber nicht für alle Produkte. Zu Freigabe-PDF und digitaler Druckfreigabe gibt es FAQ-Seiten, die nicht gelesen wurden.
- Daneben gibt es kostenpflichtige Lösungen: einen Printshop im Firmendesign ab 29 Euro im Monat plus Einrichtung, PunchOut und ein Full-Service-Portal.

## D. Automatisierungsrisiken
- Die API ist nicht öffentlich. Ohne Freischaltung, die ein hohes Volumen voraussetzt, ist keine automatische Bestellung möglich; Datei-Übergabe und Status sind unbekannt.
- Die Allgemeinwerte sind nur Standardwerte. Bei Aufklebern, Großformat und Falzprodukten können die Produktseiten abweichen. Der Exporter braucht deshalb eine Regeltabelle pro Produkt und Format.
- Das Profil hängt vom Papier ab. Ein falsches Profil löst keinen Fehler aus, verfälscht aber die Farben.
- RGB wird stillschweigend konvertiert: Wer KI-Motive nicht selbst kontrolliert nach CMYK wandelt, bekommt Farbverschiebungen.
- CutContour verlangt einen geschlossenen Vektorpfad mit mindestens 4 mm Eckenradius. Pixelmotive brauchen deshalb einen eigenen Vektorisierungsschritt für die Kontur.
- Fehlerhafte Daten führen zu Produktionsstopp und E-Mail-Schleife. Der Datencheck kostet extra.
- Unbekannt sind die Weißdruck-Regel für Folien, die maximale Dateigröße und die Sonderfarben-Regeln.

## Was heißt das für die Pipeline
- Export-Standard für Onlineprinters: PDF/X-4:2010, CMYK, 1:1, 300 dpi, 2 mm Beschnitt, 4 mm Sicherheitsabstand, Schriften in Pfade, Farbauftrag höchstens 300 % (bei Express 260 %).
- Das ICC-Profil wird aus der Papierwahl abgeleitet: gestrichen PSO Coated v3 (FOGRA51), ungestrichen PSO Uncoated v3 FOGRA52, andere Materialien PSO Coated v3.
- Für Konturschnitt-Sticker: Volltonfarbe „CutContour“ (100 % Magenta) als geschlossener Vektorpfad, Eckenradius mindestens 4 mm, Motivabstand zur Kontur mindestens 2 mm (Etiketten) bzw. 3 mm.
- Vor dem Go-live die „Druckdatenhinweise“ jeder angebotenen Produkt- und Formatvariante einlesen und als Regeln speichern; die Allgemeinwerte reichen nicht.
- Eine eigene Preflight-Prüfung (Format, Beschnitt, dpi, Fonts, TAC, Linien ≥ 0,25 pt, Text ≥ 6 pt) ersetzt den kostenpflichtigen Datencheck.
- Den API-Zugang zu op.connect beim Reseller-Ansprechpartner anfragen und dabei klären: Authentifizierung, Datei-Übergabe, Status/Webhooks, Sandbox, Mindestvolumen.
- Bis zur Freischaltung bleibt nur der Halbautomat: Bestellung im Shop, Upload über das Kundenkonto (bis zu 5 Wochen später möglich), neutraler Absender und eigener Lieferschein.

## Quellen (heute selbst gelesen)
- https://www.onlineprinters.de/c/lp/reseller
- https://www.onlineprinters.de/c/lp/op-connect
- https://www.onlineprinters.de/c/hilfecenter
- https://www.onlineprinters.de/c/faq/druckdaten
- https://www.onlineprinters.de/c/faq/einstellungen-druck-pdf
- https://www.onlineprinters.de/c/faq/welche-dateiformate-moeglich
- https://www.onlineprinters.de/c/faq/was-ist-beschnitt
- https://www.onlineprinters.de/c/druckdaten
- https://www.onlineprinters.de/c/druckdaten/farbmodus-farbraum
- https://www.onlineprinters.de/c/druckdaten/konturschnitt
- https://www.onlineprinters.de/c/druckdaten/datencheck
- https://www.onlineprinters.de/c/druckdaten/datenuebermittlung
- https://www.onlineprinters.de/c/druckdaten/schriften
- https://www.onlineprinters.de/c/druckdaten/linienstaerke
- https://www.onlineprinters.de/c/druckdaten/farbauftrag
- https://www.onlineprinters.de/c/druckdaten/transparenzen
- https://www.onlineprinters.de/p/flyer-din-a5
- https://www.onlineprinters.de/k/werbeaufkleber-2 (nur Linkliste)

## Gegenprüfung
Stand 27.11.2026. Geprüft wurden alle First-hand-Werte des Review-Bundles; neu gelesen wurden die Kategorie „Aufkleber“ (Produktlinks werden per JS geladen) und die Produktseite „Adressaufkleber“.

Geändert:
- **Sticker/Etiketten:** Für das Etikettenprodukt Adressaufkleber bestätigt die Produktseite **2 mm Beschnitt, 4 mm Abstand zum Endformat und 300 dpi**. Sie ist als neuer Produkteintrag erfasst. Für Aufkleber mit Konturschnitt bleibt der Beschnitt beim Allgemeinwert 2 mm („wenn nicht anders angegeben“). Eine Werbeaufkleber-Produktseite war nicht lesbar.
- **Sicherheitsabstand zur Schnittkontur:** Er liegt allgemein bei **3 mm**. 2 mm gelten nur für Klebeetiketten; safe_mm ist von 2 auf 3 korrigiert.
- **Farbauftrag und Mindestschrift:** Beides sind **Empfehlungen**: 300 % bzw. 260 % bei Blitz, 6 pt. Das ist ergänzt.
- **Fünf-Wochen-Upload:** Die Angabe steht auf der Reseller-Seite, nicht auf der Datenübermittlungsseite. Die Quelle ist korrigiert.

Bestätigt: PDF/X-4:2010 ohne Passwort, CMYK, PSO Coated v3 bzw. PSO Uncoated v3 FOGRA52 laut Datenhinweis, RGB→CMYK automatisch, 300 dpi, Allgemeinwerte 2 mm Beschnitt und 4 mm Abstand, Live-Transparenzen, CutContour-Regel (Volltonfarbe „CutContour“, geschlossener Pfad, Radius mindestens 4 mm), Flyer A5 (2 mm / 4 mm), op.connect nur auf Anfrage und kostenfrei.

Offen: Beschnitt für konturgeschnittene Werbeaufkleber laut Produktseite, Strichstärke und Überdrucken der CutContour, Weißdruck auf Folie, API-Übergabe der Druckdaten, Status-Push, Sandbox.
