# Printful – Druckdaten & API (Stand 2026-11-27)

Evidenz: F = heute auf Printful-Seite gelesen, W = Websuche, K = eigenes Wissen.

## A. Druckdaten-Anforderungen
- **Formate:** PNG, JPEG (F). .ai/.psd/.tiff über die API abgekündigt (F). Kein PDF/X-Workflow.
- **Farbraum:** sRGB IEC61966-2.1, *kein* CMYK anliefern; Printful rechnet RGB selbst in seinen CMYK-Prozess um (F).
- **Auflösung:** 150 DPI Richtwert, 150–300 ideal, 300 DPI für kleine, detailreiche Produkte (F). Sticker: PNG/JPEG ≥ 300 DPI (W, Support-Artikel).
- **Maximale Dateigröße:** 200 MB (F); Dateibibliothek max. 20.000 × 20.000 px (W).
- **Transparenz:** PNG mit transparentem Hintergrund wird erwartet, der Design Maker warnt bei fehlender Transparenz (F).
- **Beschnitt/Sicherheitsabstand:** Kein mm-Wert. Beschnitt steckt in der Pixelgröße der Druckdatei je Variante/Platzierung (Templates im Tab „File guidelines“ bzw. API). unbekannt in mm.
- **Druckmaße per API (F):** `GET /mockup-generator/printfiles/{id}` liefert je Produkt `available_placements` und `printfiles` mit `width`, `height`, `dpi`, `fill_mode`, `can_rotate` sowie die Zuordnung `variant_printfiles`. Beispiel T-Shirt (Produkt 71): 1800 × 2400 px, 150 dpi.
- **Seiten:** eine Datei je Platzierung (front, back, label_inside …).
- Schriften, Mindest-Schriftgröße/Linienstärke, Farbauftrag, Sonderfarben: unbekannt bzw. entfallen (Rasterdaten).

### Sticker (Kiss-Cut Stickers)
- Größen (W): 3×3 in (8×8 cm), 4×4 in (10×10 cm), 5,5×5,5 in (14×14 cm), 15×3,75 in (38×9,5 cm). Vinyl, opak (Produktbeschreibung auf der Seite gelesen, aber Seite ist JavaScript-gerendert).
- **Schnittlinie:** keine eigene Kontur anliefern. Printful schneidet entlang des Motivumrisses und lässt einen weißen Rand stehen; zwischen separat zu schneidenden Objekten mind. 0,25 in (0,64 cm) Abstand (W). Kontur also nicht steuerbar.
- Weißdruck: entfällt bei opakem Vinyl; Holographic-Sticker: Regeln unbekannt.
- Template: Produktseite „File guidelines“ / API printfiles.

### Weitere Familien
- Flyer, Visitenkarten, Banner/Roll-ups: im Sortiment nicht gefunden. Poster/Leinwände, Textil, Tassen vorhanden; Pixelmaße per API.

## B. Datenprüfung
- Design Maker prüft Auflösung/Transparenz vor der Bestellung (F); für API-Aufträge ist kein Preflight dokumentiert.
- Ablehnung: Status `failed` bei fehlenden Druckdateien, Adress- oder Zahlungsproblem (F); Hold-Status `order_put_hold`.
- Automatische Korrekturen, Kosten für Prüfung: unbekannt.
- Ergebnis: Bestellstatus + Webhooks (F).

## C. Reseller & API
- **API:** ja, REST, `https://api.printful.com/`, OpenAPI-Download (F). Rate Limit 120 Calls/Minute, Mockup-Generator niedriger (F).
- **Auth:** OAuth – Private Token (eigener Shop) oder Public App, Bearer-Header; `X-PF-Store-Id` bei Account-Token (F).
- **Katalog/Preis:** `GET /products`, `/products/{id}`, `/products/variant/{id}`, `/products/{id}/sizes`, `POST /orders/estimate-costs`, `POST /shipping/rates` (F).
- **Auftrag:** `POST /orders` erzeugt Draft; `POST /orders/{id}/confirm` oder Query `confirm=1` übergibt in die Produktion. Berechnet werden nur bestätigte Aufträge (F). `external_id` als eigene Referenz.
- **Druckdatei-Übergabe:** je Position `files[]` mit `type` (Platzierung) und `url`; Printful lädt die Datei von der URL. Alternativ `POST /files` (Dateibibliothek, ebenfalls URL-basiert, gleiche URL ⇒ gleiche Datei) und Referenz per `id` (F). Kein Multipart-Upload dokumentiert.
- **Vorschau:** Mockup Generator `POST /mockup-generator/create-task/{id}`, Ergebnis `GET /mockup-generator/task?task_key=…` (F).
- **Webhooks:** `GET/POST/DELETE /webhooks`; nur *eine* Webhook-URL pro Store; Events u. a. `package_shipped`, `order_failed`, `order_put_hold`, `stock_updated`, `product_synced` (F). Webhook Simulator vorhanden.
- **Sandbox:** keine gelesen; Drafts kosten nichts.
- **White Label:** Packing slip im Store-Branding konfigurierbar, pro Auftrag überschreibbar (`packing_slip`), `retail_price` für Lieferschein (F).
- EU-Produktion: Seite zeigt Regionen Lettland/Spanien als Länderdaten, Produktionsstandorte nicht bestätigt (unbekannt; im Dashboard je Produkt prüfen).

## D. Automatisierungsrisiken
- Kein CMYK-/PDF-Workflow: alles als sRGB-PNG in exakten Pixelmaßen je Variante.
- Druckmaße nur per API mit Token – dynamisch lesen, nicht fest codieren.
- Datei-URL muss öffentlich erreichbar sein.
- Nur eine Webhook-URL pro Store (Konflikt mit evtl. WooCommerce-Plugin).
- Sticker-Kontur automatisch, nicht steuerbar; Sticker-Details nur per Websuche belegt.

## Was heißt das für die Pipeline
Printful taugt nur für Sticker, Poster und Textil – nicht für Flyer/Visitenkarten. Der Exporter braucht einen eigenen Pfad „POD-Raster“: Higgsfield-Bild → sRGB-PNG mit Transparenz → exakt auf `width × height` aus `/mockup-generator/printfiles/{id}` skalieren (≥ 150 dpi, Sticker 300 dpi) → öffentlich hosten → `POST /orders` mit `files[{type,url}]`, zuerst als Draft, Mockup prüfen, dann `confirm`. Status per Webhook `order_failed`/`package_shipped`.

## Gelesene URLs (F)
- https://developers.printful.com/docs/
- https://www.printful.com/blog/everything-you-need-to-know-to-prepare-the-perfect-printfile
- https://www.printful.com/custom/stickers/die-cut/kiss-cut-stickers (JS-gerendert, kaum Text)
- https://www.printful.com/de/custom/wall-art/stickers (404)
- W: https://support.printful.com/hc/en-us/articles/41396541153553 (nur Suchergebnis)

## Gegenprüfung
Stand 27.11.2026, keine Änderungen nötig.

**Bestätigt**:
- Dateiformate: PNG/JPEG, kein PDF/X.
- Farbe: sRGB IEC61966-2.1, Printful wandelt selbst in seinen CMYK-Prozess.
- Auflösung: 150–300 DPI ideal, 300 DPI für kleine, detailreiche Produkte.
- Transparenz: Der Design Maker warnt, wenn der transparente Hintergrund fehlt.
- Zugang und Limits: Self-Service über das Developer Portal (Private Token oder Public App), 120 Calls pro Minute.
- Dateiübergabe: `files[{type,url}]` oder `POST /files`.
- Vorschau: Mockup Generator und printfiles-Endpoint.
- Webhooks: nur eine Webhook-URL pro Store.
- Abrechnung: Nur bestätigte Aufträge werden berechnet. Drafts dienen damit als Test, eine Sandbox gibt es nicht.

**Offen**
- Sticker-Specs (300 dpi, automatische Kontur, Abstand 0,25 in) sind nur per Websuche belegt (W).
- Beschnitt bzw. Pixelmaße je Variante gibt es nur per API mit Token.
