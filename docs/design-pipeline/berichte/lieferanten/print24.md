# print24 (Unitedprint SE) – Druckdaten, Datencheck, Schnittstelle

Stand: 27.11.2026. Evidenz: F = heute auf print24.com / upi.unitedprint.com gelesen, W = Websuche, K = eigenes Wissen. Die Produktwerte (Beschnitt, Sicherheitsabstand) stammen aus dem Datenblatt-Text und dem Konfigurations-JSON (`trimValue`, `distanceSize`), das in jede Produktseite eingebettet ist. Sie gelten für die **Standardkonfiguration** der jeweiligen Seite.

## A. Druckdaten-Anforderungen

**Allgemein (F, Datenblätter Flyer/Sticker/Visitenkarte/Plakat, FAQ)**
- Formate: PDF bevorzugt, JPEG und TIFF werden ebenfalls angenommen. Roll-up: nur „PDF X3 - 2003“.
- PDF-Standard: Für Klein- und Offsetprodukte wird kein PDF/X verlangt. Für Werbetechnik (Roll-up) gilt PDF/X-3:2003 im Maßstab 1:1 inklusive Beschnitt.
- Farbe: CMYK. ICC-Profil je Papier: **„ISO Coated v2 (Fogra 39 L)“ bei Bilderdruckpapier** und **„PSO Uncoated ISO12647 (Fogra 47L)“ bei Offset- oder Recyclingpapier**. Freiform-Aufkleber: „ISO Coated v2“, Roll-up: „ISO Coated v2 (ECI)“.
- RGB: Was mit RGB-Daten passiert, steht nirgends: **unbekannt** (beim Support erfragen). Die Pipeline sollte selbst nach CMYK konvertieren.
- Auflösung: mindestens 300 dpi. Für Plakate nennt das Datenblatt „Mind. 150dpi“ und daneben 300 dpi, ohne zu sagen, welcher Wert für welches Format gilt. Roll-up/Großformat: 70–150 dpi.
- Schriften: „Bitte wandeln Sie alle Schriften in Pfade um“ (Datenblatt). Die FAQ verlangt nur eingebettete Schriften. Im Zweifel gilt die strengere Regel: Pfade.
- Sonderfarben: „Bitte verwenden Sie keine Schmuck- bzw. Sonderfarben.“ Die einzige Ausnahme ist die Schnittlinie bei Freiform. HKS, Pantone, Gold und Silber gibt es nur als bestellte Option (FAQ: Metalleffekt nur bei 100 % Farbauftrag).
- Datei: Maße wie vorgegeben, keine Schnittmarken, kein Endformatrahmen, keine Formularfunktionen. Hilfslinien der Vorlage entfernen. Hintergründe bis in den Beschnitt ziehen. Druckbögen „schlagen um“, sie werden nicht umgestülpt (Rückseite nicht auf den Kopf stellen).
- Max. Dateigröße: Shop-Upload „max. 1 GB“.
- Nicht gefunden (unbekannt): max. Farbauftrag, Mindestschriftgröße, Mindestlinienstärke, Transparenzregeln, Weißdruck-Regel für transparente oder Metallic-Folie.

**Pro Produkt (F)**
| Produkt (Standardkonfig.) | Beschnitt | Sicherheitsabstand | dpi | Besonderheit |
|---|---|---|---|---|
| Aufkleber Freiform (20×20 mm) | 3 mm | 2 (distanceSize) | 300 | Schnittlinie s. u., Eckenradius ≥ 1 mm, ISO Coated v2 |
| Sticker (74×105 mm A7 hoch) | 2 mm | 1,5 | 300 | keine Schnittlinie verlangt |
| Flyer (A6; A8–A4, DIN lang, Sonderformat) | 2 mm | 1,5 mm | 300 | Profil je Papierklasse |
| Visitenkarte (85×55 mm) | 2 mm | 1,5 mm | 300 | wie Flyer |
| Plakat (A0 u. a.) | 2 mm | 1,5 mm (Legende) | 150/300 | Grenze unbekannt |
| Roll-up (800×2000 mm, PVC 510 g) | 5 mm | unbekannt | 70–150 | PDF/X-3:2003; unterer Bereich wird von Klemmschiene/Kassette verdeckt (Maß siehe PDF-Vorlage) |

**Schnittkontur Freiform-Aufkleber (F):** Ein Vektorpfad stellt die Schnittlinie dar. Er bekommt eine Volltonfarbe mit dem Namen **„cutting line“** und wird auf **Überdrucken** gestellt. Die Freiform muss in ihren Maximalmaßen dem bestellten Format entsprechen und das Endformat an allen vier Seiten in mindestens einem Punkt berühren. Strichstärke, Ebene und Abstand zum Motiv werden nicht genannt. Bei Standardformen (Sticker A7) ist keine Kontur verlangt.

**Vorlagen/Datenblatt:** Auf jeder Produktseite gibt es „Datenblatt anzeigen“ und eine PDF-Vorlage zum Download. Vorher muss das finale Produkt kalkuliert sein („Bitte kalkulieren Sie Ihr finales Produkt, bevor Sie die Druckvorlage herunterladen.“).

## B. Datencheck (F, FAQ Druckerzeugnisse)
- **Basis-Datencheck** (kostenlos, automatisch nach Eingang): prüft die „grundlegende Verwendbarkeit“, aber **nicht** die Qualität (Auflösung, Beschnitt, Transparenzen). Findet er Fehler, die den Druck unmöglich machen, gibt es zwei Wege: korrigierte Daten neu liefern oder die Daten ohne Bearbeitung zum Druck freigeben.
- **Premium-Datencheck** (kostenpflichtig, Preis unbekannt): persönliche, umfassende Qualitätskontrolle mit zeitlich garantiertem Feedback. Dazu kommen Neulieferung, Bearbeitung durch Mitarbeiter (fehlerabhängig und kostenpflichtig) oder Druckfreigabe.
- Formatprüfung beim Upload: Sie meldet Fehler bei falschem Format, bei Schnittmarken und bei fehlendem Beschnitt.
- Screen- oder Paperproof (nach FOGRA) ist bestellbar.
- Rückmeldung: im Shop über Druckdatenvorschau und Prüfstatus. In der API gibt es den Status „Datenbearbeitung“ (order_stati_1750_4) per Callback. Wie Datenfehler im API-Betrieb gemeldet werden, ist unbekannt.

## C. Reseller und API (F, USS-Connect-PDF, Version 1.1, Änderungsdatum 10.08.2026)
- **Es gibt eine API, aber nur für Partner:** eine SOAP-1.1-Schnittstelle „USS Connect“. Voraussetzungen: UP-Kundenkonto, Freischaltung für den Schnittstellenbetrieb und ein Client-Zertifikat von UP-IT (RAR-Archiv, Schlüssel mit der Kundennummer entschlüsseln). Zusätzlich werden customerNumber und password übergeben. WSDL Produktion: `http://interface.unitedprint.com/wsdl2.1`. Sample- und Dev-Testsystem: `http://interface.onts.unitedprint.dev/wsdl2.1`.
- **Katalog:** Jede Produktkonfiguration muss vorher im Partner-Katalog angelegt werden (`catalogue.php`, Auswahl der Eigenschaften „von oben nach unten“). Sie wird unter einer Artikelnummer gespeichert und in der Bestellung als `orderInformation->customerProductId` angegeben. Die Liste liefert `catalogueList.php`. Eine freie Options- oder Preisabfrage per API ist nicht beschrieben.
- **Bestellung:** `createOrder`. Mit `validate=1` läuft nur eine Prüfung, die Lieferdatum, Netto- und Bruttopreis sowie Währung zurückgibt. Mit `validate=0` wird der Auftrag angelegt und die Auftragsnummer zurückgegeben. Pflichtfelder sind unter anderem environment (portalName, countryCode, languageCode, partyGroupName), accountInformation, orderQuantity, shippingType (economy/standard/express/priority), orderName, dataSourceInformation und messageLevel. Rückgabe ist ein 6-stelliger Code.
- **Übergabe der Druckdatei:** pro `orderPositions[]` ein Block `dataSourceInformation` mit `dataSourceHost`, `dataSourcePort`, `dataSourceUser`, `dataSourcePassword` und `dataTransmission` (upload|download). Üblich ist, dass **UP das PDF selbst von einem FTP-Server holt** (`dataTransmission='download'`, Host enthält den Dateipfad). Alternativ geht es über die URL eines Webservers; dann dürfen User und Passwort nicht leer sein. Einen Multipart-Upload gibt es nicht. Pro Position wird ein PDF angegeben. Ob Vorder- und Rückseite getrennt geliefert werden dürfen, ist unbekannt (vermutlich ein mehrseitiges PDF, K).
- **Status:** Status wird nicht abgefragt. Stattdessen ruft UP bei jedem Statuswechsel einen **HTTP-Callback** beim Partner auf: Datenbearbeitung 1750, Pooling 2000, Verarbeitung 5000, Versandbereitstellung 5400, Versand 6000, Stornierung 7000. Der Callback liefert auch Lieferdienst und Trackingnummer. Die Einrichtung übernimmt UP.
- **Neutraler Versand:** `senderAddress` pro Position ist änderbar, Lieferadressen sind pro Position möglich, Lieferung nach DE ist möglich (ISO 3166). Ob Paket und Lieferschein vollständig neutral sind, ist unbekannt.
- Unitedprint wirbt mit „Shoppartner“/USS (Unitedprint Shop Services) für Wiederverkäufer (W). Konditionen sind unbekannt; unitedprint.com war hier nicht erreichbar und wurde nicht gelesen.

## D. Automatisierungsrisiken
- Ohne Freischaltung und Zertifikat gibt es keine API. Bis dahin ginge nur die manuelle Shop-Bestellung.
- Jede Variante (Format, Papier, Menge, Veredelung) muss vorab als Katalogprodukt angelegt werden. Ein neues Format bedeutet einen manuellen Katalogschritt.
- Die Druck-PDFs müssen per FTP oder HTTP mit Login öffentlich abrufbar sein. Das erfordert eigenes Hosting, und Zugangsdaten stehen im Auftrag.
- Der Basis-Check prüft weder dpi noch Beschnitt: Fehler werden womöglich gedruckt. Ein eigener Preflight ist Pflicht.
- Beschnitt ist je Produkt verschieden (2/3/5 mm). Die Werte stehen nur in Datenblatt und Produkt-JSON, nicht in der API.
- Regeln zu RGB, Weißdruck, Farbauftrag, Mindestschrift und Mindestlinie fehlen. Bei Folienaufklebern drohen dadurch Rückfragen.
- Fehlermeldungen zum Datencheck kommen im API-Betrieb vermutlich per E-Mail oder Telefon, nicht maschinenlesbar (unbekannt).

## Was heißt das für die Pipeline
- Export je Produktfamilie: PDF in CMYK. Profil nach Papier: Fogra39 (ISO Coated v2) für gestrichenes Papier, Fogra47L (PSO Uncoated) für Offset/Recycling. Für Großformat PDF/X-3:2003 mit ISO Coated v2 (ECI).
- Beschnitt aus einer Produkttabelle ziehen (Flyer/Visitenkarte/Sticker/Plakat 2 mm, Freiform-Aufkleber 3 mm, Roll-up 5 mm). Sicherheitsabstand 1,5 mm (Freiform 2 mm). Vor jedem neuen Produkt das Datenblatt prüfen.
- Alle Texte in Pfade wandeln, keine Sonderfarben, keine Schnittmarken. Bilder mit ≥ 300 dpi, Großformat 70–150 dpi. KI-Bilder (Higgsfield) vorher hochskalieren.
- Freiform-Sticker: Kontur als Vektorpfad, Volltonfarbe „cutting line“, auf Überdrucken, Bounding-Box = bestelltes Format.
- Eigener Preflight (Beschnitt, dpi, CMYK, Pfade) vor der Übergabe, weil der Basis-Check das nicht abdeckt.
- API-Zugang bei Unitedprint beantragen (Freischaltung, Zertifikat, Dev-System, Callback-URL, Konditionen für neutralen Versand und Reseller-Rabatt, RGB- und Weißregeln).
- Infrastruktur: Druck-PDFs auf einem FTP- oder HTTPS-Server mit Login bereitstellen. WooCommerce-Produkte auf customerProductId mappen. Preis und Lieferdatum mit `validate=1` holen, dann `validate=0`.

## Gelesene URLs (F)
- https://upi.unitedprint.com/upimgt/downloads/USS_UP_interface_documentation_DE.pdf
- https://print24.com/de
- https://print24.com/de/hilfe
- https://print24.com/de/faq
- https://print24.com/de/faq/druckerzeugnisse
- https://print24.com/de/druckprodukte/aufkleber-etiketten
- https://print24.com/de/druckprodukte/aufkleber-etiketten/aufkleber-etiketten-freiform
- https://print24.com/de/druckprodukte/aufkleber-etiketten/sticker
- https://print24.com/de/druckprodukte/flyer
- https://print24.com/de/druckprodukte/visitenkarten
- https://print24.com/de/druckprodukte/plakate
- https://print24.com/de/werbetechnik/rollups

## Gegenprüfung
Stand 27.11.2026. Alle First-hand-Werte des Review-Bundles wurden gegen den Seitentext geprüft; neue Seiten wurden nicht abgerufen.

Geändert: nichts. Alle Werte sind korrekt gelesen und den richtigen Produkten zugeordnet.

Bestätigt:
- PDF X3-2003 nur im Roll-up-Datenblatt.
- ISO Coated v2 (Fogra 39 L) für Bilderdruck und PSO Uncoated ISO12647 (Fogra 47L) für Offset und Recycling; Freiform ISO Coated v2.
- 300 dpi; Plakate 150 bzw. 300 dpi; Großformat 70–150 dpi.
- Beschnitt aus trimValue: 2 mm, Freiform 3 mm (Klartext „3 mm Beschnitt“, Eckenradius mindestens 1 mm), Roll-up 5 mm.
- Abstand 1,5 mm (Klartext „Schriften und Bilder 1,5 mm vom Rand anlegen“ bei Flyer, Visitenkarte und Plakat).
- Schnittlinie als Volltonfarbe „cutting line“ auf Überdrucken.
- UP-SOAP-Schnittstelle mit Zertifikat; Sample/Dev unter interface.onts.unitedprint.dev, Produktion unter interface.unitedprint.com; validate=1 als Trockenlauf.
- Status per HTTP-Callback, kein Polling.
- Druckdaten-Pull per FTP oder URL.

Offen:
- Der Sticker-Abstand von 1,5 mm und der Freiform-Abstand von 2 mm stammen nur aus dem Konfigurationswert distance_size, nicht aus Klartext.
- Bei Plakaten ist unklar, welche dpi-Grenze für welches Format gilt.
- Weißdruck, Farbauftrag und Transparenzen sind unbekannt.
