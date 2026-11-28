# Shirtigo (Cockpit) – Druckdaten & API (Stand 2026-11-27)

Evidenz: F = heute gelesen (v. a. https://cockpit.shirtigo.com/docs/llms-full.txt), K = eigenes Wissen.

## A. Druckdaten-Anforderungen
- **Formate:** PNG oder JPEG als Rasterbild, mind. 1000 px Breite oder Höhe (F). Kein PDF für Druckdaten.
- **Größe/Position:** wird nicht über Pixel/Beschnitt, sondern pro Druckbereich in **Millimetern** gesetzt: `width` *oder* `height` (das andere folgt aus dem Seitenverhältnis), `offset_top` (Abstand von Oberkante Druckbereich), `offset_center` (Verschiebung von der Mittellinie) (F).
- **Druckbereiche:** `front`, `back`, `sleeve-left`, `sleeve-right`, `back-inside`; je Druckbereich ein `processings`-Eintrag; Verfahren `processingmethod` z. B. dtg, dtf, embroidery, sublimation (F).
- **Auflösung:** `extract_size_and_position` nimmt die Datei als ganzen Druckbereich in 300 dpi, schneidet transparente Ränder ab und übernimmt Größe/Position aus dem sichtbaren Inhalt (für dtg, dtf, sublimation und poster) (F). Die 1000-px-Mindestprüfung ist bei `design_url` **standardmäßig ausgeschaltet** (`ignore_validation` Default true) (F).
- **Toleranz:** ±20 mm Positionierung wegen manueller Textilhandhabung; Druckbereichsmaße beziehen sich auf Größe L, bei S/M wird die max. Druckgröße evtl. automatisch reduziert (F).
- **Transparenz:** PNG-Transparenz wird genutzt (Ränder werden getrimmt) (F).
- Beschnitt/Sicherheitsabstand in mm: entfällt (Textil). Farbraum/ICC, max. Dateigröße, Schriften, Linienstärke, Sonderfarben: nicht dokumentiert gefunden → unbekannt, Support fragen.

### Produkte
- Textil (T-Shirts, Hoodies, Kinder, Taschen), Poster-Verfahren erwähnt (F). Base products per `GET /base-products` oder Katalog-XLSX (Login).
- **Sticker:** kein Sticker-Druckprodukt gefunden; `sticker_reference` ist ein Branding-Aufkleber für die Versandverpackung (F). Die Doku-Einleitung erwähnt „stick-on-demand“ (F), ohne Endpunkt.
- **Flyer/Visitenkarten/Banner:** nicht im Sortiment (K).
- **Katalog-Webseite** https://cockpit.shirtigo.com/de/catalog liefert ohne JavaScript keine Produktliste (Webflow-Seite, Produkte werden nachgeladen) → Holzprodukte, Fußmatten, Tassen heute nicht geprüft. Druckbereichsmaße und Katalog-XLSX brauchen Login.

## B. Datenprüfung
- Validierungsfehler → HTTP 422 mit Details (F); Design-Upload 422, wenn die Datei die Anforderungen nicht erfüllt (F).
- **Automatische Korrekturen (optional, F):** `extract_size_and_position`, `resize_to_processingarea` (proportional verkleinern), Positionsanpassung je Konfektionsgröße (`force_position` false = Default).
- Ablehnung: Webhook `Order.creation-failed` z. B. bei Druckdatei-Fehlern; `Order.clarification` bei ungültiger Adresse oder Lieferengpass (F).
- Kosten für Prüfung: unbekannt.

## C. Reseller & API
- **API:** ja, REST, Base URL `https://cockpit.shirtigo.com/api`; Doku `/docs/`, `llms.txt`, `llms-full.txt`, Swagger 2.0 (`/docs/swagger.json`); Clients für Python, JavaScript, PHP; zusätzlich MCP-Server (F).
- **Auth:** `Authorization: Bearer <API-Key>` (Cockpit → Interfaces → API) oder OAuth2; andere Header-Namen führen zu 401 (F). Bestellen braucht aktive Zahlungsmethode (F).
- **Katalog/Preis:** `GET /base-products`, `GET /base-products/{id}` (auch `include_stock=true`), `POST /orders/predict-price` (F).
- **Auftrag (F):** `POST /orders` – Variante A: je Produkt `base_product_sku` (z. B. `STTU169_Black_XXL`), `amount`, `processings`; Variante B: vorher `POST /projects`, Design hochladen, `POST /customized-product`, dann bestellen per `sku`. `external_reference` verhindert Dubletten.
- **Druckdatei-Übergabe (F):** pro Druckbereich `design_url` (öffentliche URL, PNG/JPEG; Bild muss direkt geliefert werden, nicht in HTML eingebettet) **oder** `design_reference` nach Upload über `POST /designs/url`, `POST /designs/file` (multipart `file`) bzw. `POST /designs/base64`.
- **Vorschau:** `POST /mockup-factory/generate-image` (mockup_ids, design_url, mm-Maße, jpg/png, max. 2000 px), Event `GeneratedMedia.rendered` (F).
- **Webhooks (F):** `POST /webhooks`, Typen über `GET /webhook-types`; Events u. a. `Order.created`, `Order.creation-failed`, `Order.updated`, `Order.shipped`, `Order.clarification`, `Order.canceled`; HMAC-SHA256-Signatur, exponentielles Retry, `POST /webhooks/{id}/test`.
- **Sandbox (F):** keine. Test mit separatem Vorkasse-Konto und `"is_onhold": true`; Freigabe `PUT /order/{reference}/release-onhold`, Storno `POST /orders/{order_reference}/cancel`.
- **White Label (F):** `sender`-Adresse pro Auftrag, muss eine physische Adresse in Deutschland sein; Branding mit Hangtags, Labels, Packins, Verpackung, Aufkleber. WooCommerce-Integration vorhanden (F).

## D. Automatisierungsrisiken
- Keine Werbemittel-Kernprodukte (Sticker, Flyer, Visitenkarten).
- Auflösungsprüfung bei `design_url` aus → Exporter muss selbst ≥ 300 dpi bei Zielgröße sicherstellen.
- ±20 mm Toleranz, automatische Verkleinerung bei S/M.
- Keine Sandbox; Tests nur live mit `is_onhold`.
- Druckbereichsmaße nur mit Login/API; Farbraum unbekannt.

## Was heißt das für die Pipeline
Shirtigo ist nur für den Textil-/Merch-Zweig relevant. Exporter: Higgsfield-Motiv → PNG mit Transparenz, ≥ 300 dpi bei geplanter mm-Größe → öffentlich hosten oder `POST /designs/file` → `POST /orders` mit `processings[{processingarea_type, design_url|design_reference, width, offset_top, offset_center}]`. Größen/Positionen sind mm-Parameter, keine Pixel-Templates. Status per signiertem Webhook.

## Gelesene URLs (F)
- https://cockpit.shirtigo.com/de/integrationen
- https://cockpit.shirtigo.com/de/developers
- https://cockpit.shirtigo.com/docs/
- https://cockpit.shirtigo.com/docs/llms.txt
- https://cockpit.shirtigo.com/docs/llms-full.txt
- https://cockpit.shirtigo.com/de/catalog (ohne JS keine Produktliste)

## Gegenprüfung
Stand 27.11.2026, keine Änderungen nötig.

**Bestätigt**:
- Druckdatei und Auflösung: design_url nur PNG oder JPEG. Die Mindestauflösung von 1000 px wird bei design_url standardmäßig nicht geprüft (`ignore_validation` default true). Bei `extract_size_and_position` gilt die Datei als Vollfläche mit 300 dpi.
- Maße und Position: Größe in mm per width/height, kein Beschnitt, Positionstoleranz ±20 mm.
- Upload: per URL, multipart oder base64.
- Vorschau: Mockup-Factory.
- Webhooks: HMAC-SHA256 und Test-Endpoint.
- Testen: keine Sandbox, nur ein separates Vorkasse-Konto mit `is_onhold: true`.
- Konto: Bestellen setzt eine aktive Zahlungsmethode voraus.
- Sticker: Der einzige gefundene Sticker ist ein Verpackungs-Branding-Aufkleber.

**Offen**: Farbraum, maximale Dateigröße, Druckbereichsmaße je Basisprodukt (nur mit Login).
