# KI-Designs für Werbemittel: Rechercheergebnisse

Der Plan steht im Dokument „Plan: KI-Designs für Werbemittel – von Higgsfield bis zur Druckdatei“
(<https://claude.ai/artifact/AdvMgQV9k6Kgv5mK4nyNwT>, sichtbar für alle, mit denen es geteilt ist).
Dieser Ordner hält die Belege fest, auf denen er beruht. Stand: 27.11.2026.

| Pfad | Inhalt |
|---|---|
| `profile/<lieferant>.json` | Druckdatenvorgaben und API-Wege je Lieferant (Entwurf), jeder Wert mit Herkunft |
| `vergleich-lieferanten.json` | dieselben elf Lieferanten in einheitlicher Vergleichsform |
| `berichte/lieferanten/<lieferant>.md` | Bericht je Lieferant, mit Gegenprüfung |
| `berichte/higgsfield-chat-anbindung.md` | was die Higgsfield-Anbindung im Chat kann: Modelle, Kosten, Grenzen |
| `berichte/higgsfield-api.md` | die öffentliche Higgsfield-API: Ablauf, Webhooks, Aufbewahrung, Bedingungen |
| `berichte/inspiration-und-recht.md` | fremde Beispiele als Inspiration: Rechtslage und Arbeitsweise |
| `berichte/druckexport-test.md` | Machbarkeitstest: ein Master, zwei Lieferantenprofile, Preflight |
| `belege/*.json` | die tragenden Aussagen zu Higgsfield und Recht mit wörtlichen Zitaten |
| `formatmatrix/` | Rechnung: Pixelbedarf je Werbemittel gegen die Seitenverhältnisse der Bildmodelle |

Der Code des Machbarkeitstests liegt in `tools/print-spike`, die Recherche-Werkzeuge in `tools/research`.

## Nachweise

- `F`: am Erhebungstag auf der Seite des Anbieters gelesen. Diese Werte tragen ein wörtliches Zitat
  (`quote`), das ein Skript auf der gespeicherten Seite wiedergefunden hat.
- `W`: aus Suchergebnissen, nicht auf der Originalseite bestätigt.
- `K`: Hintergrundwissen, nicht geprüft.
- `unknown` bzw. „unbekannt“: nicht gefunden. Keine Lücke ist mit einem branchenüblichen Wert gefüllt.
- Ein Feld `review` zeigt, was die Gegenprüfung korrigiert hat.

Die Profile sind ein Entwurf. Vor dem ersten echten Auftrag bestätigt je Produkt eine Testbestellung
die Werte (Plan, Phase 1). Die abgerufenen Seiten selbst liegen nicht im Repository; wer ein Zitat
nachprüfen will, holt die Seite mit `tools/research/page.py` neu.
