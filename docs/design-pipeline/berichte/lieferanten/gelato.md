# Gelato – Druckdaten & API (Stand 2026-11-27)

**Wichtig:** www.gelato.com (auch /llms.txt) antwortete heute mit 403 und Bot-Schutz-Seite („Just a moment…“); developers.gelato.com, support.gelato.com und dashboard.gelato.com sind durch die Netzwerk-Policy gesperrt. Es gibt daher **keine F-Belege**; alles ist W (Websuche) oder K und vor dem Bau im Gelato-Dashboard/Support zu bestätigen.

## A. Druckdaten-Anforderungen (alle W, Support-Artikel „design requirements for PDF uploads“)
- **Formate:** JPEG, PNG, SVG oder PDF; PDF/X am druckfreundlichsten (Version nicht genannt).
- **Farbraum:** CMYK empfohlen für Kleinformat-Digitaldruck (Broschüren, Geschäftsausstattung). ICC-Profil: unbekannt. Umgang mit RGB: unbekannt.
- **Auflösung:** mind. 150 dpi, max. 300 dpi in Endgröße.
- **Beschnitt:** 4 mm an allen Seiten (Fotobuch-PDF 3 mm). Beispiel: Poster 300 × 400 mm → PDF 308 × 408 mm.
- **Sicherheitsabstand:** mind. 4 mm ab Schnittlinie.
- **Schriften:** einbetten oder in Pfade wandeln; Mindestschrift 7 pt empfohlen.
- **Seiten:** mehrseitige Produkte zählen Umschlag mit; Umschlagmaße per Endpoint `cover-dimensions` (W).
- Max. Farbauftrag, Linienstärke, Transparenz, Sonderfarben, max. Dateigröße: unbekannt.
- **Widerspruch:** Ein Gelato-Blog zu Visitenkarten nennt 3 mm Beschnitt, der Support-Artikel 4 mm → beim Support klären.

### Produkte für Werbemittel
- Bestätigt (W): Poster, Leinwände, Kalender, Karten (Grußkarten), Textil, Tassen, Handyhüllen, Schreibwaren; 680 Produkte, Produktion in 32 Ländern, auch Deutschland.
- Visitenkarten: Gelato-Blog führt einen Größenleitfaden (US-Standard 88,9 × 50,8 mm); ob als Produkt bestellbar: nicht bestätigt.
- Flyer: nach eigener Kenntnis (K) im Katalog, nicht bestätigt. Sticker: nicht bestätigt.
- Konturschnitt/Weißdruck: unbekannt.
- Templates: im Gelato-Dashboard (Login).

## B. Datenprüfung
- Gelato prüft u. a., ob die Seitengröße Beschnitt enthält (Fehlermeldung „Document page dimensions matched the product size but did not include bleeds“) (W).
- Datei-URL muss öffentlich und direkt herunterladbar sein, nicht hinter einer Vorschauseite (W).
- Automatische Korrekturen, Kosten: unbekannt. Ergebnis per Webhook (W).

## C. Reseller & API (alle W)
- **API:** ja. Orders API `POST https://order.gelatoapis.com/v4/orders`, Auth Header `X-API-KEY`, Key im Dashboard.
- **Auftrag:** `orderReferenceId`, `customerReferenceId`, `items[]` mit `itemReferenceId`, `productUid`, `files[]`; `orderType` „draft“ möglich. v2 (Promise-UID), v3 und v4 unterscheiden sich – nur v4 nutzen.
- **Druckdatei-Übergabe:** je Item `files[]` mit `type` (z. B. `default`) und `url`; Gelato lädt die Datei. Dateien per PATCH auf den Auftrag änderbar.
- **Katalog:** Product Catalog API mit `productUid`; `GET /v3/products/{productUid}/cover-dimensions?pageCount=…`.
- **Webhooks:** Event `order_status_updated` (orderId, orderReferenceId, fulfillmentStatus, Item-Status); 3 Wiederholungen im 5-s-Abstand bei fehlendem 2xx; Einrichtung im Dashboard.
- **Vorschau/Proof:** unbekannt. **Sandbox:** nur in Marketingtext erwähnt, unbestätigt.
- **White Label:** Druck unter eigener Marke, lokaler Versand in Deutschland; Branded Inserts & Labels laut Developer-Guide.

## D. Automatisierungsrisiken
- Keine Quelle heute selbst lesbar (Bot-Schutz/Policy): Zahlen sind Suchergebnisse.
- Fehlender 4-mm-Beschnitt führt zu Fehlern.
- Farbraum je Produktklasse unklar (CMYK bei Kleinformat empfohlen, sonst unbekannt).
- Produktverfügbarkeit (Flyer, Sticker, Visitenkarten) und `productUid` nur mit API-Key.
- Öffentliche, direkt ladbare Datei-URL nötig.

## Was heißt das für die Pipeline
Gelato ist der einzige der drei POD-Anbieter mit PDF-/CMYK-Pfad und Beschnittregeln wie klassische Druckereien (4 mm Beschnitt, 4 mm Sicherheitsabstand, 150–300 dpi, PDF/X). Für Poster/Karten den bestehenden PDF-Exporter mit 4 mm Beschnitt wiederverwenden; Sortiment und Farbprofil vorab mit API-Key und Support klären. Bestellungen als Draft anlegen, Status per `order_status_updated`.

## Gelesene URLs
- F-Versuche: https://www.gelato.com/de (403, Bot-Schutz), https://www.gelato.com/llms.txt (403)
- W: https://support.gelato.com/en/articles/8996349-what-are-the-design-requirements-for-pdf-uploads
- W: https://support.gelato.com/en/articles/8996583-document-page-dimensions-matched-the-product-size-but-did-not-include-bleeds
- W: https://developers.gelato.com/docs/orders/v2/create, https://developers.gelato.com/docs/webhooks, https://developers.gelato.com/docs/products/product/cover-dimensions/
- W: https://connect-support.gelato.com/en/articles/11076814-working-with-order-api-endpoints
- W: https://www.gelato.com/print-on-demand/germany, https://www.gelato.com/uk/blog/business-card-size-guide

## Gegenprüfung
Stand 27.11.2026.

**Wichtig: Für Gelato gibt es keinen einzigen Wert aus erster Hand.** Alle Gelato-Seiten (www.gelato.com, llms.txt, Doku) waren gesperrt. Jede Angabe stammt aus Suchergebnissen (W) oder eigener Kenntnis (K) und darf ohne Prüfung im Gelato-Dashboard bzw. per API-Key nicht für Aufträge verwendet werden. Auch der Abschnitt „Was heißt das für die Pipeline“ oben (4 mm Beschnitt, 4 mm Sicherheitsabstand, 150–300 dpi, PDF/X) beruht nur auf W.

**Geändert im Profil**
- `products[Flyer].dpi` und `products[Visitenkarten].dpi`: 300 → null (nicht belegt). Die Suchergebnisse nennen nur allgemein „mind. 150, max. 300 dpi“.
- `products[Poster]`: Die 150 dpi sind das allgemeine Minimum, kein Poster-Wert.
- Unter `automation_risks` steht jetzt ein Hinweis, dass kein Wert aus erster Hand vorliegt.

**Offen**: alles. Besonders der Beschnitt (Support-Artikel 4 mm, Blog 3 mm bei Visitenkarten), der Farbraum je Produktklasse, die Existenz von Sticker- und Flyer-Produkten, eine Sandbox und die API-Version.
