# Probo (probo.de, Probo Prints GmbH) – Druckdaten und Reseller-API

Stand: 2026-11-27. Evidenz: F = heute auf Probo-Seite gelesen, W = Websuche, K = Eigenwissen. Probo ist ein niederländischer Digital- und Großformatdrucker. Er verkauft nur an Reseller („Print Professionals“). Probo.de gehört zur Probo Prints GmbH und hat eine eigene Auftragsliste, getrennt von Probo.nl/.eu (F).

## A. Druckdaten-Anforderungen

**Allgemein (Hilfecenter probo.de/kundendienst/dateien, F)**
- **Formate:** PDF wird empfohlen, JPG/PNG sind erlaubt. JPG geht nur bei Produkten **ohne** Schnittlinie, Bohrlöcher oder Weißdruck. Mit Farbprofil immer PDF verwenden. **TIFF, PSD, AI und EPS werden nicht akzeptiert.** Über die API: PDF (auch mehrseitig), JPG, PNG.
- **PDF-Standard:** unbekannt. Probo nennt kein PDF/X, bietet aber eigene „PDF-Voreinstellungen für Adobe Programme“ zum Download an (Inhalt nicht geprüft, wäre der nächste Schritt).
- **Farbmodus:** RGB und CMYK sind beide erlaubt. Für Fotos wird RGB empfohlen, sonst CMYK.
- **ICC-Profil:** CMYK mit **ISO Coated v2 (ECI)** zuweisen. Beim PDF-Export „Keine Konvertierung“ und „Alle Profile einschließen“ wählen. RGB mit **sRGB IEC61966-2.1**. Unterschiedliche Profile je Papierklasse werden **nicht** genannt.
- **RGB:** wird nicht abgelehnt. Probo rechnet RGB selbst um (Verfahren unbekannt). Das Profil sollte eingebettet sein.
- **Auflösung (Empfehlung, keine Pflicht):** Aufkleber und Drucksachen 300 ppi, alle anderen Produkte 150 ppi. Für Großformat gilt: 100–150 ppi beste Qualität, 70–100 gut, 30–70 für mehrere Meter Abstand.
- **Beschnitt:** **produktabhängig.** Der genaue Wert erscheint erst im Shop-Uploader, nachdem das Produkt im Warenkorb liegt, oder im Template unter „Downloads“ auf der Produktseite. Einen mm-Wert habe ich heute nicht ablesen können: **unbekannt**.
- **Sicherheitsabstand:** Bei Aufklebern ca. 5 mm empfohlen, die Schnitttoleranz liegt bei max. 1,5 mm. Für andere Produkte ist der Wert unbekannt.
- **Marken:** Dateien immer **ohne** Schnitt- oder Druckmarken liefern.
- **Schriften:** einbetten oder in Pfade umwandeln.
- **Max. Farbauftrag, Mindestschriftgröße, Mindestlinienstärke, Transparenz, max. Dateigröße:** nicht gefunden (unbekannt, Support fragen).
- **Sonderfarben:** Farbfelder als Prozessfarbe anlegen, denn Spot-Farben können zu Farbabweichungen führen. Spot-Farben sind nur Pflicht für PANTONE (wird automatisch angenähert), Schnitt- und Rilllinien, Bohrlöcher und Weiß. Eine Volltonfarbenbibliothek (.ase) für Illustrator steht zum Download bereit.
- **Seiten:** Broschüren als Einzelseiten liefern, keine Spreads. Doppelseitige Produkte brauchen Vorder- und Rückseite.

**Aufkleber (F)**
- **Rechteck/Quadrat:** Keine Schnittlinie nötig, Probo fügt sie automatisch ein.
- **Freie Form (Schnittlinie):**
  - eine geschlossene Vektorlinie ohne Füllung
  - Farbfeld mit Namen **„Cutcontour“** als **Sonderfarbe (Spot Color)**, Anzeige z. B. 100 % Magenta
  - „Linie mittig ausrichten“
  - Linie liegt in der Zeichenfläche, nicht im Beschnitt; um die Kontur herum trotzdem Beschnitt anlegen
  - nur **eine** Schnittlinie pro Design (Ausnahme: Aufkleberbögen, Fräsbuchstaben)
  - bei doppelseitigen Produkten nur auf der Vorderseite
  - nicht mit Photoshop erstellbar
  - Linienstärke und Überdrucken der Kontur werden nicht genannt (unbekannt)
- Die Laserschnitt-Toleranz liegt bei bis zu 1,5 mm. Deshalb keine dünnen Rahmen setzen und ca. 5 mm Abstand halten. Druck bis 1200 dpi (Durst TAU RSCi 510).
- **Weißdruck** (transparente Folie, Plexi; die Option wählt man bei der Bestellung):
  - „Vollständig mit Weiß füllen“: in der Datei ist nichts nötig
  - „Weiß, wo Farbe ist“: nur für Vektorobjekte; Farbe wird ab C 2 %, M 1 %, Y 6 % oder K 1 % erkannt
  - „Weiß mit Volltonfarbe“: Farbfeld **„White“** als Spot, Objekt über den Farbobjekten, **Fläche/Kontur überdrucken**; die Deckkraft ist pro Objekt steuerbar
- **Varianten:** 1 oder 2 Schichten Weiß, Day&Night (für Leuchtkästen).
- Zu **Metallicfolie** habe ich keine Regel gefunden.

**Flyer (F):** 12 feste Standardformate (A3–A7), 8 Papiersorten, z. B. Holzfrei Offset weiß 90/170 g und Sulfatpapier 260/300 g. Laminat glänzend, matt oder Soft-Touch. Ein Template liegt unter „Downloads“ auf der Produktseite. Beschnitt in mm: unbekannt (zeigt der Uploader).

**Großformat/Banner (F):** Übergroße Formate im Maßstab anlegen und im Uploader vergrößern. Ösen werden mit Ösenpunkten in der Datei markiert. Säume und Ösenabstände im Detail habe ich nicht gelesen (Hilfeseiten „oesen-angeben“ und „druckdatei-im-maßstab“; Letztere lieferte heute HTTP 500).

**Visitenkarten, Postkarten, Plakate, Roll-ups:** heute nicht gelesen. Die Regeln je Produkt stehen auf der jeweiligen Produktseite (Templates unter „Downloads“).

## B. Datencheck

- **Automatisch (F):** Nach dem Bestelleingang (Status queued) lädt Probo die Dateien und prüft sie (Preflight), bevor der Auftrag accepted wird.
- **Automatisch korrigiert:** PANTONE wird angenähert (F). Skaliert oder gedreht wird **nur auf Anweisung** über die Dateifelder `fill`, `fill_even`, `rotation`, `mirror` und `tiling`. Standard ist: keine Skalierung (F).
- **Ablehnung (F):**
  - Datei nicht verarbeitbar; häufigste Ursache ist eine **fehlende oder nicht erlaubte Sonderfarbe**
  - doppelte Bestell-ID
  - Format größer als das Material, ohne `tiling` (dann evtl. nicht akzeptiert)
- **Kostenpflichtige Prüfung:** unbekannt.
- **Rückmeldung (F):** Webhook an `callback_urls` mit `status: not_accepted` und `"File rejected, could not be processed"` samt Datei-ID. Zusätzlich gibt es `error_email_addresses` im Auftrag.

## C. Reseller und API

- **Vorhanden (F):** Probo Reseller API, Doku unter https://apidocs.proboprints.com/, Host `api.proboprints.com`.
- **Auth (F):** Header `Authorization: Basic {{token}}`.
- **Zugang (F):**
  1. Konto über probo.de/konto-anfordern beantragen.
  2. Probo prüft, ob ein echter Print-Profi dahintersteht.
  3. Rechnungskauf muss freigeschaltet sein.
  4. Dann das Token beantragen: Mein Konto > Konto & Einstellungen > API-Verknüpfung.
- **Katalog/Preis (F):** Produkte z. B. über `GET /products/product/{code}`. Konfigurierte Produkte brauchen einen vollständigen Options-Satz (configure endpoint oder Plattform). Ein Preis-Call ist ähnlich aufgebaut wie ein Order-Call; den Pfad habe ich nicht gelesen.
- **Bestellung (F):** `/order` endpoint, JSON mit folgenden Feldern:
  - `id`, `reference`, `contact_email`
  - `deliveries` (`address`, `delivery_date_preset`, `shipping_method_preset`, optional `sender_address`, `packing_slip_urls`)
  - `products` (`customer_code` oder `code` + `options`, `files`)
  - `callback_urls`, `order_type`
- **Übergabe der Druckdatei (F):** **kein Multipart-Upload.** Jedes Produkt hat ein Array `files`. Jedes Objekt trägt eine `uri`, also eine **öffentlich erreichbare URL, von der Probo die Datei holt**, dazu `page`, `side` (front/back), `amount`, `rotation`, `fill`, `fill_even`, `tiling` und `mirror`. Ein mehrseitiges PDF wird über ein Array `pages` angesprochen. Für doppelseitige Produkte gibt es ein Datei-Objekt je Seite oder je Seite des PDFs. Alternativ gibt es eine White-label-Uploader-Session (nicht im Detail gelesen).
- **Proof/Vorschau:** unbekannt (Uploader nicht geprüft).
- **Webhooks (F):** `callback_urls` müssen bei **jeder** Order mitgeschickt werden. Status u. a. `queued`, `accepted`, `not_accepted` und `shipment_in_transit` (mit `tracking_url` und `barcode`). Signatur im Header `X-Probo-Signature` (HMAC-SHA256, base64).
- **Sandbox (F):** Es gibt **keine.** Testbestellungen laufen mit `order_type: test` und werden storniert. `production` wird gedruckt und berechnet.
- **Neutralversand (F):** `sender_address` und `packing_slip_urls` pro Lieferung (Lieferschein in s/w, in einer Folientasche). Versand nach DE: Probo.de beliefert DE/AT (aus der Kontoseite abgeleitet; Versandkosten unbekannt).
- **WooCommerce (F):** Es gibt ein fertiges Plugin „Probo Connect for WooCommerce“ samt Configurator-Webkomponente. Das ist für den Shop direkt relevant.

## D. Automatisierungsrisiken

- Beschnitt in mm steht nur im Uploader oder Template: je Produkt einmal erfassen oder beim Support anfragen.
- Ein KI-Rasterbild (Higgsfield) hat keine Vektor-Schnittlinie. Für Freiform-Aufkleber muss eine Kontur erzeugt und als Spot „Cutcontour“ ins PDF geschrieben werden.
- Weiß auf transparenter Folie braucht den Spot „White“ auf Überdrucken. Falsche Sonderfarben sind die häufigste Ablehnung.
- Die Druckdateien brauchen öffentliche URLs, bis Probo sie geladen hat. Das betrifft Hosting, Ablaufzeit und Datenschutz.
- Ohne Sandbox sind nur Testaufträge möglich. Eine Fehlkonfiguration produziert echte Kosten.
- Ablehnungen kommen asynchron per Webhook. Ohne `callback_urls` gibt es kein Update.
- PDF/X, maximaler Farbauftrag sowie Mindestschrift und -linie sind nicht dokumentiert.

## Was heißt das für die Pipeline

- Exporter: PDF mit eingebettetem ISO Coated v2 (ECI) (oder sRGB für Fotos), Schriften in Pfade, keine Marken, Beschnitt aus einer Produkt-Tabelle.
- Für Aufkleber in freier Form: Vektorkontur aus der Alphamaske erzeugen, eine geschlossene Linie als Spot „Cutcontour“ (mittig, in der Zeichenfläche), Motiv in den Beschnitt verlängern, 5 mm Sicherheitsabstand.
- Für Rechteck- und Quadrat-Aufkleber keine Kontur mitschicken.
- Für transparente Folie die Weißoption wählen: „vollflächig“ oder „wo Farbe“ ist ohne Datei-Änderung möglich, für volle Kontrolle den Spot „White“ auf Überdrucken setzen.
- Fertige PDFs auf einem öffentlichen, zeitlich begrenzten Link ablegen und als `files[].uri` übergeben, mit `fill: false` und exakter Endgröße.
- Webhook-Endpunkt mit Prüfung von `X-Probo-Signature` bauen; bei `not_accepted` den Auftrag anhalten und benachrichtigen.
- Vor Go-live mit `order_type: test` testen und prüfen, ob das Plugin „Probo Connect for WooCommerce“ die Order- und Upload-Logik schon abdeckt.
- Beim Support erfragen: Beschnitt je Produkt, PDF-Voreinstellung (PDF/X?), max. Farbauftrag, Mindestlinie, Dateigröße.

## Heute gelesene URLs (F)

- https://apidocs.proboprints.com/
- https://apidocs.proboprints.com/guides/how-to-supply-files-for-an-order-request
- https://apidocs.proboprints.com/getting-started/authorization
- https://apidocs.proboprints.com/getting-started/sandbox-test-env
- https://apidocs.proboprints.com/guides/callbacks-webhooks
- https://apidocs.proboprints.com/requesting-a-probo-account
- https://apidocs.proboprints.com/examples/order
- https://apidocs.proboprints.com/examples/products
- https://apidocs.proboprints.com/examples/price
- https://apidocs.proboprints.com/guides/packingslips
- https://www.probo.de/
- https://www.probo.de/aufkleber-druckarbeit/kleines-format/kleine-aufkleber
- https://www.probo.de/aufkleber-druckarbeit/papier/flyer
- https://www.probo.de/kundendienst/dateien/erstellen-einer-schnittkontur-in-3-schritten/
- https://www.probo.de/kundendienst/dateien/formatieren-von-dateien-mit-weissdruck/
- https://www.probo.de/kundendienst/dateien/beschnitt-fur-die-fertigstellung-in-dateien/
- https://www.probo.de/kundendienst/dateien/tipps-fur-die-besten-farbprofile-und-einstellungen/
- https://www.probo.de/kundendienst/dateien/die-beste-auflosung-fur-das-beste-ergebnis/
- https://www.probo.de/kundendienst/dateien/das-richtige-dateiformat/
- https://www.probo.de/kundendienst/dateien/schriftarten-einbetten/
- (HTTP 500: https://www.probo.de/kundendienst/dateien/druckdatei-im-maßstab/)

## Gegenprüfung
Stand 27.11.2026. Neu gelesen: https://apidocs.proboprints.com/reference/probo und https://apidocs.proboprints.com/examples/configure-examples (downloads/probo-check/001.bin, 002.bin).

**Geändert im Profil**
- `products[Aufkleber klein].white` (falsch gelesen): Die Volltonfarbe „White“ kommt nicht „in eine eigene Datei“, sondern wird in derselben Druckdatei angelegt (Option „Weiße Tinte selbst platzieren“). Die Produktseite bietet „Weiße Tinte angeben“ an.
- `general.bleed_mm`: Die Produktseiten (kleine Aufkleber, Flyer) enthalten nur den JavaScript-Platzhalter `${bleed}`. In der öffentlichen API-Doku (Reference-Seite, Configure-Beispiele, Produkt-Beispiele) steht kein Beschnittfeld. Die OpenAPI-JSON der Reference-Seite liegt auf einem Fremd-Host (proboapi.cdn.prismic.io) und wurde nicht geladen. Der Beschnitt bleibt **unbekannt**.

**Bestätigt**: RGB und CMYK erlaubt (Fotos RGB empfohlen), ISO Coated v2 (ECI) bzw. sRGB IEC61966-2.1 zuweisen, ohne Konvertierung, alle Profile einschließen, 300 ppi (Aufkleber, Drucksachen) bzw. 150 ppi als Empfehlung, Großformat 100–150/70–100/30–70 ppi, Sicherheitsmarge ca. 5 mm und Abweichung bis 1,5 mm (kleine Aufkleber), Volltonfarben nur für Pantone, Schnitt, Bohrlöcher und Weiß, Kontur „Cutcontour“ (geschlossen, mittig, innerhalb der Zeichenfläche, nur Vorderseite, eine Linie), Weiß-Optionen (C2/M1/Y6/K1, nur Vektor, Überdrucken, 1 oder 2 Schichten, Day&Night), API nur für Probo-Profis mit Konto, files[].uri, Callbacks queued/accepted/not_accepted/shipment_in_transit mit X-Probo-Signature, keine Sandbox (order_type test wird storniert).

**Offen**
- Beschnitt je Produkt: nur über das Konto (Uploader oder Produkt-API) oder den Support.
- Linienstärke und Überdrucken der Cutcontour.
