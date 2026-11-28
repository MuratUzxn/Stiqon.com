# Print.com – Druckdaten, Datencheck, API (Stand 2026-11-27)

Evidenz: F = heute auf Print.com-Seiten gelesen, W = Websuche, K = eigenes Wissen. Zitate und Dateipfade stehen in `print-com.profile.json`.

Wichtig vorab: Print.com veröffentlicht keine zentrale Seite mit Anlieferspezifikationen. Die verbindlichen Vorgaben je Produkt stehen in der Webapp (Login), in herunterladbaren Templates und Werkzeichnungen und in der API (`POST /products/{sku}/workfile`, `POST /printjobs/templates` mit `bleed` und `safety` in mm). Die Kennisbank-Artikel sind allgemein gehalten, widersprechen sich teils und enthalten Fehler (Banner-Artikel: 3 × 2 m bei 150 dpi = „1800 × 1200 px“). Nur Richtwert.

## A. Druckdaten-Anforderungen

**Allgemein**
- **Dateiformat:** PDF. Die Kennisbank empfiehlt PDF/X-1a oder PDF/X-4 (F). Die Preflight-API prüft mit den PitStop-Profilen `GWG_SheetCmyk_2015 CMYK` und `… CMYK + RGB` (F).
- **Farbmodus:** CMYK. Pantone nur, wenn bestellt (F).
- **ICC-Profil:** Ein verbindliches Profil je Papierklasse wurde nicht gefunden. Die Kennisbank nennt einmal „ISO Coated v2 300% oder PSO Coated v3“ und einmal „ISO Coated v2 (ECI) oder Fogra39 für gestrichenes Papier, ISO Uncoated für ungestrichenes“ (F). Das verbindliche Profil muss beim Support erfragt werden.
- **RGB:** Wird laut Kennisbank automatisch nach CMYK konvertiert, ohne dass man die Konvertierung steuern kann (F). Ob der Datencheck RGB ablehnt oder nur warnt, ist unbekannt.
- **Auflösung:** 300 dpi im Endformat (F). Für Großformat nennt die Kennisbank 150 dpi, für große Outdoor-Banner 100–150 dpi und für Roll-ups 300 dpi (F, Artikel unzuverlässig).
- **Beschnitt:** Standard 3 mm (Folder/Flyer, Doming-Sticker; F). Ausnahmen: Fußmatte 5 mm, Re-board 10 mm (eine Platte mit 1000 × 1000 mm wird als 1020 × 1020 mm angeliefert; F).
- **Sicherheitsabstand:** 3 mm (Folder; F), Kennisbank teils „3 tot 5 mm“.
- **Schriften:** einbetten oder in Pfade wandeln (F).
- **Mindestschriftgröße und Mindest-Linienstärke allgemein:** unbekannt. Bekannte Einzelwerte: Folienveredelung bei Klebebroschur mindestens 15 pt Text, Spot-UV/Folie mindestens 0,25 mm Linie, Schnitt- und Rilllinien 0,25 pt (F).
- **Maximaler Farbauftrag:** Nur allgemein „doorgaans 300% tot 320%“ (W/Kennisbank). Kein Print.com-Grenzwert gefunden.
- **Transparenz:** Reduziert oder verarbeitet anliefern (F).
- **Seiten:** Ein PDF. Bei Broschüren der Inhalt als Einzelseiten und der Umschlag als Druckbogen, keine Schnittmarken (F). Mehrere Motive in einer Order über `designs[]` (F).
- **Maximale Dateigröße:** 1 GB beim Webapp-Upload, seit 16.11.2025 (vorher 500 MB; F). Einzelne Produktseiten nennen noch 500 MB (W). Für Dateien, die per API über `fileUrl` kommen, ist kein Limit dokumentiert.

**Sonderfarben-Ebenen** (Volltonfarbe auf Überdrucken, eigene Ebene; F, Folder-FAQ): `White` oben, darunter gleiche Form C0 M0 Y0 K0 ausgespart, ≥1 mm zu Schnitt/Rillung, Vektor ohne Schnittmasken; `Thrucut`, `Kisscut`, `Creasing`, `Perforation`, `Drilling` mit 0,25 pt; `Numbering` (plus Excel per Mail an files@print.com); `Scodix Spot`/`Scodix Foil …` für Veredelung.

**Sticker und Etiketten**
- **Stickerbogen:** Jeder Sticker ist eine Konturlinie in der Volltonfarbe **„Kiss Cut“, CMYK 0/100/0/0**. Die Anzahl der Konturlinien wird im Formular angegeben. Die Datei muss vor 18:00 Uhr hochgeladen sein, sonst gilt der Liefertermin nicht (F).
- **Doming-Sticker, eigene Form:** Konturlinie als Volltonfarbe **„CutContour“**. Bei transparentem Material mit 5/0 Druck kommt die Weißlage als Volltonfarbe **„White“**. Beschnitt 3 mm. Templates gibt es in der Bestell-App (F).
- **Etiketten auf Rolle und kleine Aufkleber:** Standardformen (rechteckig, rund, oval) brauchen keine eigene Kontur. Für eine eigene Form ist „1 Konturlinie“ nötig. 5/0 CMYK + Weiß ist wählbar (F).
- **Holografische Sticker:** Schnittlinien als Volltonfarbe „volgens de aanleverspecificaties“ (der Name wird nicht genannt). Weiß setzt man selbst per Volltonfarbe (F).
- **Schnittfarbname** variiert je Produkt (Kiss Cut, CutContour, Thrucut/Kisscut): immer aus dem SKU-Template übernehmen.

**Flyer und Falzflyer:** PDF, 3 mm Beschnitt, CMYK, 300 dpi, 3 mm Sicherheitsabstand. Werkzeichnungen mit Falz- und Schnittlinien gibt es in der App. Die Standardrillung setzt Print.com selbst, nur variable Rillungen werden markiert (F). Die deutsche Flyer-Seite selbst enthält keine Datenspezifikation (F).

**Visitenkarten, Banner:** Die Produktseiten enthalten keine Datenspezifikation (F). Für Banner gibt es Saum- und Tunneloptionen (Tunnelsaum 4–12 cm); ob eine Saumzugabe in der Datei nötig ist, ist unbekannt. Klärung über das Template.

## B. Datencheck
- **Automatik:** Print.com führt nach dem Upload eine „(automatic) check“ gegen die Anlieferspezifikationen durch. Design, Text, Farbdeckung, Position, Ausschnitte und Überdrucken werden ausdrücklich nicht geprüft (AGB; F). Die Webapp zeigt sofort Feedback (Kennisbank).
- **Automatische Korrekturen:** Über `fileOptions.autoscale` (`all|up|down|none`; ohne Angabe gilt `down`) wird die Datei skaliert (F). Die Preflight-API wendet Korrekturen „when possible“ an und liefert ein geändertes PDF (F). Welche Korrekturen genau, ist unbekannt.
- **Ablehnung:** Status `DESIGNREJECTED` (durch einen Print.com-Dateiprüfer), `DESIGNWARNING`, `MANUALCHECK` (F). Konkrete Kriterien sind nicht dokumentiert.
- **Kostenpflichtig:** Service-Level „Extra“ (oder „Zeker“) mit zusätzlicher manueller Dateiprüfung (F). Der Preis ist unbekannt. Laut Lieferseite ist das Level „Premium“ in Deutschland und Österreich noch im Aufbau (F).
- **Rückmeldung:** Über den Order-Status (API und Webapp). Bei Premium File Check kommt eine E-Mail (F).

## C. Reseller und API
- **API:** Vorhanden (F). Es gibt drei REST-APIs:
  - Print.com API: `https://api.print.com`, Staging `https://api.stg.print.com`.
  - Platform API: `https://platform.print.com`.
  - Partners API: nur für Produzenten.
- **Dokumentation:** https://developer.print.com/llms.txt (Seiten als .md).
- **Authentifizierung:** Header `authorization: PrintApiKey <key>`. Den Key erzeugt man unter app.print.com/account. Alternativ `Bearer` aus `POST /login`, 168 h gültig (F).
- **Zugang:** Ein aktives Kundenkonto ist nötig. Die Registrierung ist kostenlos, wird aber geprüft („nur für Druckprofis“). Ein Staging-Konto muss separat per Mail angefragt werden. Support nur auf Englisch (F).
- **Katalog:** `GET /products`, `GET /products/{sku}`, `/products/{sku}/price`, `POST /products/{sku}/workfile` (Workfile-ZIP-URLs); Platform API: `/products/batch/specs`, Batch-Preise, `POST /printjobs/templates` (Seitenmaße, Overlays, `bleed`, `safety` in mm).
- **Order:** `POST /orders` (F). Der Body enthält `items[]` mit `sku`, `options`, `fileUrl` **oder** `designs[]`, `fileOptions`, `approveDesign`, `senderAddress`, `shipments[]`. Dazu kommen `billingAddress` und optional `webhookUrl`.
- **Übergabe der Druckdatei:** Print.com **holt die Datei über eine URL ab**. Möglich ist `items[].fileUrl` (PDF, http/https) oder `designs: [{fileUrl, copies}]` mit einer Datei je Motiv; beides schließt sich gegenseitig aus. Beim Order gibt es keinen Multipart-Upload. `fileOptions.isProduction` ist laut Schema Pflicht. Achtung: „Order will still be placed if this url wasn't available“ (F).
- **Proof und Vorschau:** `POST /pdf/preflight` (JSON-Location oder Multipart-Upload; GWG-Profil plus Template mit Maßen je Seite, Toleranz 0,1 mm), Ergebnis über `GET /pdf/preflight/{id}`, Bitmap über `/pdf/preview/{file}`. Mit `approveDesign=false` ist eine explizite Freigabe im Studio nötig (F).
- **Webhooks:** `webhookUrl` im Order-Root, Event `ORDER_STATUS_CHANGED`. Gemeldet werden nur die Bestellung selbst, `ACCEPTEDBYSUPPLIER` und `SHIPPED` (F). Tracking über `GET /orders/{n}?fields=item.trackingUrl`.
- **Sandbox:** Staging mit eigenem Konto, alle 24 h zurückgesetzt. Orders dort werden weder produziert noch berechnet (F).
- **Neutraler Versand:** Neutrale weiße Kartons, neutrales Track & Trace, frei wählbare `senderAddress` (F). Das eigene Logo auf dem Packzettel kommt über `senderAddress.stickySlipImageUrl`; das ist kostenlos und wird schwarz-weiß gedruckt (F).
- **Lieferung nach DE:** deutscher Shop vorhanden; Länder über `/shipping/shippable-countries`.

## D. Automatisierungsrisiken
1. Order wird auch bei nicht erreichbarer `fileUrl` angelegt; URL-Verfügbarkeit und Status selbst prüfen.
2. `DESIGNREJECTED`/`MANUALCHECK` kommen nicht per Webhook, nur per Polling von `GET /orders/{orderNumber}`.
3. ICC-Profil nicht verbindlich dokumentiert, Kennisbank widersprüchlich.
4. Schnittfarbname und Beschnitt hängen vom Produkt ab; feste Werte im Exporter sind gefährlich.
5. Allgemeine Mindestschrift, Mindestlinie, max. Farbauftrag fehlen; Dateilimit nur für Webapp-Upload belegt.
6. Nummerierung/Personalisierung nur per Excel-Mail.
7. Production-Orders sind echt und werden berechnet; Testkonto separat beantragen.

## Was heißt das für die Pipeline
- Je Produktkonfiguration vor dem Export `POST /printjobs/templates` abfragen (Endformat, `bleed`, `safety`, Overlays) und zusätzlich `/products/{sku}/workfile` holen. Daraus die Zeichenfläche bauen, statt feste 3 mm einzutragen.
- Higgsfield-Raster (RGB) in einem eigenen Prepress-Schritt verarbeiten: auf Endformat plus Beschnitt bei mindestens 300 dpi bringen (Großformat ab 150 dpi), nach CMYK konvertieren (Profil vorher mit dem Support klären, vorläufig ISO Coated v2 / PSO Coated v3) und als PDF/X-4 ausgeben.
- Sticker: die Kontur als Vektorpfad (0,25 pt, Überdrucken, eigene Ebene) in der Volltonfarbe ablegen, die das SKU-Template verlangt (Kiss Cut 0/100/0/0, CutContour oder Thrucut). Bei transparentem oder metallischem Material eine Ebene `White` als Vektor anlegen.
- Jedes PDF vor der Order mit `POST /pdf/preflight` (GWG_SheetCmyk_2015 CMYK) und dem passenden Template prüfen. Nur fehlerfreie Ergebnisse weitergeben, das geänderte PDF sichten.
- Das PDF unter einer stabilen, öffentlich abrufbaren HTTPS-URL bereitstellen (z. B. signierte URL mit langer Laufzeit), `fileUrl` beziehungsweise `designs[]` übergeben, `isProduction: true`, `autoscale: "none"`.
- Nach der Order den Status regelmäßig abfragen (Design-Status), zusätzlich `webhookUrl` für Produktion und Versand. `DESIGNREJECTED` als Fehlerfall an WooCommerce melden.
- White-Label: `senderAddress` mit Shopdaten und `stickySlipImageUrl` mit dem Shoplogo.
- Zuerst Staging-Konto beantragen und alle Produktfamilien dort testen.

## Selbst gelesene URLs
- API (https://developer.print.com/): llms.txt; reference/ introduction.md, accounts-authentication.md, orders.md, placeorder.md, order-status.md, getproductworkfiles.md, generate-template.md, pdf-preflight-1.md, pdf-preview-1.md, batch-get-product-specifications.md, custom-logo-on-sticky-packing-slip.md
- https://www.print.com/de/: stickerbogen-drucken/, etiketten-auf-rolle/, aufkleber-klein/, flyer/, lieferung/, reseller/
- https://www.print.com/nl/: stickervellen/, holografische-stickers/, doming-stickers/, folders-drukken/, brochure-gelijmd/, re-board/, deurmat-bedrukken/, spandoek-bedrukken/, product/visitekaartjes-drukken/, appdates/
- https://www.print.com/en/our-rules/
- Kennisbank https://www.print.com/nl/kennisbank/: printtechniek/hoe-controleer-ik-of-mijn-bestand-geschikt-is-om-te-laten-drukken/, printtechniek/wat-moet-ik-aanleveren-voor-een-foutloze-offsetdrukorder/, printtechniek/wat-moet-ik-aanleveren-voor-een-foutloze-digitale-drukorder.md, printtechniek/hoe-snel-kan-ik-een-bestand-van-rgb-naar-cmyk-omzetten-voor-aanlevering.md, spandoeken-banners/wat-is-de-minimale-resolutie-voor-spandoek-ontwerpen.md

## Gegenprüfung
Stand 27.11.2026, zweite Prüfung aller Erstquellen-Werte gegen den gespeicherten Seitentext. Neu gelesen: https://www.print.com/nl/kleine-stickers/ (downloads/print-com-check/001.bin).

**Geändert im Profil**
- `general.icc_profiles` (Widerspruch): Der Prüfartikel der Kennisbank nennt „ISO Coated v2 300% of PSO Coated v3“, der RGB-nach-CMYK-Artikel nennt „ISO Coated v2 (ECI) of Fogra39 voor gecoat, ISO Uncoated voor ongecoat“. Beide Texte sind allgemeine Ratgeber („die meisten Druckereien in Europa“), keine Print.com-Vorgabe. Die Evidenz ist deshalb von F auf W gesenkt. Gemeinsam ist beiden nur ISO Coated v2 (FOGRA39-Basis). Die Empfehlung oben („vorläufig ISO Coated v2 / PSO Coated v3“) stellt also zwei verschiedene Charakterisierungen (FOGRA39 bzw. FOGRA51) nebeneinander. Verbindlich bleibt die Klärung mit dem Support.
- `general.transparency` (Widerspruch): Der Prüfartikel verlangt abgeflachte Transparenzen. Die Artikel zu Offset- und Digitaldruck sagen dagegen, PDF/X-4 unterstütze Transparenzen. Die Evidenz ist auf W gesenkt.
- `general.min_dpi`: 150 dpi für Großformat sind laut Kennisbank nur „manchmal ausreichend“, also eine Empfehlung und kein Grenzwert. Die 300 dpi sind bestätigt.
- `general.bleed_mm`: Die Werte 3/5/10 mm sind bestätigt. Für Stickerbogen, kleine Sticker (de und nl geprüft), Etiketten auf Rolle, Flyer (de) und Visitenkarten steht kein Beschnitt auf der Seite, er ist nur über das Template zu erfahren.
- `products[Stickerbogen].white` und `products[Etiketten auf Rolle].white` (falscher Geltungsbereich): Die Weiß-Volltonfarbe „White“ war von Doming bzw. Folders übernommen. Belegt ist für diese Produkte nur die Option 5/0 CMYK + Weiß.
- `api.access`: „Registrierung wird geprüft“ ist nicht belegt und wurde entfernt. Belegt sind „Exklusiv für Druckprofis“ und die kostenlose Anmeldung.
- `api.sandbox`: ergänzt. Auf Staging folgt der Orderstatus nicht der Produktion, deshalb lassen sich Webhooks für Produktion und Versand dort nicht testen.

**Bestätigt**: PDF/X-1a oder X-4 (Kennisbank, allgemein formuliert), CMYK, RGB-Autokonvertierung, Sicherheitsabstand 3 mm (Folders), Volltonfarben-Namen (Creasing, Perforation, Drilling, Thrucut, Kisscut, Numbering, Scodix, 0,25 pt, Überdrucken), Stickerbogen „Kiss Cut“ 0/100/0/0, Doming 3 mm + CutContour + White, Holo-Schnittoptionen, Flyer (de) 5/0 und 5/5, API-Übergabe per fileUrl/designs[], Preflight-API (GWG_SheetCmyk_2015, Toleranz 0,1 mm), approveDesign=false nur mit Freigabe im Studio, Webhook (drei Ereignisse, event_type ORDER_STATUS_CHANGED), Staging.

**Offen**
- Verbindliches ICC-Profil je Papierklasse.
- Beschnitt von Stickerbogen und kleinen Stickern: nur über `POST /printjobs/templates` mit einem Konto.
- Name der Weiß-Volltonfarbe bei Stickerbogen, Rolle und kleinen Stickern.
- Ob die Produktion PDF/X-4 mit Live-Transparenz annimmt.
