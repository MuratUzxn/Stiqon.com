# Higgsfield HTTP-API aus Sicht eines Shop-Backends

Stand: 2026-11-27. Nachweis: **F** = heute auf der Primärquelle gelesen (Dateien unter `downloads/higgsfield-api/`), **W** = Websuche, **K** = eigenes Wissen, ungeprüft. Belegte Einzelaussagen mit Zitat: `higgsfield-api.claims.json`.

## 0. Kurzfassung

- Basis-URL ist **`https://api.higgsfield.ai`** (F, introduction, FAQ, openapi `servers`). `platform.higgsfield.ai` kommt nur in Drittquellen vor (W, apis.io) und in der Doku nirgends.
- Webhooks sind nach **Standard Webhooks** signiert (HMAC-SHA256, `whsec_`-Secret). Retries laufen bis zu 2 h (F).
- Abrechnung: Prepaid-Guthaben in **USD** pro erfolgreicher Generierung. Fehlgeschlagene und NSFW-Anfragen kosten nichts. Guthaben verfällt nach 1 Jahr (F). Konkrete Modellpreise stehen auf den öffentlichen Seiten nicht maschinenlesbar. Man ermittelt sie mit `POST /estimate/<endpoint>`.
- SDKs gibt es nur für Python (`higgsfield-client`) und Node/TS (`@higgsfield/client`). **Kein PHP-SDK.** Für WordPress/WooCommerce heißt das: direkt REST ansprechen (F).
- **Druckrelevant:** Die API-Doku listet 17 Bild-Einträge. Darunter sind **keine** Nano Banana, GPT Image (außer intern in Ads Studio), Flux, Seedream oder Kling Image (F). Recraft V4.1 liefert über die API nur **jpg/png/webp, kein SVG**, und nimmt keine Referenzbilder an (F). Der Connector im Consumer-Produkt bietet SVG an (`recraft_v4_1` vector, `gpt_image_2_6`). Für die API ist das **nicht belegt**.
- AGB: Output-Rechte liegen beim Nutzer, die kommerzielle Nutzung ist frei. **Inputs und Outputs dürfen aber standardmäßig fürs Training genutzt werden.** Der Opt-out ist in der Console standardmäßig AUS. Vertragspartner ist Higgsfield Inc. (USA), es gilt kalifornisches Recht. Der Haftungsdeckel liegt bei max(6-Monats-Zahlungen, 100 USD) (F).

## 1. Technik (alles F, Dateinummern in Klammern)

- **Auth/Schlüssel (004, 017):** `Authorization: Key ID:SECRET`, legacy `hf-api-key`/`hf-secret`. 401 bei falschen Credentials; nicht freigeschaltetes Modell: 404/423/503. Schlüssel in Console „API keys“ mit Name, Ablaufdatum, Tags, „last used“; abgelaufen/deaktiviert → 403 `api_key_expired`/`api_key_disabled`. Nur serverseitig verwenden.
- **Lebenszyklus (003, 006, openapi):** `POST /<endpoint-id>` (z. B. `higgsfield-ai/soul/v2/standard`, `recraft/v4.1/text-to-image`) → `{status:"queued", request_id, status_url, cancel_url, expires_after:604800}`. `GET /requests/{id}/status`; Zustände `queued`, `in_progress`, terminal `completed|failed|nsfw|canceled`. Ergebnis `images:[{url}]` (Webhook zusätzlich `content_type`), `expires_at` nach Abschluss. URLs aus der Antwort benutzen.
- **Idempotency-Key (022, 024):** 1–255 sichtbare ASCII-Zeichen; Retry mit gleichem Key liefert die ursprüngliche `request_id` ohne neue Kosten; andere Parameter → 422. Pro Design-Auftrag speichern.
- **Polling (007):** 2 s, steigend bis 10 s, Jitter, eigenes Timeout; Webhook primär, Polling als Rückfall.
- **Webhooks (008, 016, 022):** pro Request per Query `hf_webhook=<urlencodete HTTPS-URL>` (keine globale Konfiguration dokumentiert). Endpoint: HTTPS, nur öffentliche IPs, keine Credentials in URL, 2xx binnen 10 s. Zustellung nur bei `completed|failed|nsfw`; Envelope `{request_id,status,error,expires_at,payload:{images:[…]}}`, bei Fehler `payload:null`. Signatur nach Standard Webhooks: Header `webhook-id`, `webhook-timestamp`, `webhook-signature` (`v1,<base64>`); HMAC-SHA256 über `{id}.{ts}.{roher Body}` mit base64-dekodiertem Secret ohne `whsec_`; Toleranz 5 min. Ein Secret pro Account in Console > Settings; **erst ab dem ersten Aufdecken wird signiert**. Rotation: altes Secret 24 h gültig. Retries bei Netzfehler/5xx bis 2 h, 4xx endgültig, Duplikate möglich (nach `webhook-id` deduplizieren); nach Löschung keine Zustellung mehr. PHP: Doku nennt nur Python/npm-Bibliotheken; manuell mit `hash_hmac` machbar (K).
- **Abbrechen/Löschen (006, 014):** `POST /requests/{id}/cancel` nur solange `queued` → 202 (sonst 400), wird erstattet. `DELETE /requests/{id}` → 200 `{status:"deleted"}`, idempotent; 409 wenn laufend, 404 fremd/unbekannt. Löscht Outputs, gespeicherte Inputs, Prompt, Parameter, Webhook-URL; Billing bleibt. Bei Standard-Retention werden nach 7 Tagen nur die **Dateien** gelöscht, Prompt/Parameter bleiben.
- **Uploads (009, 014):** `POST /files/generate-upload-url` `{"content_type":"image/png"}` → `public_url`, `upload_url` (presigned, 1 h), `upload_headers` (u. a. `x-amz-tagging: retention=temporary`); `PUT` an `upload_url` mit diesen Headern, ohne Higgsfield-Auth; `public_url` dann als `image_url` o. Ä. Typen: jpeg/jpg/png/webp/gif, wav, mp4 – **kein PDF/SVG/TIFF**. Größenlimit **unbekannt**. Uploads verfallen binnen 7 Tagen; kein Lösch-Endpunkt dokumentiert. Öffentliche HTTPS-URLs gehen auch.
- **Fehler (010):** 400 Parameter, 401 Auth, **403 Guthaben zu niedrig**, 404, 409 läuft noch, 422 Validierung/Idempotenz/`Hf-Expires-After`, 423 Modell gesperrt, 429 Queue voll, 500, 503. Jede Antwort hat `X-Correlation-ID`.
- **Limits (011, 018):** Concurrency pro Account (teils pro Modell); Überhang wird gequeued; Queue = Vielfaches der Concurrency (jeder Batch-Output zählt); voll → 429 `account_queue_full` + `Retry-After`, ohne Kosten. Werte nur in der Console (**unbekannt**), keine Rate-Limit-Header. SLAs nur Enterprise (020).
- **Abrechnung (012, 019, 023):** Prepaid-Guthaben in USD, Preis je Modell/Parameter; `POST /estimate/<endpoint-id>` → `{"usd":"…"}` (0,094 in der Doku nur Formatbeispiel). failed/nsfw/canceled kostenlos. Guthaben verfällt nach 1 Jahr. `GET /models/usage` = Analytik, kein Beleg. Rechnungskauf nur mit Enterprise-/Committed-Use-Vertrag.
- **Aufbewahrung (012, 014, 022, 023):** 7 Tage ab Abschluss; `Hf-Expires-After` (3600 s bis Account-Retention) verkürzt nur; Account-Retention 1 h–7 d auf Anfrage. **Der Shop muss jedes Ergebnis sofort selbst herunterladen und speichern**; laut FAQ können Dateien nach 7 Tagen jederzeit verschwinden.
- **SDKs (021, 023):** Python `higgsfield-client` (3.8+), Node/TS `@higgsfield/client` (`/v2`). **Kein PHP** – REST direkt (z. B. `wp_remote_post` + Action Scheduler, K) oder kleiner Python/Node-Worker.
- **Enterprise (013, 015, 017):** Trainings-Opt-out Console > Settings „Exclude data from training“, pro Account, **standardmäßig aus**, deckt Inputs und Outputs. Organisationen (Admin/Member), SAML-SSO, dedizierte Kapazität auf Anfrage.

## 2. Bildmodelle der API

- Die Doku listet 17 Bild-Einträge aus 14 Familien (F 026): SOUL, SOUL V2, SOUL Cinema, Soul ID (Training), Marketing Studio Image (3 Workflows), Ads Studio (intern „GPT Image 2.5 Flare“), Grok Image 2.0 (bis 10 Referenzen), Recraft V4.1 / Pro (2K) / Utility / Utility Pro, Qwen Image 3 (Edit mit 1–3 Referenzen), Ideogram 4.0 (1 optionale Referenz), Z-Image Turbo (1K/2K).
- `openapi.json` (v2.0.0) enthält nur `POST /higgsfield-ai/soul/standard` (`prompt`, `num_images`, `resolution` 2K/4K, 10 Seitenverhältnisse von 21:9 bis 9:16), drei Request-Endpunkte sowie Kling 2.5 und Hailuo 2.3 für Video. Upload, Estimate und Webhook fehlen darin. llms.txt bezeichnet die Datei ausdrücklich als ergänzend und nicht maßgeblich (F 001, 027).
- **Recraft V4.1 Text-to-Image** (`POST /recraft/v4.1/text-to-image`, F 029):
  - `prompt` mit 1–10000 Zeichen, `resolution` nur `1k`.
  - 14 Seitenverhältnisse: 1:1, 2:1, 1:2, 3:2, 2:3, 4:3, 3:4, 5:4, 4:5, 6:10, 14:10, 10:14, 16:9, 9:16.
  - `output_format` jpg, png oder webp.
  - `colors` als Liste von `{rgb:[r,g,b]}`; `background_color`.
  - **Keine Referenzbilder, kein SVG, kein Seed, kein Transparenz-Parameter.**
  - Die Seiten für Pro und Utility habe ich nicht gelesen; offen ist, ob „Utility“ Vektor-Output erlaubt. Das lässt sich über `docs/models/recraft-v4-1-utility.md` prüfen.
- Nicht gelesen wurden außerdem die Seiten zu Ideogram 4.0, Qwen Image 3, Grok Image 2.0 und Z-Image (Budget). Seed, Transparenz und Ausgabeformat sind dort jeweils **unbekannt**, die Detailseiten unter `docs/models/<id>.md` geben Auskunft.
- **Connector-Modelle:**
  - `gpt_image_2_6`, `nano_banana_pro`, `flux_3_image`, `seedream` und `kling_image_4_0` haben **keine Erstbelege** in der API-Doku.
  - `recraft_v4_1` ist als API-Endpunkt belegt, aber ohne Vektor-Modus.
  - `ideogram_4_6` ist nicht belegt; die Doku nennt nur Ideogram 4.0.
  - Laut llms.txt beweist das Fehlen eines Modells in openapi.json nicht, dass es nicht verfügbar ist; der maßgebliche Katalog liegt in der (blockierten) Console.
  - Drittquellen nennen z. B. `bytedance/seedream/v4/text-to-image` (W, byteiota) und `nano-banana` (W, apidog, veraltet). Die Marketingseite higgsfield.ai/higgsfield-api zeigt `bytedance/seedance-2.0/text-to-video` und spricht von „50+ frontier models“ (F 033).
  - **Fazit:** Vor einer Modellentscheidung muss man den Console-Katalog mit dem eigenen Account prüfen. Dabei interessiert vor allem, ob die API SVG, Transparenz oder 4K bei Bildmodellen hergibt.
- Keine der gelesenen API-Seiten erwähnt CMYK, dpi, Beschnitt oder PDF. Druckdaten müssen also immer nachgelagert erzeugt werden: Upscaling, Vektorisierung, Farbraum-Konvertierung.

## 3. Bedingungen für einen Druck-Reseller

- **Eigentum und kommerzielle Nutzung:** Higgsfield beansprucht kein Eigentum an Inputs oder Outputs und beschränkt die kommerzielle Nutzung nicht. Die Rechte dürfen an Kunden übertragen oder unterlizenziert werden (F 031, AGB 4.4, Stand 23.10.2026).
- **Training:** Inhalte, Inputs und Outputs dürfen zum Training genutzt werden, außer bei einem Enterprise Agreement (F 031, 4.4). Der Opt-out in der Console ist **zwingend einzuschalten** (F 015).
- **Weiterverkauf:** Die Developer Terms 11.5 verbieten es, den API-Zugang Dritten „standalone“ oder als „pass-through or service bureau … with no independent value added“ bereitzustellen (F 031).
  - Ein Shop, der Designs in Druckprodukte verarbeitet, bietet eigenen Mehrwert. Ein reiner „Prompt rein, Bild raus“-Generator für Endkunden wäre grenzwertig. Hier ist eine rechtliche Prüfung nötig.
  - Nach 11.5 haftet man für Endnutzer, nach 11.7 sind KI-Inhalte offenzulegen, wo das Gesetz es verlangt.
- **IP-Prüfung:** Higgsfield prüft keine Rechte an Inputs oder Outputs (keine „clearance searches“), die Verantwortung liegt allein beim Nutzer (F 031, 13.2). Es gibt nur eine Content-Moderation, sichtbar am Status `nsfw` (F 006). Logo- und Markenprüfung bei Kunden-Uploads muss der Shop selbst leisten.
- **Haftung:** Gewährleistung „as is“. Gehaftet wird höchstens mit dem größeren Betrag aus den Zahlungen der letzten 6 Monate und 100 USD (F 031, 13.1/14.2).
- **Vertragspartner und Recht:** Higgsfield Inc., 535 Mission St., San Francisco. Kalifornisches Recht, Gerichtsstand San Francisco (F 031, 19.7–19.9).
- **DSGVO:**
  - Sitz in den USA, Übermittlung in die USA; SCCs sind als Garantie genannt.
  - EU- und UK-Vertreter ist DataRep. Für die Privacy Policy bezeichnet sich Higgsfield als „controller“ (F 032).
  - Ein Auftragsverarbeitungsvertrag (DPA) wird nur als mögliche separate Vereinbarung mit Organisationen erwähnt (F 032). Ob ein Standard-DPA für API-Kunden existiert, ist **unbekannt**; das ist bei support@higgsfield.ai bzw. privacy@higgsfield.ai anzufragen.
  - Ein EU-Datenstandort ist nicht dokumentiert.
  - Drittanbieter-Modelle unterliegen zusätzlich deren eigener Datenrichtlinie (F 014).
- **Zugang und Preise:**
  - Die API braucht einen Console-Account und Guthaben; laut Doku bekommt jeder Account dieselben Endpunkte (F 013). Ein bestimmter Abo-Plan ist für die API nicht erforderlich (laut Doku Prepaid in USD), das AGB-Abomodell mit Credits betrifft das Web-Produkt.
  - Die Pricing-Seite lieferte ohne JavaScript keine Preise (F 034, leer). Konkrete Modellpreise sind **unbekannt** und lassen sich über `/estimate` oder die Console ermitteln.

## 4. Minimaler Referenzablauf (Pseudo-HTTP)

`[A]` markiert Annahmen, die nicht belegt sind.

```
# 1 Referenzbild hochladen
POST https://api.higgsfield.ai/files/generate-upload-url
Authorization: Key $ID:$SECRET
Content-Type: application/json
{"content_type":"image/png"}
-> {public_url, upload_url, content_type, upload_headers}
PUT {upload_url}            # ohne Higgsfield-Auth, alle upload_headers mitsenden
Content-Type: image/png
x-amz-tagging: retention=temporary
<bytes>

# 2 Generierung mit Webhook, kurzer Aufbewahrung, Idempotenz
POST https://api.higgsfield.ai/<endpoint-id>?hf_webhook=https%3A%2F%2Fshop.example%2Fwp-json%2Fhf%2Fv1%2Fhook   [A: Pfad im Shop frei gewählt]
Authorization: Key $ID:$SECRET
Content-Type: application/json
Idempotency-Key: <uuid pro Auftrag, gespeichert>
Hf-Expires-After: 86400
{"prompt":"…","image_url":"{public_url}", …}   [A: Name des Referenzfelds und Felder modellabhängig; Recraft V4.1 nimmt KEINE Referenz]
-> 200 {status:"queued", request_id, status_url, cancel_url, expires_after:86400}
   bei 429: Retry-After abwarten, mit gleichem Idempotency-Key erneut senden

# 3 Webhook prüfen (Shop-Endpunkt)
roh = Request-Body (unverändert)
key = base64decode(secret ohne "whsec_")
sig = base64(HMAC_SHA256(key, webhook-id + "." + webhook-timestamp + "." + roh))
gültig, wenn sig zeitkonstant gleich einem "v1,<…>"-Eintrag in webhook-signature und |now - timestamp| <= 300 s
dedupe nach webhook-id; dauerhaft speichern; innerhalb 10 s 2xx antworten
Download asynchron [A: per Queue-Job, damit die 10 s eingehalten werden]

# 4 Ergebnis holen (oder Fallback-Polling: GET {status_url}, 2 s bis 10 s Backoff)
GET {payload.images[0].url}   [A: CDN-URL ohne Auth abrufbar; Doku zeigt nur Beispiel-URLs]
-> Datei in eigenen Speicher, vor expires_at

# 5 Daten bei Higgsfield löschen
DELETE https://api.higgsfield.ai/requests/{request_id}
Authorization: Key $ID:$SECRET
-> 200 {"request_id":"…","status":"deleted"}   (409, falls noch läuft)
# Upload-Datei: kein DELETE dokumentiert, verfällt binnen 7 Tagen
```

## 5. Offene Punkte

- Console-Modellkatalog: SVG-, Transparenz- und Seed-Parameter je Bildmodell, Freischaltung von GPT Image, Nano Banana, Flux und Seedream per API.
- Preise je Modell, Concurrency-Werte, Upload-Größenlimit.
- Ob es einen DPA bzw. AVV gibt und wo die Daten liegen.
- Auslegung von AGB 11.5 („service bureau“) für ein Shop-Modell mit Endkunden-Prompts.

## 6. Heute selbst gelesene URLs (F)

docs.higgsfield.ai/docs/: llms.txt, index.md, quickstart.md, authentication.md, how-to/introduction.md, concepts/requests.md, concepts/polling.md, how-to/webhooks.md, concepts/file-uploads.md, concepts/errors.md, concepts/rate-limits.md, concepts/billing-and-retention.md, concepts/idempotency.md, enterprise/overview.md, enterprise/data-retention.md, enterprise/model-training.md, enterprise/webhook-security.md, enterprise/access-control.md, enterprise/capacity.md, enterprise/usage-and-billing.md, enterprise/reliability-and-support.md, how-to/sdk.md, api-reference/headers.md, help/faq.md, models.md, models/image-generation.md, models/recraft-v4-1.md, models/recraft-v4-1/text-to-image.md, openapi.json.
higgsfield.ai: / (Links), /terms-of-use-agreement, /privacy-policy, /higgsfield-api, /pricing (ohne Preise gerendert).

Blockiert: console.higgsfield.ai, open.higgsfield.ai, status.higgsfield.ai, github.com.
