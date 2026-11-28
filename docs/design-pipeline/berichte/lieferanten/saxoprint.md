# SAXOPRINT (Dresden) – Druckdaten, Datencheck, Schnittstelle

Stand: 2026-11-27. Evidenz: F = heute auf saxoprint.de gelesen, W = Websuche, K = eigenes Wissen. Zitate und Dateipfade stehen in `saxoprint.profile.json`.

## A. Druckdaten-Anforderungen

**Allgemein (F, Druckdatenanleitung):**
- **Formate:** nur geschlossene Formate, bevorzugt **PDF (PDF/X-4 bzw. PDF 1.6)**, alternativ JPG oder TIFF. Ein Fehlermeldungstext im Shop nennt dagegen „PDF 1.4, JPEG, TIFF“, also ist die Angabe widersprüchlich. Für die Pipeline: PDF/X-4 verwenden.
- **Upload-Center:** PDF, JPG oder TIFF, **keine RAR/ZIP-Archive**, keine Umlaute oder Sonderzeichen im Dateinamen. Einzelseiten heißen `01.pdf`, `02.pdf` …, Broschüren getrennt in „Umschlag“ und „Inhalt“. **Upload nur einmal pro Auftrag.**
- **Farbe:** CMYK, kein RGB. Graustufen-Bestellung bedeutet Graustufen-Datei. Schwarzer Text nur mit 100 % K, kein 4c-Schwarz.
- **ICC-Profile:** **PSO Coated v3 (FOGRA51)** für gestrichene Papiere *und Werbetechnik*, **PSO Uncoated v3 (FOGRA52)** für ungestrichene Papiere. Für Folienaufkleber wird kein eigenes Profil genannt.
- **Auflösung:** Bilder 250 dpi, Strichbilder 1.200 dpi, Plakate und Werbetechnik mit Text 150 dpi, Werbetechnik 72 dpi.
- **Beschnitt:** Standard **2 mm**. Ausnahmen: Briefumschläge 1 mm; Werbetechnik, Verpackungen, Präsentationsmappen, Klatschpappen und Monatskalender 3 mm; Prospekte (Rollenoffset) 5 mm; Textilspannrahmen 15 mm. Die Daten werden im offenen Endformat inklusive Beschnitt angelegt.
- **Sicherheitsabstand:** 3 mm zum Endformat. Bei Wire-O 20 mm zur Bindung, bei Foldern 5 mm beidseitig zum Falz. Keine Rahmen als Layoutbegrenzung.
- **Farbauftrag:** maximal **300 %** (Offset). Keine Registerfarbe (400 %) im Motiv. Sichtbare Elemente brauchen mindestens 5 % Farbauftrag, komplett leere Seiten werden abgelehnt.
- **Linien:** mindestens **0,125 pt** bei mindestens 40 % Farbauftrag. Dünnere Linien werden automatisch verstärkt, Linien mit 0 pt nicht garantiert.
- **Schrift:** einbetten oder in Pfade wandeln, nur lizenzfreie Fonts mit korrekter Kodierung. Mindestgröße **6 pt** (die Angabe steht im Absatz zur Folienveredelung). Text, der veredelt wird, nicht in Pfade wandeln.
- **Transparenz/Ebenen:** native Transparenzen erhalten. **Keine Ebenen (Optional Content).** Keine aktiven Hyperlinks und keine Formularfelder.
- **Sonderfarben (HKS/Pantone, aufpreispflichtig):** Den Farbkanal mit der Farbnummer benennen und als 100 % Vollton anlegen. CMYK über einer Sonderfarbe wird ausgespart, nicht überdruckt (und umgekehrt). Sonderfarben ohne entsprechende Bestellung werden nach CMYK umgewandelt.
- **Dateigröße:** keine feste Grenze genannt. Es gibt die Fehlermeldung „Maximale Dateigröße überschritten“, und ein Fehlertext nennt Dateien über 1 GB als nicht verarbeitbar.
- **Abweichendes Format:** wird ohne Randanschnitt automatisch auf das Bestellformat skaliert. Fehlender Beschnitt wird durch Spiegelung ergänzt.
- **Vorlagen:** Druckvorlagen-Generator unter /druckvorlagen (PDF, InDesign, Illustrator; bei Visitenkarten auch PSD). Vorlage und Datenblatt gibt es im Produktkonfigurator. Die Downloads liegen auf api.saxoprint.de und waren hier gesperrt.

**Aufkleber (F):**
- Druck im Offset 4/0 Euroskala, CMYK. Mindestmenge 250 Stück, Formate bis DIN A3.
- Materialien: 95 µm Haftfolie matt, 80 µm Haftfolie glänzend, 80 µm Haftfolie transparent, Haftpapier 70/80 g/m², vegane und ablösbare ECO-Folie.
- Standardformen (rund, oval, runde Ecken) werden **gestanzt**, rechteckige und quadratische **geschnitten**. Für Standardformen ist keine Kontur in den Daten dokumentiert. Für Beschnitt und Sicherheitsabstand gelten die Standardwerte 2 mm und 3 mm; eine produktspezifische Bestätigung fehlt.
- **Konturaufkleber:** 135 g/m² PVC-Folie weiß glänzend oder matt, nur feste Bogenformate:
  - 289×202 mm (1 Etikett je Bogen), 142,5×202 mm (2), 202×94 mm (3), 99×142,5 mm (4)
  - 99×94 mm (6), 99×69 mm (8), 47,5×69 mm (16), 30×44,5 mm (36)
  - **Die Schneidkontur-Regel ist unbekannt.** Der Fehlerkatalog kennt „In den Druckdaten fehlt die Schneidkontur.“ Bei Stanzprodukten (Verpackungen) ist die Kontur in der Vorlage als Sonderfarbe **„DieCutting“** angelegt, daneben stehen Bleed, FoldEdge und GluedArea. Ob das auch für Konturaufkleber gilt, ist **nicht bestätigt**. Das Datenblatt im Konfigurator holen oder den Service fragen.
- **Weißdruck:** nur auf **transparenter Haftfolie**, in den Varianten „ohne“, „vollflächig“ oder „hinter Farbflächen“. Regeln für „hinter Farbflächen“ (F, Fehlerdiagnose Farben):
  - eigener Sonderfarbkanal **„Weißdruck“** in 100 % Vollton, in der Anzeige besser Magenta statt Weiß
  - steht **immer auf Überdrucken**
  - keine Verläufe, Transparenzen, Raster oder Effekte
  - Linien mindestens 8 pt (2,82 mm), Schrift mindestens 6 pt
  - Ein anderer Fehlertext nennt 0,25 mm als Mindestlinie. Bis zur Klärung den strengeren Wert (2,82 mm) verwenden.
  - Fehler hier sind „NICHT mit Druckfreigabe produzierbar“.

**Flyer (F):** DIN A4, A5, A6, DIN lang und die Sonderformate 10×21, 21×21 und 14,8×14,8 cm. 13 Papiersorten von 80 bis 450 g/m². 2 mm Beschnitt, 3 mm Sicherheitsabstand, 250 dpi. Bilderdruckpapier mit FOGRA51, Offsetpapier mit FOGRA52 ausgeben.

**Falzflyer:** 5 mm Abstand zum Falz. Der Profi-Datencheck prüft Falzart und Falzposition.

**Plakate:** 150 dpi mit Text. Ob 2 mm oder 3 mm Beschnitt gilt, ist offen, weil nicht klar ist, ob Plakate als Werbetechnik zählen.

**Roll-ups/Werbetechnik:** 3 mm Beschnitt, 72 dpi (150 dpi mit Text), FOGRA51.

**Visitenkarten:** Standardwerte. Vorlagen für Hoch- und Querformat, 1- und 2-seitig sowie Klappkarten mit 4 Seiten.

## B. Datencheck (F)
- **Basis-Datencheck (kostenfrei, bei jedem Auftrag):** prüft Seitenmaße, CMYK und eingebettete Schriften. Wandelt RGB und nicht bestellte Sonderfarben automatisch nach CMYK um und passt das Format an, **teils ohne vorherige Information**.
- **Profi-Datencheck (9,90 €):** prüft zusätzlich Randanschnitt, Farbprofil, Ebenen sowie Falzart und Falzposition. Dabei werden **keine automatischen Anpassungen** vorgenommen.
- **Ergebnis:** kommt per E-Mail, mit einem Fehlerprotokoll, das auf die Fehlerdiagnose-Seiten verlinkt. Der Fehlerkatalog unterscheidet „Mit Druckfreigabe produzierbar“ (der Kunde erteilt im Kundenkonto die Freigabe) von „NICHT mit Druckfreigabe produzierbar“ (neue Daten nötig).
- **Typische Ablehnungen:**
  - Weißdruck falsch angelegt
  - Veredelung als Pixelbild
  - fehlende Schneidkontur
  - Broschüren nicht als Einzelseiten
  - beschädigte, passwortgeschützte oder ungültige Datei
- **Korrektur:** neue Daten über die Auftragsübersicht hochladen.

## C. Reseller und Schnittstelle
- **API existiert, aber nur für Partner (F, /schnittstelle).** Ablauf:
  1. E-Mail an den Service mit Kundennummer, geplantem Jahresumsatz und der Angabe, ob die Anbindung selbst umgesetzt werden kann
  2. Fragebogen und NDA
  3. nach Prüfung Zugang zur **interaktiven Schnittstellendokumentation** und einem **Staging-API-Key**
  4. Tests, danach **Live-API-Key** und Live-Prüfung
- **Voraussetzungen:** Kundenkonto als Reseller oder Geschäftskunde, Rechnungsfreigabe (USt-IdNr. eingetragen), eigene IT-Ressourcen.
- **Umfang laut Seite:** Bestellungen, Druckdaten und Auftragsinformationen werden direkt übertragen; SAXOPRINT bleibt dabei „unsichtbar im Hintergrund“.
- **Unbekannt (Doku erst nach NDA):**
  - Authentifizierungsdetails über „API-Key“ hinaus
  - Endpunkte und Felder
  - wie die Datei übergeben wird (Multipart oder URL)
  - Produkt- und Preisabfrage
  - Webhooks
- **Web-Shop:** Upload-Center, Upload einmal pro Auftrag, „Upload mit Vorschau & Prüfung“, Vorab-Proof als Option.
- **Reseller-Leistungen:** neutraler Versand an Endkunden, Versandaufteilung, Kauf auf Rechnung, neutrale Produktmuster. **Versand nur innerhalb Deutschlands**, nicht an Packstationen, Postfächer oder DHL-Filialen. Datenannahme bis 14 Uhr zählt als Produktionstag.

## D. Automatisierungsrisiken
- Der API-Zugang hängt an Antrag, Umsatzangabe, NDA und Freigabe. Die Technik ist vor der Freigabe nicht planbar.
- Stille Autokorrekturen (RGB nach CMYK, Skalierung, gespiegelter Beschnitt) können Farbe und Format ohne Rückfrage verändern. Mit dem Profi-Check gibt es keine Autokorrektur mehr, dafür eine Ablehnung.
- Fehlerfälle laufen über E-Mail, manuelle Druckfreigabe und erneuten Upload. Ob es dafür ein maschinenlesbares Status-Feedback gibt, ist unbekannt.
- Konturaufkleber: Name und Aufbau der Schneidkontur sind offen. Eine falsche Kontur macht den Auftrag nicht produzierbar.
- Weißdruck braucht einen separaten Vektor-Sonderfarbkanal. Reine KI-Rasterbilder reichen dafür nicht.

## Was heißt das für die Pipeline
- Higgsfield-Bilder (RGB) selbst nach **FOGRA51** (gestrichen, Folie, Werbetechnik) oder **FOGRA52** (ungestrichen) konvertieren. Farbauftrag auf höchstens 300 % begrenzen, Schwarz nur als 100 % K.
- Exportprofil pro Produkt: **PDF/X-4**, offenes Endformat + 2 mm (Werbetechnik 3 mm) Beschnitt, 3 mm Sicherheitsabstand, Bilder ≥ 250 dpi im Endformat (Werbetechnik 72–150 dpi). Schriften einbetten, keine Ebenen.
- Upscaling vor dem Layout einplanen. Ein 250-dpi-A4-Flyer mit Beschnitt braucht etwa 2.110×2.965 px (214×301 mm).
- Der Weißdruck-Kanal „Weißdruck“ muss als Vektormaske erzeugt werden: 100 %, Überdrucken, Linien ≥ 2,82 mm. Erst danach transparente Sticker anbieten.
- Konturaufkleber erst freischalten, wenn Datenblatt und Konturregel vom Service vorliegen. Standardformen (rund, oval, eckig) brauchen keine Kontur.
- Dateinamen ASCII-only, eine Datei pro Auftrag, Mehrseiter als 01.pdf, 02.pdf. Weil der Upload nur einmal pro Auftrag geht, vorher selbst preflighten.
- Die API früh beantragen (Umsatzangabe, NDA). Bis dahin ist nur der manuelle Shop-Weg möglich, über ein Reseller-Konto mit neutralem Versand innerhalb Deutschlands.

## Heute first-hand gelesene URLs
- https://www.saxoprint.de/service-und-hilfe/druckdatenanleitung
- https://www.saxoprint.de/service-und-hilfe/datencheck
- https://www.saxoprint.de/service-und-hilfe/sonderfarben
- https://www.saxoprint.de/schnittstelle
- https://www.saxoprint.de/werbebedarf/aufkleber
- https://www.saxoprint.de/werbebedarf/aufkleber/aufkleber-drucken?c=800-0LQ0MU01E0MW01w0N403J03e (Konfigurator, eingebettete Shop-Texte)
- https://www.saxoprint.de/service-und-hilfe/fehlerdiagnose/farben
- https://www.saxoprint.de/service-und-hilfe/fehlerdiagnose/sonstiges
- https://www.saxoprint.de/druckvorlagen
- https://www.saxoprint.de/werbebedarf/flyer

Nicht erreichbar (Netzwerk-Policy): api.saxoprint.de (Druckdatenanleitung-PDF, Checkliste, Datenblätter, Vorlagen).

## Gegenprüfung
Stand 27.11.2026. Geprüft wurden alle First-hand-Werte des Review-Bundles; neu gelesen wurde die Produktseite „Konturaufkleber drucken“.

Geändert:
- **PDF-Standard: Widerspruch bleibt.** Die Druckdatenanleitung sagt „vorzugsweise PDF (PDF/X-4 bzw. PDF 1.6)“. Ein Fehlermeldungstext, der in jede Seite eingebettet ist, nennt dagegen „von uns akzeptierten Dateiformat (PDF 1.4, JPEG, TIFF)“. Arbeitsannahme: PDF/X-4 nach der Anleitung. Vor dem Livegang mit dem Staging-Key bzw. beim Service prüfen, ob PDF 1.6 angenommen wird. Die herunterladbare Druckdatenanleitung (PDF auf api.saxoprint.de) wurde nicht abgerufen, weil dieser Host bisher nicht genutzt wurde.
- **Konturaufkleber:** Die Produktseite nennt **keine** Konturfarbe und keinen Konturaufbau. Sie verweist nur auf kostenlose Vorlagen (InDesign, Acrobat, Illustrator) „mit bereits eingezeichneten Schnitträndern“ und auf ein Datenblatt. Die Kontur bleibt **unbekannt**. „DieCutting“ ist nur für Verpackungen und Stanzprodukte belegt.
- **Aufkleber Standardformen** (2 mm Beschnitt, 3 mm Abstand, 250 dpi) und **Roll-ups** (3 mm, 72 dpi): Das sind Allgemeinwerte der Anleitung, nicht produktspezifisch belegt. Das ist jetzt vermerkt.

Bestätigt: CMYK und Graustufen, PSO Coated v3 (FOGRA51) für gestrichene Papiere und Werbetechnik, PSO Uncoated v3 (FOGRA52) für ungestrichene Papiere, automatische RGB-/Sonderfarben-Konvertierung, 250/1.200/150/72 dpi, Beschnitt-Tabelle (Standard 2 mm, Werbetechnik 3 mm usw.), Abstände (3 mm, Wire-O 20 mm, Falz 5 mm), 300 % Farbauftrag, Weißdruck-Regel (Kanal „Weißdruck“, 100 %, Überdrucken, mindestens 8 pt / 6 pt), Partner-API mit Staging-Key, Zugangsvoraussetzungen.

Offen: PDF-Version, Konturregel für Konturaufkleber, produktspezifischer Beschnitt für Aufkleber und Roll-ups, Widerspruch zur Weißdruck-Mindestlinie (8 pt gegen 0,25 mm), API-Details erst nach NDA.
