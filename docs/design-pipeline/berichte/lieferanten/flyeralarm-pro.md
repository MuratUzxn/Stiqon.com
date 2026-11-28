# FLYERALARM PRO – Druckdaten & Reseller-API (Stand 2026-11-27)

Evidenz: F = heute auf FLYERALARM-Seiten gelesen, W = Websuche, K = Vorwissen. Belegzitate stehen in `flyeralarm-pro.profile.json`.

## A. Druckdaten-Anforderungen

**Allgemein**
- **Dateiformate:** PDF, TIF, JPG (F, Leitfaden 2020). JPG/TIF werden „gegen einen Aufpreis“ konvertiert (F). Über die API gehen PDF oder ZIP mit PDF/JPEG/TIFF (F, Swagger).
- **PDF-Standard: widersprüchlich.** Der Leitfaden 2020 verlangt PDF/X-3:2002 mit PDF 1.3, ohne Transparenzen und ohne Ebenen, Schriften in Pfade (F). Der FLYERALARM-Blog empfiehlt dagegen seit einem Update 2025 PDF/X-4 mit ISO Coated v2 (W). Das Datenblatt für Freiform-Aufkleber sagt „Als Dateiformat ist nur ein PDF/X-4 möglich“ (F). Empfehlung: PDF/X-4 mit Output-Intent erzeugen und je Produkt Datenblatt bzw. API `allowed_format` prüfen.
- **Farbmodus:** CMYK bei 4/0 und 4/4 (F). Reine Sonderfarbprodukte (z. B. Werbeartikel) dürfen kein CMYK enthalten; CMYK-Reste lösen im Datencheck eine Fehlermeldung aus (F).
- **ICC-Profile:** Offset-Produkte nutzen ISO Coated v2 300 % (ECI), Werbetechnik und Werbeartikel ISO Coated v2 (ECI) (F, Checkliste 2026).
- **RGB:** wird automatisch nach CMYK umgewandelt, leuchtende Farben können dunkler oder blasser werden (F).
- **Auflösung:** 300 bis 356 ppi, Großformat ab DIN A2 100 bis 150 ppi (F).
- **Farbauftrag:** maximal 300 %, mindestens 10 % (F). Texte in reinem Schwarz 0/0/0/100 (F).
- **Linien:** positiv mindestens 0,25 pt (0,09 mm), negativ mindestens 0,5 pt (0,18 mm) (F).
- **Schrift:** nicht unter 6 pt (F). Für Veredelungen gelten höhere Werte, z. B. partieller UV-Lack 12 pt, Heißfolie 14 pt (F, Leitfaden 2020).
- **Schriften:** im Leitfaden 2020 alle in Pfade konvertieren (F). Ob das unter PDF/X-4 noch gilt, ist unbekannt.
- **Sonderfarben:** HKS/Pantone nur nach Datenblatt, mit exakt benanntem Farbfeld (F). „Überdrucken“ nur, wenn das Datenblatt es verlangt (F). Weiße Elemente werden nur gedruckt, wenn sie nicht auf „Überdrucken“ stehen (F).
- **Beschnitt und Sicherheitsabstand:** je Produkt laut Datenblatt (F). Gelesene Werte: Plakat A4 und Block A4 1 mm Beschnitt und 6 mm Sicherheitsabstand, Flyer DL mit Visitenkarte 1 mm und 3 mm (F). Rahmen und Outlines müssen mindestens 4 mm breit sein (F).
- **Platzierung:** Daten werden zentriert platziert, eine TrimBox wird berücksichtigt (F). Hoch- und Querformat dürfen nicht gemischt werden, keine Seitendrehung im PDF (F). Druckmarken legt man nicht an (W).
- **Seiten:** Seiten in chronologischer Reihenfolge ab Titel (F). Leere Rückseiten nicht weglassen (F, API-Beispiel). Haben Vorder- und Rückseite unterschiedliche Farbigkeit, braucht jede Seite eine eigene PDF (F, Blog).
- **Max. Dateigröße:** unbekannt. Beim Reseller-Support erfragen.

**Aufkleber/Etiketten (Freiform, A4-Bogen, Datenblatt `etikett_bogen_cut.pdf`, F)**
- Bogen 21,0 × 29,7 cm, Motivbereich 20,6 × 29,3 cm. Randabstand 2 mm; Sicherheitsabstand zum Motivbereich 2 mm oben, rechts und links, 7 mm unten, weil eine Steuermarke von 10 × 9 mm automatisch aufgedruckt wird.
- **Schneidekontur:**
  - separate Ebene, Volltonfarbe 100 % Magenta mit dem Namen **„Cutkontur“**, Überdrucken EIN
  - Linienstärke 0,1 mm (0,28 pt), nur geschlossene Pfade, Kontur mittig ausgerichtet, möglichst wenige Ankerpunkte
  - nicht im Beschnitt anlegen
  - Abstand zwischen Konturen mindestens 2 mm (an anderer Stelle 3 mm, also 3 mm einhalten)
  - Eckenradius 3 mm, Zacken und Wellen mindestens 3 mm hoch
- Gestaltung mindestens 2 mm über die Kontur hinaus anlegen. Texte mindestens 2 mm innerhalb der Kontur. Für feine Motive und Texte ist der Konturschnitt ungeeignet.
- Nur PDF/X-4. Office- und Bildbearbeitungsprogramme sind ungeeignet, es braucht Vektor-Sonderfarben.
- Andere Produkte verwenden andere Regeln: Magnetfolie z. B. „CutContour“ und 4–5 mm Sicherheitsabstand (W). Für Standardformen wie rund oder eckig ist unbekannt, ob eine Kontur geliefert werden muss.
- **Weiß:** Transparente Etiketten und Aufkleber lassen sich nicht ohne Weiteres weiß bedrucken (F). Dafür gibt es das Produkt „Etiketten auf Rolle mit Zusatzfarbe Weiß“, bei dem Weiß als Sonderfarbe im 5. Farbkanal angelegt wird (F). In anderen Datenblättern heißt das Farbfeld „WHITE“ mit 100 % Tonwert (W). Das Rollen-Datenblatt habe ich nicht gelesen.

**Flyer:** Gelesen habe ich nur das Sonderprodukt Flyer DIN lang mit ausgestanzter Visitenkarte (F): Endformat 210 × 98 mm, Datenformat 212 × 100 mm, 1 mm Beschnitt, 3 mm Sicherheitsabstand, auch zur Perforation. Für Standard-Flyer A6 bis A4 liegt kein Datenblatt first-hand vor.
**Visitenkarten:** 1 mm Beschnitt (nur W).
**Plakat A4:** 21,2 × 29,9 cm Datenformat, 1 mm Beschnitt, 6 mm Sicherheitsabstand (F, Datenblatt von 2015).
**Banner/Roll-up:** nicht geprüft.
**Datenblätter:** Die Datenblätter liegen als PDF unter `https://www.flyeralarm.com/sheets/de/<name>.pdf` und sind im Produktkonfigurator verlinkt. Die Shopseiten werden per JavaScript gerendert, deshalb waren die Datenblatt-Namen nicht automatisch auslesbar.

## B. Datencheck
- Die Reseller-API bietet einen „Vollständigen Druckdatencheck“ (F). Vor der Bestellung ist `POST /{country}/v1/orders/check_print_data` mit `print_data_url` und `quantity_id` Pflicht („must be done before submitting an order“, F). Das Ergebnis kommt über `GET /{country}/v1/orders/print_data_checks/{print_data_check_id}` mit `order_status_id`, `order_status` und `reasons`, z. B. „Page 1: Incorrect file format.“ (F).
- Nach dem Upload liefert `GET .../orders/{order_id}/print_data/status` die Felder `order_upload_status` und `reasons` (F).
- **Automatische Korrekturen:** RGB→CMYK und zentrierte Platzierung (F). Aufträge mit hohem Wert oder komplexen Daten prüft der Graphical Support zusätzlich proaktiv (F, Blog). Weitere Autokorrekturen sind nicht belegt.
- **Ablehnung:** belegt sind falsches Dateiformat (API-Beispiel) und CMYK in Sonderfarbprodukten (F). Eine vollständige Liste ist unbekannt.
- **Kostenpflichtig:** JPG/TIF-Konvertierung (F). Ob es einen Basis- oder Profi-Datencheck gibt und was er kostet, ließ sich nicht belegen; beim Support erfragen.

## C. Reseller-Programm & API
- **API existiert** (F): REST, OpenAPI 3.0.1 unter `https://resellerapi.flyeralarm.com/docs/v1/swagger.json`, nur ein Production-Server.
- **Zugang** (F): Reseller-Vertrag; mittelfristig mindestens 2.500 EUR netto Umsatz pro Monat; ein Testzugang ist möglich; Abrechnung per monatlicher Sammelrechnung, bonitätsabhängig.
- **Auth** (F): `POST /user/login` mit `username` und `password` liefert ein JWT (`token`, `expiration`), danach Bearer-Header.
- **Katalog** (F): `GET /{country}/v1/product_groups/list`, `/product_groups/{id}`, `/product_variants/{id}`, `/product_variants/quantity_id/{quantity_id}`, `/product_options/{quantity_id}/{shipping_type_id}`. Dazu `POST /product_attributes/{product_group_id}` als Konfigurator und Custom-Size-Preise über `/product_variants/{id}/prices` bzw. `/product_quantities/{id}/prices`. `country` ist z. B. `de`.
- **Bestellung** (F): `POST /{country}/v1/orders` mit
  - `quantity_id`
  - `shipping_type` (standard, express oder overnight)
  - `payment` (invoice, prepayment oder debit)
  - `addresses` (`delivery` ist Pflicht, dazu `invoice` und `sender`)
  - optional `reference_text`, `custom_size` und `options`
  Hinweis der Doku: Mit `prepayment` angelegte Bestellungen gelten als Testbestellungen und werden nicht verarbeitet.
- **Druckdaten-Übergabe** (F): ein eigener Schritt nach Anlage der Bestellung, über `POST /{country}/v1/orders/{order_id}/print_data`. Es gibt zwei Wege:
  - JSON `{"print_data_url": "..."}`, FLYERALARM holt die Datei dann per http- oder ftp-URL ab
  - `multipart/form-data` mit dem Feld `print_data`, das eine PDF- oder ZIP-Datei enthält
  Vorher liefern `GET .../upload_allowed_status` und `GET .../print_data/allowed_format` die Regeln: `numberOfPages` für eine einzelne PDF, oder ein ZIP mit Dateien nach dem Muster vier Ziffern plus Endung (`0001.pdf`, `0002.tif` …).
- **Proof/Vorschau:** keine Proof- oder Vorschau-Endpunkte in der Spezifikation; unbekannt.
- **Status** (F): `GET .../orders/{order_id}`, `/status` und `/orders/statuses`. In der Spezifikation stehen **keine Webhooks**, Status gibt es also nur per Polling.
- **Sandbox:** keine. Tests laufen über Bestellungen mit `prepayment` oder über den Testzugang.
- **Neutraler Versand** (F): Verpackung und Lieferschein sind neutral. Ohne `sender` druckt FLYERALARM eine eigene Standardadresse aufs Label. Ein eigener Lieferschein lässt sich per `POST .../delivery_note` hochladen. Versand innerhalb Europas und in die Schweiz (F). Für Selbstabholung mit eigenem Spediteur gibt es das Schema `SelfShipper` (UPS, DHL, DPD).

## D. Automatisierungsrisiken
- Unklar ist, ob PDF/X-3 oder PDF/X-4 gilt. Freiform-Aufkleber verlangen zwingend PDF/X-4.
- Konturschnitt braucht eine Vektor-Sonderfarbe „Cutkontur“. Higgsfield liefert Raster, also muss die Pipeline die Kontur vektorisieren und glätten (Radius 3 mm, Mindestabstände) und mit einer Überfüllung von 2 mm versehen.
- Weiß auf transparenter Folie gibt es nur bei einem bestimmten Produkt, mit eigenem Sonderfarbkanal.
- Der Datencheck ist vor jeder Bestellung Pflicht und läuft asynchron per Polling ohne Webhooks.
- Seiten- und ZIP-Struktur unterscheiden sich je Produkt und müssen je Auftrag abgefragt werden.
- Ohne Sandbox laufen Tests gegen die Produktion.
- Der Mindestumsatz kann den Zugang gefährden.
- Datenblätter sind teils von 2015, und es gibt keine API für Datenblatt-Maße. Beschnitt und Sicherheitsabstand müssen deshalb manuell gepflegt oder aus dem Datenblatt-PDF geparst werden.

## Was heißt das für die Pipeline
- Exporter: PDF/X-4 mit Output-Intent ISO Coated v2 300 % (ECI) für Offset und ISO Coated v2 (ECI) für Werbetechnik; CMYK; 300 ppi bezogen auf das Datenformat; Text in 100 K; Farbauftrag höchstens 300 %.
- Eine Tabelle pro `quantity_id` bzw. Produkt mit Datenformat, Beschnitt, Sicherheitsabstand und Seitenzahl, gepflegt aus dem jeweiligen Datenblatt. Vor jedem Upload `allowed_format` abgleichen.
- Für Sticker die Kontur automatisch aus der Alpha-Maske erzeugen: als Pfad glätten, Spot „Cutkontur“ 100 M, 0,1 mm, Überdrucken, eigene Ebene; Motiv 2 mm darüber hinaus anlegen, Text 2 mm nach innen.
- Ablauf: Login (JWT) → Preis und Optionen → `check_print_data` mit einer öffentlich erreichbaren, signierten URL zum PDF → Status pollen → bei OK Bestellung anlegen → `print_data` mit derselben URL → Status pollen.
- Bei jeder Bestellung immer `sender` mit Shop-Adresse und `reference_text` mit WooCommerce-Bestellnummer mitsenden.
- `reasons` aus dem Datencheck in WooCommerce speichern; Bestellungen bei einem Fehler anhalten statt automatisch erneut senden.
- Vor dem Go-live beim Reseller-Support klären: Testzugang, maximale Dateigröße, PDF/X-Version, ob Standardformen eine Kontur brauchen, Kosten des Datenchecks.

## URLs (first-hand gelesen)
- https://resellerapi.flyeralarm.com/docs/index.html
- https://resellerapi.flyeralarm.com/docs/v1/swagger.json
- https://flyeralarm.pro/
- https://flyeralarm.pro/reseller-api/
- https://flyeralarm.pro/faq/
- https://www.flyeralarm.com/blog/wp-content/uploads/Die_Flyeralarm_Checkliste_zur_Erstellung_von_Druckdaten_2026.pdf
- https://www.flyeralarm.com/images/upload/content/_pdf/de/de/flyeralarm-druckdaten-de-050520.pdf
- https://www.flyeralarm.com/sheets/de/etikett_bogen_cut.pdf
- https://www.flyeralarm.com/sheets/de/a4_mass.pdf
- https://www.flyeralarm.com/sheets/de/flyer_dl_vk_mass.pdf
- https://www.flyeralarm.com/sheets/de/plakat_a4_mass.pdf
- https://www.flyeralarm.com/blog/de/serie-sonderfarben-teil-3-sonderfarbdruck/
- https://www.flyeralarm.com/blog/de/kundenservice-die-haeufigsten-fehler-beim-erstellen-von-druckdaten/
- (404: https://www.flyeralarm.com/de/content/druckdaten)

## Gegenprüfung
Stand 27.11.2026. Geprüft wurden alle First-hand-Werte des Review-Bundles; drei neue Seiten wurden gelesen (Datenblätter flyer_a6_mass.pdf und flyer_a5_mass.pdf, Blog-Tutorial „Druckfähiges PDF mit InDesign“).

Geändert:
- **PDF-Standard:** Der Widerspruch ist aufgelöst. Das Blog-Tutorial sagt first-hand: „Update 2025: Inzwischen empfehlen wir Ihnen, immer PDF/X-4 zu verwenden.“ Das Freiform-Datenblatt verlangt nur PDF/X-4, Textil ebenfalls. Der Leitfaden 2020 (PDF/X-3:2002, PDF 1.3) ist älter, wurde aber nicht ausdrücklich zurückgezogen. Die Evidenz steigt von W auf F. Arbeitsannahme: PDF/X-4, und je Auftrag `allowed_format` abfragen.
- **Standard-Flyer A6/A5:** laut Datenblatt (Stand 10.03.2015) **1 mm Beschnitt und 4 mm Sicherheitsabstand**, als neuer Produkteintrag. Der bisherige Hinweis „typisch 1 mm / 6 mm“ war vom A4-Plakat und vom A4-Block übertragen und ist ersetzt.
- **safe_mm allgemein:** Die 4-mm-Regel betrifft die Stärke von Rahmen und Outlines, nicht den Sicherheitsabstand. Das ist korrigiert.
- **Weiß bei Rollenetiketten:** Der Farbkanalname „white“ stammt aus der Kugelschreiber-Antwort derselben FAQ (wrong-scope). Er ist entfernt; der Farbfeldname für Etiketten ist unbekannt.
- **Testbestellungen:** Der Swagger-Satz „Use payment: prepayment for test orders only, otherwise they won't be processed!“ ist mehrdeutig. Dass prepayment-Aufträge sicher nicht produziert werden, ist **nicht** belegt.

Bestätigt: CMYK-Regel, ICC-Profile (ISO Coated v2 300 % für Offset, ISO Coated v2 für Werbetechnik), RGB-Konvertierung, 300–356 ppi (ab A2 100–150 ppi), Farbauftrag 300 %/10 %, Sonderfarben- und Überdrucken-Regeln, die komplette Cutkontur-Spezifikation des Freiform-Datenblatts, DL-Flyer mit Visitenkarte (1 mm / 3 mm), API-Existenz, Zugang (2.500 EUR Mindestumsatz), print_data-Übergabe (URL oder Multipart), Status nur per Polling.

Offen:
- Flyer **DIN A4**: Datenblatt nicht gelesen. Plakat und Block A4 haben 6 mm Sicherheitsabstand, A6/A5 haben 4 mm.
- Ob der Sicherheitsabstand (z) vom End- oder vom Datenformat gemessen wird. Konservativ ab Endformat rechnen.
- Datenblatt „Etiketten auf Rolle mit Zusatzfarbe Weiß“ ist nicht gelesen.
- Ob Schriften unter PDF/X-4 noch in Pfade müssen. Regel aus dem Leitfaden 2020.
- Verhalten von prepayment-Testaufträgen schriftlich beim Service klären.
