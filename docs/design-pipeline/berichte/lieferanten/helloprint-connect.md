# HelloPrint Connect – Druckdaten, Datencheck, API

Stand: 2026-11-27. Evidenz: F = heute auf der Lieferantenseite gelesen, W = Websuche, K = eigenes Wissen. Wörtliche Belege stehen in `helloprint-connect.profile.json`.

HelloPrint Connect (connect.helloprint.de) ist die Reseller-Plattform von Helloprint. Sie ist nur für verifizierte Druck-Reseller zugänglich. Bestellt wird über die Website oder per API. Laut Landingpage gibt es White-Label-Versand (F).

## A. Druckdaten-Anforderungen

**Allgemein** (gleicher Textblock auf allen gelesenen Produktseiten, F):
- **Formate:** Empfohlen ist ein druckfertiges, vektorbasiertes PDF. Akzeptiert werden auch JPEG, PNG und EPS; bei Fotos ein hochaufgelöstes JPEG ohne Kompression. Die Ebenen der Vorlage vor dem PDF-Export löschen.
- **PDF-Standard:** Die Shop-Seiten nennen keinen Standard. Die API-Doku zu `fileUrl` sagt „Preferably … PDF/X compatible standards“ (F). Welche PDF/X-Version gemeint ist, ist **unbekannt**.
- **Farbe:** CMYK, nicht RGB. Profil **FOGRA39 (ISO Coated v2)**. Das steht auf allen gelesenen Produkten gleich, auch bei Offset-, Recycling- und Großformat-Material. Ein eigenes Profil für ungestrichenes Papier wird nicht genannt (**unbekannt**).
- **RGB:** Helloprint konvertiert RGB-Daten auf Wunsch selbst; dabei können die Farben abweichen.
- **Auflösung:** 300 dpi. Bei Großformat (Roll-up, Banner, Vinylaufkleber) 150 ppi.
- **Farbauftrag:** höchstens 300 %, empfohlen 260 %. Schwarz als 100 K oder als C40 M40 Y0 K100. Kleiner schwarzer Text in reinem 100 K.
- **Schriften:** einbetten oder in Pfade umwandeln. Mindestschriftgröße 6 pt (Roll-up 20 pt, Banner nach Maß 60 pt).
- **Linien:** mindestens 0,25 pt, negative (helle auf dunklem Grund) mindestens 0,5 pt.
- **Überdrucken:** keine Elemente auf Überdrucken stellen, außer es ist gewollt. Regeln zu Transparenzen sind **unbekannt**.
- **Rahmen:** Bei Formaten kleiner als A4 keine Ränder unter 20 mm, wegen der Schnittabweichung.
- **Ausrichtung:** Datei und gewähltes Format müssen gleich ausgerichtet sein. Ein Querformat-Design auf einer Hochformat-Vorlage wird automatisch gedreht.
- **Seiten:** Broschüren als ein PDF mit allen Einzelseiten inklusive Umschlag. Wie die Rückseite bei beidseitigen Flyern geliefert wird (2-seitiges PDF?), ist **unbekannt**. Die API kennt nur ein `fileUrl` pro Position.
- **Maximale Dateigröße:** **unbekannt**.
- **Vorlagen:** PDF- und IDML-Vorlagen je Format im Bereich „Vorlagen/Leitfaden“ jeder Produktseite (gehostet auf assets.ctfassets.net).

**Pro Produkt** (F):

| Produkt (productKey) | Beschnitt | Sicherheitsabstand | Auflösung | Besonderheit |
|---|---|---|---|---|
| Standard Sticker (`stickers`) | 5 mm | 3 mm | 300 dpi | Laserschnitt, weißer Rand möglich; Einzelsticker |
| Sticker im Wunschformat (`stickersownsize`) | 5 mm | 3 mm | 300 dpi | Konturregel siehe unten |
| Vinylaufkleber (`vinylstickers`) | 5 mm | 10 mm | 150 ppi | Konturregel; Weiß nicht auf Transparent |
| Klassische Flyer (`standardflyers`) | 3 mm | 4 mm | 300 dpi | A3–A7, DL, Quadrate, A-Long, X-Long; 90–400 g/m² |
| Standard-Visitenkarten (`businesscardsstandard`) | 3 mm | 4 mm | 300 dpi | keine Rahmen |
| Plakate (`posters`) | 3 mm | 4 mm | 300 dpi | z. B. A2 |
| Roll-up Budget (`rollupbannersv2`) | 5 mm | 10 mm, zusätzlich oben 10 / links 4 / rechts 4 / unten 150 mm | 150 ppi | Mindestschrift 20 pt |
| Banner nach Maß (`banners`) | 5 mm | 50 mm | 150 ppi | Mindestschrift 60 pt |

**Stanzkontur für Sticker** (F, Wunschformat und Vinyl):
- Die Kontur ist eine Vektorform oben auf dem Design.
- Strichstärke **1 pt**, Farbe **100 % Magenta als Spotfarbe**, Name **„cutcontour“**, nur eine Konturlinie.
- Das Motiv muss mindestens **5 mm über die Konturlinie** hinausgehen.
- Wer die Form von Helloprint anpassen lassen will, schickt sie auf einer separaten Seite an der gewünschten Position.
- Ein Ebenenname wird nicht verlangt. Ob die Kontur auf Überdrucken stehen muss, ist **unbekannt**.
- Standard-Sticker in festen Formen (rund, eckig, quadratisch) brauchen keine Kontur; die Form wird als Option gewählt.

**Weiß auf transparenter Folie** (F):
- Weiß wird auf transparentem Material standardmäßig **nicht** gedruckt; weiße Flächen werden durchsichtig.
- Vollfarbe mit Weiß oder reiner Weißdruck geht nur mit einer **Vektordatei**.
- Wie die Weißebene benannt und angelegt wird (Spotfarbname), ist auf den Seiten **nicht** angegeben (**unbekannt**, beim Reseller-Support fragen).
- Auf der Vinylseite heißt es: „Weiß kann nicht auf transparente Aufkleber gedruckt werden“.
- Für Metallic-Folie wurde nichts gefunden.

## B. Datencheck

- **Basis-Check:** Im Konsumenten-Shop helloprint.com prüft ein kostenloser Basis-Check Auflösung, Größe und Farbeinstellungen und liefert einen digitalen Proof (W). Ob das für Connect und die API gleich gilt, ist **unbekannt**.
- **Premium Artwork Check:** wird auf der Connect-Stickerseite im Warenkorbschritt genannt, ist also eine kostenpflichtige Option (F). Preis in Connect **unbekannt**; im UK-Konsumentenshop ab GBP 2,99 (W).
- **Automatische Korrekturen** (F): RGB→CMYK-Konvertierung und Drehung bei falscher Ausrichtung. Weitere sind nicht veröffentlicht.
- **Folgen fehlerhafter Daten** (AGB Art. 7, F): Der Kunde trägt die volle Verantwortung für die Daten. Bei fehlerhaften Daten kann sich die Lieferung verschieben, der Lieferant kann Mehrkosten gesondert berechnen oder vom Vertrag zurücktreten. Proofs gibt es nur, wenn sie in der Auftragsbestätigung stehen (Art. 7.2). Eine Stornierung ist bis zur Proof-Freigabe oder bis zum Produktionsbeginn möglich (Art. 10.1).
- **Rückmeldung per API** (F): Die Order-States enthalten `ARTWORK_REQUIRED`, `ARTWORK_RECEIVED`, `ARTWORK_FILECHECK`, `ARTWORK_ACCEPTED`, `ARTWORK_APPROVAL_REQUIRED` und `ARTWORK_REJECTED`. Konkrete Ablehnungsgründe sind nicht veröffentlicht.

## C. Reseller und API

- **Existenz:** ja, aber **nur für Connect-Kunden** (partner-only). Den API-Key bekommt man per Anfrage an Helloprint (F).
- **Doku:** developers.helloprint.com (readme.io, `/llms.txt` und `.md`-Seiten mit eingebettetem OpenAPI 3.0). Produktions-Basis-URL `https://api.helloprint.com/rest/v1`.
- **Auth:** Header `x-api-key`.
- **Katalog:**
  - `GET /products/{productKey}` und Variante per SKU.
  - Der Variantenschlüssel `productKey~sku` steht auf jeder Produktseite (z. B. `standardflyers~FL-135G-A5-44`, `stickers~ST-PP60TWG-50x50-40-[ROUND+INDIVIDUAL]`).
  - Der Quote-Endpoint liefert Preise (`centAmountExclTax`), Lieferzeit und Cutoff-Zeit.
  - CSV-Preislisten gibt es asynchron per `POST /products/{productKey}/prices/download`; das Ergebnis kommt per Callback.
- **Bestellung** `POST /orders` mit:
  - `orderReferenceId` (Pflicht), `mode` (`prod`|`test`, Default `test`), `shipping` (Pflichtfelder firstName, lastName, addressLine1, postcode, city, country; dazu companyName und phone), `callbackUrls[]`
  - `orderItems[]` mit `itemReferenceId`, `variantKey`, `quantity`, `serviceLevel` (saver|standard|express) und **`fileUrl`**
- **Übergabe der Druckdatei:** **Pull per öffentlicher URL.** Pro OrderItem gibt es ein `fileUrl`, und Helloprint holt die Datei ab. Multipart-Upload und separater Upload-Schritt sind nicht dokumentiert. Getrennte Dateien pro Seite sind nicht vorgesehen.
- **Proof/Preview:** Kein Endpoint dokumentiert. Wie man im Status `ARTWORK_APPROVAL_REQUIRED` per API freigibt, ist **unbekannt**.
- **Callbacks:** dokumentiert sind nur `ORDER_CREATED` (mit orderId, itemIds und requestId), `SHIPPED` (mit trackingUrls) und eine Fehlermeldung bei unbekannter Kombination aus variantKey, Menge und serviceLevel. Bestellungen lassen sich per GET nach id, requestId oder orderReferenceId abrufen und stornieren.
- **Sandbox:** `mode=test`. Was der Testmodus genau tut, ist nicht beschrieben.
- **Versand:** White-Label laut Landingpage. Die API-Doku sagt: „Delivery can only take place in the same country as the customer“. Ein deutsches Konto liefert also nach DE.
- **Einschränkung:** Die API-Produktübersicht (Stand 2025-03) sagt: „custom-size and clothing products are not yet available/working“.

## D. Automationsrisiken

1. Sticker im Wunschformat (`stickersownsize`) und Textilien sind per API möglicherweise nicht bestellbar. Das muss vor dem Start getestet werden.
2. Pro Position gibt es nur ein `fileUrl`. Vorderseite, Rückseite, Kontur und Weiß müssen also korrekt in einer Datei liegen, und die Seitenlogik ist nicht dokumentiert.
3. Die Datei muss öffentlich und lange genug abrufbar sein (signierte URL mit langer Laufzeit, nicht nach Minuten ablaufend).
4. Artwork-Status kommen nicht als dokumentierter Callback. Man muss pollen; eine Freigabe per API ist nicht dokumentiert.
5. Die Regeln hängen stark vom Produkt ab (3/5 mm Beschnitt, 3/4/10/50 mm Sicherheitsabstand, 300/150 ppi, 6/20/60 pt). Die Pipeline braucht deshalb eine Tabelle pro productKey.
6. Weißdruck auf Transparentfolie: Der Aufbau der Weißebene ist nicht veröffentlicht.
7. Es gibt keine PDF/X-Version und keine maximale Dateigröße. Bei Verstößen drohen nur allgemein Mehrkosten, Verzug oder Rücktritt.
8. Die API-Produktliste ist älter als einige Variantenschlüssel der Website (z. B. `standardflyers`, `rollupbannersv2`). Varianten sollten live per API geprüft werden.

## Was heißt das für die Pipeline

- Der Exporter erzeugt CMYK-PDF/X (Empfehlung: PDF/X-4 oder X-1a; die Version vorher mit Helloprint klären) mit FOGRA39. Higgsfield-Bilder (RGB) selbst nach CMYK konvertieren, nicht Helloprint überlassen.
- KI-Rasterbilder bei 300 dpi Endgröße plus Beschnitt erzeugen beziehungsweise hochskalieren, bei Roll-up und Banner bei 150 ppi.
- Beschnitt, Sicherheitsabstand und Mindestschrift als Tabelle pro `productKey` hinterlegen und vor jedem Export prüfen (eigener Preflight: Farbauftrag ≤ 300 %, Linien ≥ 0,25 pt, Schriften in Pfaden).
- Für Konturschnitt die Spot-Ebene „cutcontour“ (100 % Magenta, 1 pt, Vektor) automatisch aus der Freistellmaske erzeugen und das Motiv 5 mm darüber hinaus anlegen.
- Transparente Sticker zunächst ohne Weiß planen, oder den Weißaufbau vorab beim Support klären.
- Druck-PDFs an einem öffentlichen Ort (z. B. Storage-Bucket mit signierter URL, lange Laufzeit) ablegen und als `fileUrl` übergeben. `callbackUrls` auf einen WooCommerce-Endpoint setzen und zusätzlich den Artwork-Status pollen.
- Vor dem Start: Connect-Reseller-Konto verifizieren, API-Key anfordern, mit `mode=test` jedes Zielprodukt (insbesondere Sticker im Wunschformat) einmal durchspielen.

## Heute gelesene URLs (F)

- https://connect.helloprint.de/ + landing, agb, standardsticker, stickerwunschformat, vinylaufkleber, standardflyer, standardvisitenkarten, poster, rollbannersbudget, bannernachmass
- https://developers.helloprint.com/reference/ + helloprint-api.md, authentication.md, createorder.md, callbacks.md, order-states.md, quotes.md, getproduct.md, download-sku-pricesheets.md
- https://developers.helloprint.com/docs/productoverview-uk.md

Aus der Websuche (W, Host gesperrt): https://www.helloprint.com/en-us/artwork-check, https://www.helloprint.com/en-ie/help-centre/what-is-and-what-is-not-included-in-a-premium-deluxe-artwork-check

## Gegenprüfung
Stand 27.11.2026, keine neuen Seiten abgerufen.

**Geändert im Profil**
- `general.max_ink_pct` (falscher Geltungsbereich): Die Angaben „max. 300 %“, „mit 260 % konvertieren“ und Schwarz 100 K bzw. C40 M40 Y0 K100 stehen nur auf den Seiten für Flyer, Visitenkarten und Poster. Auf den Seiten für Sticker, Vinyl, Roll-up und Banner fehlen sie.
- `general.bleed_mm`: gegengeprüft, Wert unverändert. 3 mm (Flyer, Visitenkarten, Poster) und 5 mm (Standard-Sticker, Sticker im Wunschformat, Vinyl, Roll-up, Banner) stehen jeweils im Spezifikationsblock des Produkts selbst. Das ist produktspezifisch, keine Verwechslung. Beim Wunschformat muss das Motiv zusätzlich mindestens 5 mm über die Kontur reichen.
- `products[stickersownsize].notes`: In der API-Produktübersicht steht stickersownsize ausdrücklich unter „custom-size … not yet available/working“.

**Bestätigt**: FOGRA39 (ISO Coated v2) auf allen gelesenen Produktseiten, CMYK, RGB-Konvertierung durch Helloprint, 300 bzw. 150 ppi je Produkt, Sicherheitsabstand 3/4/10/50 mm je Produkt, Kontur „cutcontour“ (1 pt, 100 % Magenta, Spot, eine Linie), Weiß auf Transparent standardmäßig nicht gedruckt und Weißdruck nur mit Vektordatei, Vinyl ohne Weiß, API nur für verifizierte Connect-Reseller (Landingpage), fileUrl (PDF/X bevorzugt), Callbacks ORDER_CREATED und SHIPPED, `mode` prod/test mit Default test (Achtung: Produktionsaufträge brauchen ausdrücklich `mode=prod`).

**Offen**
- Benennung und Aufbau der Weißebene: Auf keiner gespeicherten Seite ist eine Spot-Farbe für Weiß genannt. Die verlinkten Anleitungs-PDFs liegen auf einem Fremd-Host (assets.ctfassets.net) und wurden nicht geladen. Klärung beim Support.
- Die PDF/X-Version.
- Wie sich `mode=test` verhält.
- Ob die ARTWORK_*-Status als Callback kommen.
