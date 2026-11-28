# Maxilia (Werbeartikel) – Lieferantenprofil

Stand: 2026-11-27. Anbieter laut Impressum: Maxilia Werbeartikel GmbH, Moers (gehört zur niederländischen Maxilia-Gruppe, Shops DE/FR/IT/NL). Evidenz: F = heute auf maxilia.de gelesen, W = Websuche, K = Eigenwissen.

**Kernaussage:** Maxilia ist ein Werbeartikel-Händler mit Reseller-Programm und Blanko-Versand. Eine API oder einen Datenfeed haben wir nicht gefunden. In der DE-Sitemap (ca. 2.840 URLs, davon ca. 2.297 Produktseiten) gibt es **keine Aufkleber, Flyer, Visitenkarten, Postkarten oder Plakate**. Dazu gibt es nur wenige Großformate (Absperrgitter-Banner, Fahnen). Für Sticker und Flyer kommt Maxilia daher nicht in Frage.

## A. Druckdaten-Anforderungen

Allgemein (Seite /aufdruck/, F):
- **Dateiformate:** vektorisierte EPS- oder AI-Datei („Um Ihr Logo scharf zu drucken, benötigen wir eine vektorisierte EPS- oder AI-Datei.“). PDF wird nur akzeptiert, wenn es wirklich Vektoren enthält („eine PDF-Datei ist nicht immer ein Vektor“). JPG und PNG sind „nicht (direkt)“ geeignet, Maxilia konvertiert sie kostenlos. Ein JPG soll nicht einfach als EPS gespeichert werden. Genannte Programme: Illustrator, Photoshop, Quark XPress, InDesign.
- **Farben:** PMS (Pantone Coated) für alle Farbcodes. CMYK nur bei Vollfarbdruck („Digitaldruck“). „4 Farben“ heißt 4 einzelne Sonderfarben und ist nicht dasselbe wie Vollfarbe. Die maximale Farbanzahl steht je Produkt unter „Anzahl der Druckfarben auswählen“. Maxilia kann ein Logo in weniger oder andere Farben umwandeln. Die Druckfarbe kann je nach Produktfarbe abweichen.
- **Schriften:** in Buchstabenkonturen umwandeln oder die TTF-Datei mitschicken. Alternativ Schriftname und PMS-Farben angeben.
- **Druckflächen:** Jedes Produkt hat feste Druckpositionen mit maximaler Druckfläche (Breite x Höhe in mm) unter „Eigenschaften“. Das Logo wird „standardmäßig größtmöglich innerhalb der Druckfläche“ gesetzt. Die Strichzeichnung ist nicht maßstabsgetreu, maßgeblich ist der digitale Korrekturabzug.
- **Vorlagen:** Auf /bestellprozess/ gibt es DIY-Vorlagen (Canva, Photoshop, InDesign). Damit erstellt man aber nur eine *Produktansicht* (Mockup), keine Druckvorlage. Wo die Vorlage je Produkt liegt, ist unbekannt.
- **Unbekannt** (nicht veröffentlicht, beim Reseller-Ansprechpartner erfragen): PDF/X-Standard, ICC-Profile, Umgang mit RGB-Daten, Mindestauflösung in dpi (auch für Vollfarbdruck und Banner), Beschnitt und Sicherheitsabstand in mm, maximaler Farbauftrag, Mindest-Schriftgröße, Mindest-Linienstärke, Transparenzen, maximale Dateigröße.

Produkte (F):
- **Kugelschreiber Rio, Vollfarbdruck (Nr. 53062):** Druckfläche rechtshändig 58 x 6 mm oder linkshändig 58 x 6 mm. Lieferzeit 1–2 Wochen.
- **Absperrgitter-Banner Medium, Banner 510, beidseitig (Nr. 101832):** für Gitter mit 250 cm Breite. Ösen nur links und rechts. „Vorder- und Rückseite des Designs müssen angeliefert werden.“ Die Produktdaten nennen Druckbreite 215 mm und Druckhöhe 146 mm. Das ist für ein Bauzaunbanner unplausibel (Datenfehler oder andere Einheit) und muss geklärt werden. Beschnitt und dpi sind unbekannt.
- Sticker, Flyer, Visitenkarte, Plakat, Roll-up: **nicht im Sortiment gefunden.** Es gibt nur verwandte Artikel wie Wasserflaschen mit eigenem Etikett und Samenpapier A6.

## B. Datenprüfung

- **Wer prüft:** DTP-Spezialisten prüfen manuell und kostenlos („Kostenlose Dateikontrolle“, F). Ein automatischer Preflight ist nicht belegt.
- **Automatische Korrekturen:** Pixeldateien werden kostenlos vektorisiert, Logos können farblich reduziert oder umgefärbt werden, fehlende Schriften setzt Maxilia nach Angabe (F).
- **Ablehnungsgründe:** unbekannt.
- **Kosten:** Einrichtungskosten pro Druckfarbe. Die Kategoriepreise enthalten 1 Farbe (F). Der Preis ist all-in (Produkt, Druck, Lieferung) zzgl. 19 % MwSt.
- **Ergebnis per E-Mail:** Bestätigungs-E-Mail, dann Auftragsbestätigung mit Logo-Prüfung, dann digitale Druckvorschau (laut /aufdruck/ innerhalb von 4 Stunden, kostenlos). Nach der Freigabe kommt eine E-Mail mit dem Liefertermin, nach dem Druck eine Sendungsnummer (F). Nach Freigabe und Produktionsstart sind Änderungen und Storno nicht mehr möglich.

## C. Reseller und API

- **API:** keine gefunden. Die Reseller-Seite erwähnt keine Schnittstelle, die Websuche ergab keine Treffer (W). Status: „no“, final per Anfrage beim Reseller-Team bestätigen. Unbekannt: Dokumentation, Authentifizierung, Sandbox, Webhooks.
- **Zugang (F):** Anfrage per E-Mail oder Button. Konditionen kommen in 1–2 Werktagen. Voraussetzungen: Eintrag im Handelsregister, aktiver Wiederverkauf von Maxilia-Produkten, Kreditwürdigkeit (jährliche Prüfung). Keine Gebühren, keine Mindestbestellmenge. Es gibt einen persönlichen Ansprechpartner.
- **Bestellung (F):** über die Website mit Kundenkonto, per Angebotsanfrage über die Website oder per E-Mail an die Kontaktperson. Neukunden zahlen per Vorkasse, Bestandskunden auf Rechnung.
- **Übergabe der Druckdaten (F):** *nach* der Bestellung Logo oder Design im Account hochladen. Danach folgen Druckvorschau, Freigabe oder Änderungswunsch. Ein Upload mit der Bestellung, eine Pull-URL oder eine Datei je Seite per Schnittstelle ist nicht belegt. Bei beidseitigen Artikeln werden Vorder- und Rückseite getrennt geliefert.
- **Katalog:** kein Feed. Die öffentliche Shopware-Sitemap listet alle Produkt-URLs. Druckpositionen und -größen stehen nur im HTML der Produktseiten.
- **Neutraler Versand (F):** „Blankoversand (Dropshipping)“, also neutrale Lieferung ohne Maxilia-Branding direkt an Endkunden. Standardversand ist innerhalb der EU kostenlos, Lieferung nach Deutschland also möglich. Blanko-Muster sind erhältlich.

## D. Automatisierungsrisiken

1. Das Sortiment passt nicht zum Kerngeschäft: keine Sticker, Flyer oder Visitenkarten.
2. Keine API: Jede Bestellung läuft manuell über Web-Konto oder E-Mail. Eine Browser-Automatisierung müsste vorher anhand der AGB geprüft und abgestimmt werden.
3. Der Upload erfolgt getrennt und erst nach der Bestellung.
4. Für jede Bestellung ist eine Proof-Freigabe Pflicht. Der Liefertermin ist nur bei rechtzeitiger Freigabe verbindlich.
5. Maxilia prüft manuell und passt Dateien an (Vektorisierung, Farbreduktion, Größe). Das gedruckte Ergebnis ist also nicht 1:1 das gelieferte Design.
6. Higgsfield liefert Pixelbilder. Maxilia verlangt vektorisierte AI/EPS-Dateien und PMS-Farben, außer bei Vollfarbdruck.
7. Die Einrichtungskosten pro Farbe machen den Preis abhängig vom Design.
8. Die Druckflächendaten auf den Produktseiten sind teils unplausibel und dürfen nicht ungeprüft übernommen werden.

## Was heißt das für die Pipeline

- Für Sticker und Flyer kommt Maxilia nicht in Frage. Maxilia eignet sich höchstens später für Werbeartikel und Textilien.
- Für Werbeartikel ein Exportprofil „Logo-Druck“ anlegen: Higgsfield-Motiv vektorisieren (SVG nach AI/EPS oder Vektor-PDF), Schrift in Konturen umwandeln, Farben auf die erlaubte PMS-Anzahl (Coated) reduzieren. Für Vollfarbartikel CMYK verwenden. Die dpi-Vorgabe ist unbekannt.
- Druckflächen (mm) je Produkt aus dem HTML der Produktseite („Eigenschaften“, Druckbreite und Druckhöhe) auslesen oder manuell pflegen. Unplausible Werte markieren.
- Den Ablauf halbautomatisch planen: WooCommerce-Bestellung, dann manuelle Bestellung im Maxilia-Konto, dann Upload, dann Proof-E-Mail an Kunden oder Team, dann Freigabe. Die Proof-Freigabe muss als eigener Status im Shop abgebildet werden.
- Den Preis immer mit Einrichtungskosten pro Druckfarbe kalkulieren. Die Farbanzahl kommt aus dem Design.
- Den Reseller-Ansprechpartner zu den offenen Punkten fragen: API oder Feed, Mindest-dpi, Beschnitt bei Bannern und Vollfarbe, Proof-Freigabe durch den Reseller statt durch den Endkunden, Upload mit der Bestellung.
- Blanko-Dropshipping nutzen, um direkt an Endkunden in Deutschland zu liefern.

## Selbst gelesene URLs

- https://www.maxilia.de/reseller/
- https://www.maxilia.de/aufdruck/
- https://www.maxilia.de/bestellprozess/
- https://www.maxilia.de/bestellungen/
- https://www.maxilia.de/druckwerk/
- https://www.maxilia.de/sitemap.xml (und die verlinkte Shopware-Sitemap .xml.gz)
- https://www.maxilia.de/product/kugelschreiber-rio-farbiger-griff-kunststoff-vollfarbdruck-53062/
- https://www.maxilia.de/product/absperrgitter-banner-medium-banner-510-beidseitig-101832/

Nicht gelesen: faq.maxilia.de (anderer Host, nicht in der Freigabeliste).

## Gegenprüfung
Stand 27.11.2026. Alle First-hand-Werte des Review-Bundles wurden geprüft; neue Seiten wurden nicht abgerufen.

Geändert: Die Angabe „Druckvorschau innerhalb von 4 Stunden“ stammt von der Seite /aufdruck/, nicht von der Produktseite. Die Quelle ist im Profil vermerkt. Ergänzend gilt: Ist die Vorschau freigegeben (innerhalb eines Arbeitstages nach der Bestellung) und die Produktion gestartet, sind keine Änderung und keine Stornierung mehr möglich.

Bestätigt:
- PMS Coated für Sonderfarben; CMYK nur bei Vollfarbdruck („Digitaldruck“).
- Reseller-Voraussetzungen: Handelsregister, aktiver Wiederverkauf, jährliche Bonitätsprüfung.
- Logo-Upload im Account nach der Bestellung, danach Freigabe der Vorschau.
- Status nur per E-Mail, Tracking nach dem Druck.
- Blankoversand ohne Maxilia-Branding.

Offen: Eine API ist auf keiner gelesenen Seite erwähnt („no“ bleibt W). Druckdatenstandard, dpi, Beschnitt und Weißregeln sind unbekannt.
