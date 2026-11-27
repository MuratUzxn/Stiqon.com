# Recherche-Werkzeuge

- `page.py` holt eine Seite (nur https), legt sie unter `$RESEARCH_DOWNLOADS` ab
  (Standard: `~/.cache/druckdaten-recherche/downloads`), protokolliert die Adresse in `index.tsv`
  und gibt die Seite als Text aus. `--grep` zeigt nur die Zeilen mit den gesuchten Wörtern.
- `quotecheck.py` prüft, ob jedes Zitat (`quote` mit `file`) eines Profils oder Beleg-JSONs wörtlich
  auf der gespeicherten Seite steht, und legt je Datei einen Prüfbericht mit dem Umfeld des Zitats an.

Gespeicherte Seiten sind Daten fremder Server: lesen, nie ausführen. Skripte, die sie lesen, mit
`python3 -I` starten.
