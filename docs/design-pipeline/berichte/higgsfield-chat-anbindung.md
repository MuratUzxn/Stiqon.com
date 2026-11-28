# Dossier: Higgsfield-MCP-Werkzeuge für Druckdesign (Werbemittel)

Stand: 2026-11-26. Erhoben ausschließlich lesend in einer Sitzung, an die der Higgsfield-MCP-Connector angebunden ist.

Nachweis-Kürzel in diesem Dokument:

- **[O: …]** = in dieser Sitzung direkt beobachtet (Tool-Antwort oder Tool-Schema, Name des Tools steht dabei)
- **[W: url]** = Ergebnis einer heute ausgeführten Websuche (Suchmaschinen-Zusammenfassung, Seite selbst nicht abgerufen)
- **[K]** = eigenes Hintergrundwissen, heute nicht überprüft
- **[R]** = eigene Rechnung aus den daneben genannten Zahlen

---

## 0. Kurzfassung

1. Der Connector stellt 203 Tools bereit [O: Tool-Liste der Sitzung, gezählt], davon sind für Druckdesign relevant: `models_explore`, `generate_image`, `generate_image_batch`, `estimate_image_cost`, `upscale_image`, `outpaint_image`, `remove_background`, `media_upload` / `media_import_url` / `media_confirm`, `elements_*`, Projekt-/Ordner-Tools, `sandbox_exec`, `get_preset_instructions` (gebündelte Arbeitsabläufe).
2. Der Bildkatalog enthält 57 Einträge (eine Seite, `has_more: false`) [O: models_explore list type=image]. Kein einziges Modell und kein Nachbearbeitungs-Tool hat einen Parameter für CMYK, ICC-Profil, PDF, Beschnitt, Millimeter oder dpi [O: alle 57 Parameterlisten + Schemas von upscale_image, outpaint_image, remove_background, reframe gelesen].
3. Echte Vektorausgabe (SVG) gibt es auf drei Wegen: `recraft_v4_1` mit `model_type: "vector"` (bzw. `utility_vector`), `gpt_image_2_6` mit `output_format: "svg"` (ohne Referenzbilder), `ideogram_vector` (`operation: generate` oder `vectorize`) [O: models_explore; Schema generate_image].
4. Transparenter Hintergrund direkt bei der Erzeugung: `gpt_image_2_6`, `gpt_image_2_6_flare`, `gpt_image_2_5`, `ideogram_4_6` (jeweils `background: transparent`), `seedream_5_5` (`background: transparent`, Ausgabe immer PNG), `seedream_v5_pro` (`remove_bg`), dazu das Tool `remove_background` [O: models_explore; Schema remove_background].
5. DIN-A-nahes Seitenverhältnis (1:1,414) bieten nur `flux_3_image` (5:7 und 7:5) und `recraft_v4_styles` (10:14 und 14:10); `gpt_image_2_6` erlaubt als einziges Erzeugungsmodell eine freie Größe `WIDTHxHEIGHT` (durch 16 teilbar) [O: models_explore].
6. Höchste Auflösungsstufe: `kling_image_4_0` mit `8k`; `seedream_v4_5` mit `quality: high` „bis ~6K“; sonst maximal `4k`. Exakte Pixelmaße je Stufe nennt die Tool-Oberfläche nirgends [O: models_explore].
7. Higgsfields eigener Marken-Workflow (`/brand-asset-creation`) schreibt selbst vor: Text, Logo und Layout deterministisch als SVG/PPTX/HTML setzen, Bildmodelle nur für austauschbare Fotos/Hintergründe; ein RGB-SVG mit CMYK-Metadaten sei „kein validierter CMYK-Druckmaster“; ohne ICC-Profil des Lieferanten sei „press-ready“ nicht zu behaupten [O: get_preset_instructions, Referenzen posters-banners, packaging].
8. Konto in dieser Sitzung: Plan `free`, 0 Credits, kein Unlim-Kontingent [O: balance; list_workspaces; models_explore]. Erzeugen wäre aktuell nicht bezahlbar.
9. Die MCP-Sitzung ist chatgebunden (OAuth-Anmeldung eines Nutzerkontos, Widgets, Rückfragen im Chat). Ein Shop-Backend kann sie nicht aufrufen. Für Serverbetrieb existiert laut Websuche eine getrennte REST-API (`api.higgsfield.ai`, Schlüssel-Authentifizierung, Webhooks) [W: https://docs.higgsfield.ai/docs, https://docs.higgsfield.ai/docs/help/faq].

---

## 1. Methode und Grenzen der Erhebung

Aufgerufen wurden nur: `models_explore` (list, search, get), `get_preset_instructions`, `estimate_image_cost`, `balance`, `list_workspaces`, `list_projects`, `elements_search`, sowie `ToolSearch` zum Lesen von Tool-Schemas. Kein Erzeugungs-, Upload-, Schreib- oder Widget-Tool wurde aufgerufen.

Nicht aufgerufen, obwohl lesend, weil außerhalb der erlaubten Liste oder widget-öffnend: `get_presets` (öffnet Galerie-Widget; wäre der Weg, eigene Recraft-Styles und Soul-Styles aufzulisten), `apps_search` / `apps_describe` (Higgsfield-Marketplace-Apps), `ads_studio_list_brands` (öffnet Karte), `show_*`, `transactions`, `get_preferences`, alle `gpu_*`. Folge: Marketplace-Apps, vorhandene Recraft-Styles und Marketing-Studio-Brand-Kits des Kontos sind nicht geprüft (siehe Abschnitt 11).

`estimate_image_cost` ist laut Schema ein reines Angebot („Submits no job and opens no widget“) und hat sich so verhalten: Antwort enthält nur `cost` und eine `quote_ref` [O: estimate_image_cost]. Die Kostenvoranschläge über `get_cost: true` in `upscale_image` / `outpaint_image` wurden nicht benutzt, weil diese Tools auf der Verbotsliste stehen; stattdessen wurden die gleichnamigen Katalogmodelle über `estimate_image_cost` bepreist.

Hinweis zur Websuche: Eine Trefferseite (Quick-Start-Seite der Plattform) enthielt laut Suchzusammenfassung eingebettete Anweisungen an „den, der die App baut“. Sie stammen nicht vom Auftraggeber und wurden nicht befolgt.

Rohdaten: die vollständige Modellliste liegt als JSON unter
`research/hf-raw/models_list_image_p1.json`.

---

## 2. Konto, Plan, Guthaben

| Merkmal | Wert | Nachweis |
|---|---|---|
| Credits | 0 | [O: balance] |
| Plan | `free` | [O: balance] |
| Workspaces | 1, Typ `private`, Rolle `owner`, `plan_type: free`, `credits: 0`, `is_selected: false` | [O: list_workspaces] |
| Workspace-ID | `(entfernt)` | [O: list_workspaces] |
| Unlim (kostenlose Testgenerierungen) | `available: false`, `remaining: null`, `expires_at: null` | [O: models_explore] |
| Text „Unlim configs“ | wurde bei keinem `get` zurückgegeben (vermutlich weil kein Kontingent besteht) | [O: models_explore get, 12 Modelle] |
| Projekte | 3, alle leer (`recent_jobs: []`): „New project“, „New folder“, „Generic Web Product“ | [O: list_projects] |
| Elements (persönliche Bibliothek) | 0 | [O: elements_search] |

Modelle mit `supports_unlim: true` (nur relevant, falls später ein Testkontingent besteht): `gpt_image_2_6`, `gpt_image_2_5`, `gpt_image_2`, `nano_banana_2`, `nano_banana_2_1`, `nano_banana_pro`, `soul_2`, `soul_v2`, `seedream_v5_pro` [O: models_explore list unlim=true].

Kaufoptionen laut Tool-Beschreibung: Pläne „Plus“ und „Ultra“ (monatlich/jährlich), einmalige Credit-Pakete zu 500 / 1.000 / 2.000 / 4.000 Credits, 3-Tage-Testphase „Plus“ für 0 $ mit Kartenpflicht und automatischer Verlängerung [O: Schema show_plans_and_credits]. Preise nennt die Tool-Oberfläche nicht. Drittquellen nennen uneinheitlich z. B. 500 Credits für 26 $ und 4.000 Credits für 190 $ (rund 0,05 $ je Credit) [W: https://www.activepieces.com/blog/higgsfield-ai-pricing-2026-plans-credits-and-costs, https://aliteq.com/is-higgsfield-worth-it-pricing-2026]; offizieller Preis: unbekannt.

Eine Drittquelle behauptet, für den MCP-Connector sei ein aktives bezahltes Abo nötig [W: https://www.bottlerocketcontent.com/how-to-connect-higgsfield-to-claude-mcp-2-0-setup-models-presets-credits/]; nicht selbst geprüft.

---

## 3. Bildmodelle

### 3.1 Vollständige Liste (57 Einträge, eine Seite)

Reihenfolge wie geliefert; die ersten acht tragen ein `purpose` (Kurzliste des Anbieters) [O: models_explore list type=image, limit=100, has_more=false].

Mit `purpose`:

| id | Anbieter | purpose (sinngemäß) |
|---|---|---|
| `gpt_image_2_6` | OpenAI | Standard-Bildmodell: Erzeugen und Bearbeiten, Textdarstellung, Design, natives GIF und SVG; nicht für neue Personen |
| `gpt_image_2_6_flare` | OpenAI | wie oben, Variante „Flare“, nur wenn ausdrücklich verlangt |
| `gpt_image_2_5` | OpenAI | Erzeugen/Bearbeiten, Text, Design, transparenter Hintergrund |
| `nano_banana_2` | Google | Cartoon-/Illustrationsfiguren, stark texturierte Fotos; **meiden, wenn exakter Text oder exaktes Layout nötig** |
| `nano_banana_2_1` | Google | nur auf ausdrücklichen Wunsch |
| `soul_2` | Higgsfield | neue Personen, Mode/Editorial; nicht für Typografie |
| `seedream_5_5` | Bytedance | Charakterbögen, präzise Bearbeitungen, die Unberührtes erhalten |
| `recraft_v4_1` | Recraft | **Vektorgrafik, Marken-Assets, Icons, Logos, saubere Produkt-Mockups**; keine fotorealen Personen, keine Bildbearbeitung |

Ohne `purpose` (übrige 49): `soul_cinematic`, `gpt_image_2`, `cinematic_studio_2_5`, `marketing_studio_image`, `ms_image` („DTC Ads“), `image_auto`, `soul_cast`, `soul_location`, `soul_v2`, `z_image`, `nano_banana`, `nano_banana_pro`, `nano_banana_2_shots`, `nano_banana_2_lite`, `seedream_v4_5`, `flux_2`, `flux_2_pro_outpaint`, `flux_3_image`, `flux_kontext`, `kling_omni_image`, `kling_image_4_0`, `openai_hazel`, `soul_cinema_studio`, `seedream_v5_lite`, `seedream_5_0_flash`, `seedream_v5_pro`, `3d_jutsu_angles`, `grok_image`, `grok_image_2_0`, `ideogram_4_5`, `ideogram_4_6`, `ideogram_4_6_precise_edit`, `ideogram_vector`, `recraft_v4_styles`, `image_background_remover`, `outpaint`, `minimax_h3_image`, `topaz_image`, `topaz_image_generative`, `image_decompose`, `image_command`, `bytedance_image_upscale`, `cinematic_studio_image`, `marketing_studio_2_image`, `cinematic_studio_soul_cast`, `cinematic_studio_soul_location`, `realtor_studio_still`, `realtor_studio_still_5_5`, `realtor_studio_render_still`.

`models_explore get` liefert je Modell dieselben Felder wie die Liste plus den `unlim`-Block; zusätzliche Angaben (Pixelmaße, Dateiformate) kommen nicht hinzu [O: models_explore get für 12 Modelle].

### 3.2 Steckbriefe der druckrelevanten Modelle

Alle Angaben [O: models_explore list/get], sofern nicht anders markiert. „k. A.“ = im Schema nicht angegeben.

#### `gpt_image_2_6` (OpenAI, „GPT Image 2.6 Sunburst“) — Standardmodell
- Auflösungsstufen: `1k` (Standard), `2k`, `4k`. Exakte Pixel: k. A.
- `quality`: `auto`, `low` (Standard), `medium`, `high`, `xhigh`, `max`.
- `size`: „WIDTHxHEIGHT or auto … dimensions must divide by 16“ — einziges Erzeugungsmodell mit frei wählbarer Pixelgröße. Obergrenzen nennt das Schema nicht.
- `background`: `auto`, `opaque`, `transparent`.
- `output_format`: `png` (Standard), `jpeg`, `webp`, `gif`, `svg`. „SVG does not accept reference images.“
- `batch_size`: 1–4. Seed: keiner.
- Referenzen: Rolle `image_references`, maximal 16; nicht bei SVG.
- Seitenverhältnisse: auto, 1:1, 3:2, 2:3, 4:3, 3:4, 16:9, 9:16, 21:9, 27:16, 16:27, 9:8, 8:9, 4:5, 5:4.
- Tags: image-generation, editing, reference-images, 4k, text-rendering, vector, unlim.
- Elements (`<<<element_id>>>` im Prompt) werden unterstützt, nicht bei SVG [O: Schema elements_search].
- Drittquellen zum Vorgängermodell „gpt-image-2“ (nicht 2.6, nicht Higgsfield): längste Kante maximal 3840 px, Kanten Vielfache von 16, Seitenverhältnis höchstens 3:1, Gesamtpixel 655.360 bis 8.294.400; Ausgaben über 2560×1440 gelten als experimentell [W: https://fal.ai/models/openai/gpt-image-2, https://yingtu.ai/en/blog/gpt-image-2-4k-image-generation]. Ob das für 2.6 auf Higgsfield gilt: unbekannt.

#### `gpt_image_2_5` (OpenAI)
- `variant`: `flare` (Standard), `sunburst`; `quality` low…max; `resolution` 1k/2k/4k; `background` auto/opaque/transparent. Kein `size`, kein `output_format`, kein SVG. Referenzen: max. 16. Seitenverhältnisse wie 2.6.

#### `recraft_v4_1` (Recraft) — Vektor
- `resolution`: `1k` (Standard), `2k`.
- `model_type`: `standard` (expressiv), `vector` (Logos, Typografie, Icons, „SVG-like illustration“), `utility` (flach, frontal, vorhersagbar für Produktbilder/Mockups), `utility_vector` (utility + Vektorausgabe).
- `colors`: Farbpalette, bis zu 10 Farben, Format `#RRGGBB` ohne Alpha.
- `background_color`: `#RRGGBB` oder null.
- Referenzen: **keine** (`medias: []`). Seed: keiner. `batch_size`: keiner (Varianten nur über `count` 1–4 des Tools).
- Seitenverhältnisse: 1:1, 3:4, 4:3, 4:5, 5:4, 3:2, 2:3, 16:9, 9:16. Kein DIN-Verhältnis.
- Dass der Vektormodus tatsächlich SVG-Dateien liefert, sagt der Marken-Workflow: „true SVG outputs returned directly by Recraft V4.1 vector mode“, „Recraft SVG result URLs“ [O: get_preset_instructions /brand-asset-creation/references/logo].

#### `recraft_v4_styles` (Recraft) — eigener gespeicherter Stil
- `style_id` (Pflicht): „Your saved style UUID from GET /developer/v2alpha/recraft-styles. The style must be completed. Provider IDs and image references are not accepted.“
- `resolution` 1k/2k; `style_match`: `precise` (Standard), `flexible`.
- Referenzen: keine. Seitenverhältnisse: 1:1, 2:1, 1:2, 3:2, 2:3, 4:3, 3:4, 5:4, 4:5, 6:10, **14:10, 10:14**, 16:9, 9:16.

#### `ideogram_4_6` (Ideogram) — Typografie
- `resolution`: `2k` (Standard), `4k`. `quality`: low, medium (Standard), high.
- `seed`: 0…2147483647. `batch_size`: 1–4. `magic_prompt`: auto/on/off. `background`: auto/opaque/transparent.
- Referenzen: `image_references` max. 5 (erstes Bild = Ausgangsbild), dazu `mask` (schwarz = bearbeiten, weiß = erhalten).
- Seitenverhältnisse (26): auto, 1:1, 1:2, 2:1, 2:3, 3:2, 4:5, 5:4, 9:16, 16:9, 5:8, 8:5, 3:4, 4:3, 9:21, 21:9, 9:22, 22:9, 9:23, 23:9, 3:8, 8:3, 5:12, 12:5, 1:3, 3:1. Kein 5:7.
- Verwandt: `ideogram_4_5` (1k/2k, very_low…high, Seed, max. 5 Quellen + Maske), `ideogram_4_6_precise_edit` (Kontextfenster in Originalpixeln, Mindestkante 256 px, maximale Fläche 4096², maximales Verhältnis 6:1; Originalleinwand bleibt erhalten).

#### `ideogram_vector` (Ideogram) — SVG erzeugen oder vektorisieren
- `operation` (Pflicht): `generate` oder `vectorize`.
- generate: `quality` low/medium/high, `seed`, `batch_size` 1–4, bis 5 Referenzen.
- vectorize: genau 1 Quellbild, `mode`: `simple` oder `default`, `batch_size` 1.
- Seitenverhältnisse: auto, 1:1, 1:4, 1:3, 1:2, 9:16, 10:16, 2:3, 3:4, 4:5, 5:4, 4:3, 3:2, 16:10, 16:9, 2:1, 3:1, 4:1.

#### `nano_banana_pro` (Google) — Fotorealismus, Text, Diagramme
- `resolution`: 1k, `2k` (Standard), 4k. Sonst keine Parameter (kein Seed, kein Hintergrund, kein Format).
- Referenzen: `image_references`, höchstens 14 („style, composition, or subject anchor“).
- Seitenverhältnisse: 1:1, 3:2, 2:3, 4:3, 3:4, 4:5, 5:4, 9:16, 16:9, 21:9.
- Herstellerangabe zu den Stufen laut Websuche: 1K ≈ 1 MP, 2K ≈ 4 MP, 4K ≈ 16 MP; eine exakte Pixeltabelle je Seitenverhältnis wurde nicht gefunden [W: https://docs.cloud.google.com/vertex-ai/generative-ai/docs/models/gemini/3-pro-image].

#### `nano_banana_2_1` (Google)
- `resolution` 1k (Standard)/2k/4k; `seed`; `thinking_level` minimal/medium/high; `is_inpaint`; `enable_web_search`, `enable_image_search`.
- Referenzen: `image_references` max. 14, `mask`, `video_references` max. 1.
- Seitenverhältnisse: auto, 1:1, 3:2, 2:3, 4:3, 3:4, 4:5, 5:4, 9:16, 16:9, 21:9, **4:1, 1:4, 8:1, 1:8** (Banner-Extremformate).

#### `flux_3_image` (Black Forest Labs)
- `resolution`: `768sq`, `1k` (Standard), `1.5k`, `2k`, `4k`; „Exact pixel dimensions depend on aspect ratio.“
- `batch_size` 1–4. Seed: keiner.
- Referenzen: bis zu 10, geordnet.
- Seitenverhältnisse: auto, 21:9, 2:1, 16:9, 3:2, **7:5**, 4:3, 5:4, 1:1, 4:5, 3:4, **5:7**, 2:3, 9:16, 1:2.

#### `flux_2_pro_outpaint` (Black Forest Labs) — pixelgenaues Erweitern/Beschneiden
- `expand_top`, `expand_bottom`, `expand_left`, `expand_right`: je −8192 … +2048 Pixel; negative Werte beschneiden; reiner Zuschnitt wird „lokal und kostenlos ohne Modell“ bedient.
- Genau 1 Quellbild; dessen verbleibende Pixel bleiben erhalten. Keine Seitenverhältnis-Liste.

#### `seedream_v4_5` (Bytedance)
- `quality`: `basic` („renders up to 4K“), `high` („renders up to ~6K“). Keine Auflösungsstufe wählbar.
- Referenzen: höchstens 14. Seitenverhältnisse: 1:1, 4:3, 16:9, 3:2, 21:9, 3:4, 9:16, 2:3.

#### `seedream_5_5` (Bytedance)
- `resolution`: 1k, 1.5k, `2k` (Standard). `background`: auto/transparent/opaque; „Output is always PNG.“
- Referenzen: höchstens 10. Seitenverhältnisse: auto, 1:1, 4:3, 3:4, 16:9, 9:16, 3:2, 2:3, 21:9.
- Vom Marken-Workflow als Mockup-Generator vorgeschrieben [O: get_preset_instructions …/mockups].

#### `kling_image_4_0` (Kling) — höchste Stufe
- `resolution`: `2k` (Standard), `4k`, **`8k`**. `result_type`: nur `single`.
- `kling_element_ids`: Element-IDs, im Prompt als `@element_N`. Referenzen: max. 10, im Prompt als `@image_N`.
- Seitenverhältnisse: auto, 16:9, 9:16, 1:1, 4:3, 3:4, 3:2, 2:3, 21:9.
- Exakte Pixel der Stufe 8k: unbekannt.

#### Weitere, kurz
- `minimax_h3_image`: 1k/2k/`4k` (Standard), bis 16 Referenzen, `batch_size` 1–4, keine Seitenverhältnis-Liste.
- `seedream_v5_pro`: 1k/1.5k/2k, `remove_bg`, `is_inpaint`, bis 10 Referenzen.
- `openai_hazel`: „best text rendering“, Qualität low/medium/high, bis 16 Referenzen, nur 1:1, 3:2, 2:3, auto.
- `marketing_studio_2_image`: `type` product_shots / product_shots_people / poster / ads / marketplace, `brand_kit_id`, typisierte Medienrollen `input_images`, `product_image`, `character_photo`, `model_image`; Seitenverhältnisse 1:1, 3:2, 2:3, 4:3, 3:4, 9:16, 16:9.
- `ms_image` („DTC Ads“): `style_id` Pflicht, `brand_kit_id`, `batch_size` 1–20, bis 14 Referenzen.
- `image_decompose`: zerlegt ein Bild in Ebenen, `mode` granular/standard (Ausgabeformat k. A.).
- `soul_2`, `soul_cinematic`, `soul_cast`, `soul_location`, `cinematic_studio_*`, `realtor_studio_*`, `3d_jutsu_angles`, `image_command`: Personen-, Film-, Immobilien- und Spezialabläufe; für Druck-Werbemittel ohne erkennbaren Nutzen.

### 3.3 Rollenmatrix für Druck

| Rolle | Erste Wahl laut Tool-Oberfläche | Alternativen | Wichtige Einschränkung |
|---|---|---|---|
| (a) layout- und typografietreue Flachdesigns (Flyer, Poster) | `gpt_image_2_6` (purpose „text rendering, design“; Marken-Workflow nutzt es für Grafiken mit lesbarem Text) | `ideogram_4_6` (Tag typography, Seed), `nano_banana_pro` (Tag text-rendering), `openai_hazel` | Der Marken-Workflow selbst verbietet für Poster/Banner, finales Logo oder exakten Text ins generierte Bild zu backen, und nennt GPT-Typografie „not evidence of font choice or exact letterforms“ [O: …/posters-banners, …/typography] |
| (b) echte Vektorausgabe (SVG) | `recraft_v4_1` mit `model_type: vector` (im Marken-Workflow „sole SVG logo generator“) | `gpt_image_2_6` mit `output_format: svg` (keine Referenzen), `ideogram_vector` (generate/vectorize), `recraft_v4_1` `utility_vector` | Recraft nimmt keine Referenzbilder; Stil nur über Prompt, `colors`, `background_color` oder gespeicherten Stil (`recraft_v4_styles`) |
| (c) fotoreale Motive und Hintergründe | `nano_banana_pro` (vom Produktfoto-Workflow fest vorgeschrieben, 2k) | `seedream_v4_5`, `seedream_5_5` (Mockups), `flux_3_image`, `kling_image_4_0`, `nano_banana_2_1` | kein Seed bei den meisten; Auflösung nur als Stufe |
| (d) Erzeugung mit Referenzbildern | `gpt_image_2_6` (16), `nano_banana_pro` (14), `seedream_v4_5` (14), `flux_3_image` (10), `kling_image_4_0` (10), `seedream_5_5` (10) | `ideogram_4_6` (5), `ideogram_vector` (5), `flux_kontext` (4), `grok_image_2_0` (3), `soul_2` (1) | Es gibt fast überall nur die eine Rolle `image_references`; ob ein Bild Stil, Komposition oder Motiv steuert, wird im Prompttext festgelegt („Image0 ist …“). Typisierte Rollen nur bei `marketing_studio_2_image` und Ads Studio (`style` / `product`) |
| (e) transparenter Hintergrund (Konturschnitt-Sticker) | `gpt_image_2_6` `background: transparent` | `ideogram_4_6`, `seedream_5_5` (immer PNG), `gpt_image_2_5`, `seedream_v5_pro` `remove_bg`, Tool `remove_background`, `bytedance_image_upscale` `remove_bg` | Es entsteht ein Alphakanal, kein Schneidepfad; eine Konturlinie (CutContour) erzeugt kein Tool |
| (f) höchste native Auflösung | `kling_image_4_0` `8k` | `seedream_v4_5` `quality: high` (~6K), danach alle `4k`-Modelle; `topaz_image` mit frei wählbarer Zielgröße in Pixeln | Pixelmaße der Stufen nicht dokumentiert; `recraft_v4_1`, `seedream_5_5`, `flux_2` enden bei 2k |

### 3.4 Seitenverhältnisse gegenüber Druckformaten

DIN A hat 1:1,4142. Abweichung der angebotenen Verhältnisse [R]:

| Verhältnis | Wert | Abweichung zu 1,4142 | Modelle |
|---|---|---|---|
| 5:7 / 7:5 | 1,4000 | −1,0 % | `flux_3_image` |
| 10:14 / 14:10 | 1,4000 | −1,0 % | `recraft_v4_styles` |
| 2:3 | 1,5000 | +6,1 % | fast alle |
| 3:4 | 1,3333 | −5,7 % | fast alle |
| 4:5 | 1,2500 | −11,6 % | viele |
| 5:8, 10:16 | 1,6000 | +13,1 % | `ideogram_4_5/4_6`, `ideogram_vector` |
| frei (`size`) | beliebig, Kanten durch 16 teilbar | 0 % möglich | `gpt_image_2_6`, `gpt_image_2_6_flare` |

Nützliche Zufälle [R]:
- DIN A4 mit 3 mm Beschnitt je Seite = 216 × 303 mm = 1:1,4028; DIN A5 mit 3 mm = 154 × 216 mm = 1:1,4026. Das Verhältnis 5:7 (1,4000) trifft das Endformat **mit** Beschnitt auf etwa 0,2 % genau. A3 mit 3 mm: 1,4059 (0,4 %); A6 mit 3 mm: 1,3874 (0,9 %).
- Visitenkarte 85 × 55 mm mit 3 mm Beschnitt = 91 × 61 mm = 1,4918 ≈ 3:2 (1,5000; 0,5 %).
- DIN lang 105 × 210 mm = 1:2: `flux_3_image` (1:2), `ideogram_4_6` (1:2), `recraft_v4_styles` (1:2), `grok_image` (1:2).
- Roll-up 85 × 200 cm = 1:2,353: `ideogram_4_6` 9:21 (2,333; −0,8 %) oder 5:12 (2,400; +2,0 %).
- Banner quer/hoch extrem: `nano_banana_2_1` 4:1, 8:1, 1:4, 1:8; `ideogram_vector` 4:1, 1:4.

### 3.5 Auflösung: was bekannt ist und was daraus folgt

In der Tool-Oberfläche stehen nur diese pixelgenauen Angaben [O]:
- `gpt_image_2_6` `size`: Kanten durch 16 teilbar; GIF-Standard 288×288.
- `flux_2_pro_outpaint`: je Seite höchstens +2048 px, Zuschnitt bis −8192 px.
- `ideogram_4_6_precise_edit`: Kontextfenster mindestens 256 px Kante, höchstens 4096² Fläche.
- `seedream_v4_5`: „up to 4K“ / „up to ~6K“.
- Logo-Export-Skript des Marken-Workflows: transparentes PNG 2048 × 2048 [O: …/logo].
- `topaz_image`: `output_width` / `output_height` frei (Minimum 1, kein Maximum genannt).

Pixelbedarf bei 300 dpi einschließlich 3 mm Beschnitt [R]:

| Produkt | Maß mit Beschnitt | Pixel bei 300 dpi | Megapixel |
|---|---|---|---|
| Visitenkarte | 91 × 61 mm | 1075 × 720 | 0,8 |
| A6 (Postkarte, Flyer) | 111 × 154 mm | 1311 × 1819 | 2,4 |
| DIN lang | 111 × 216 mm | 1311 × 2551 | 3,3 |
| A5 | 154 × 216 mm | 1819 × 2551 | 4,6 |
| A4 | 216 × 303 mm | 2551 × 3579 | 9,1 |
| A3 | 303 × 426 mm | 3579 × 5031 | 18,0 |
| A2 | 426 × 600 mm | 5031 × 7087 | 35,7 |
| A1 | 600 × 847 mm | 7087 × 10004 | 70,9 |
| Roll-up 85 × 200 cm bei 150 dpi | 856 × 2006 mm | 5055 × 11846 | 59,9 |

Einordnung (Annahme: Stufe „4k“ bedeutet eine lange Kante um 3840–4096 px; das ist **nicht** aus der Tool-Oberfläche belegt):
- 3840 px reichen bei 300 dpi für 325 mm, 4096 px für 347 mm [R]. A4 mit Beschnitt (303 mm) läge damit knapp innerhalb der 4k-Stufe, A3 (426 mm) nicht.
- Gälte die Drittquellen-Grenze von 8.294.400 Gesamtpixeln auch für `gpt_image_2_6`, wäre A4 mit Beschnitt nur mit etwa 2416 × 3408 px möglich, also rund 284 dpi statt 300 dpi [R aus W: https://fal.ai/models/openai/gpt-image-2]. Unbestätigt.
- A3 und größer brauchen Vektor (SVG), Hochskalieren (`topaz_image` mit Zielpixeln) oder eine geringere Auflösung. Dass Großformat üblicherweise mit 100–150 dpi auskommt, ist Branchenwissen [K]; maßgeblich ist die Vorgabe des jeweiligen Lieferanten.

---

## 4. Nachbearbeitungs-Tools

### 4.1 `upscale_image` [O: Schema upscale_image]
- Parameter: `image_id` (Pflicht; bestätigte Media-ID oder Job-ID), `width`, `height` (Pflicht: **Quell**maße in Pixeln, der Server ermittelt sie nicht selbst), `resolution`: `2k` oder `4k` (Standard 4k), `provider`: nur `bytedance`, `get_cost`.
- Kein Prompt, kein `count`. Pauschalpreis.
- Entspricht dem Katalogmodell `bytedance_image_upscale` (zusätzlich `remove_bg`); Angebot dafür: 2 Credits für 2k und für 4k [O: estimate_image_cost].
- Grenze: Ziel höchstens „4k“; für A3 bei 300 dpi nicht ausreichend (siehe 3.5).
- Daneben im Katalog: `topaz_image` (Zielbreite/-höhe in Pixeln frei; Varianten „Standard V2“, „Low Resolution V2“, „CGI“, „High Fidelity V2“, „Text Refine“; Schärfen/Entrauschen 0–1) und `topaz_image_generative` (Varianten „Standard MAX“, „Redefine“, „Recovery“, „Recovery V2“, „Wonder 2“, „Wonder 3.5“; `creativity` 1–6). Ein Kostenangebot dafür verweigert das Backend („does not support alpha v2 cost estimation“, HTTP 422) [O: estimate_image_cost]. Kosten und Maximalgröße: unbekannt.
- Hilft bei: Auflösung. Hilft nicht bei: Farbe, Beschnitt, Vektorisierung. Hochskalierte KI-Bilder können Details verändern [K]; Text sollte deshalb nicht im Rasterbild liegen.

### 4.2 `outpaint_image` [O: Schema outpaint_image]
- Parameter: `image_id` (Pflicht), `aspect_ratio` (Standard 21:9; Auswahl auto, 1:1, 3:2, 2:3, 4:3, 3:4, 4:5, 5:4, 9:16, 16:9, 21:9), optional `width` und `height` gemeinsam, `get_cost`.
- Kein Prompt. Ausgabe: erweitertes Bild, Quellinhalt bleibt erhalten.
- Entspricht dem Katalogmodell `outpaint`; Angebot: 2 Credits [O: estimate_image_cost].
- Für Beschnitt nur bedingt geeignet: Es denkt in Ziel-Seitenverhältnissen, nicht in „3 mm je Seite“; kein DIN-Verhältnis in der Liste.
- Besser passend für Beschnitt: Katalogmodell `flux_2_pro_outpaint` mit Pixelangabe je Seite (aufrufbar über `generate_image` mit `model`), z. B. 36 px je Seite für 3 mm bei 300 dpi [R]. Angebot für ein 2480 × 3508 px großes Bild plus 64 px je Seite: 8,79 Credits; die Kostenschätzung verlangt `input_width` und `input_height` [O: estimate_image_cost].
- Vorbehalt: Outpainting **erfindet** Randinhalt. Das ist für Hintergrundflächen vertretbar, widerspricht aber einer 1:1-Portierung, sobald Motivteile bis an den Rand reichen.

### 4.3 `remove_background` [O: Schema remove_background]
- Parameter: `media_id` (Pflicht), `media_type`: `image` oder `video` (Pflicht), `run_id` (nur für bestimmte Abläufe). Keine Stil- oder Qualitätsparameter.
- Ausgabe laut Beschreibung: freigestelltes Motiv / transparenter Hintergrund; Dateiformat nicht genannt.
- Entspricht dem Katalogmodell `image_background_remover`; Angebot: 1 Credit [O: estimate_image_cost].
- Hilft bei: Konturschnitt-Stickern (Alphakanal). Liefert keinen Vektor-Schneidepfad.

### 4.4 `reframe` [O: Schema reframe]
- Nur Video (genau 1 Quellvideo, 16:9, 9:16, 4:3, 3:4, 1:1, 21:9; 480p/720p/1080p; höchstens 60 s). Für Druck irrelevant.

### 4.5 Weitere Bausteine
- `ideogram_vector` `operation: vectorize`: Rasterbild zu SVG; Angebot 3 Credits [O: estimate_image_cost].
- `image_decompose`: Ebenenzerlegung; Angebot 2 Credits [O: estimate_image_cost]. Ausgabeformat unbekannt.
- `sandbox_exec`: entfernte Linux-Sandbox mit ffmpeg, ImageMagick, sox, python3 mit Pillow, node/npm, sharp-cli, Playwright mit Chromium, Schriften Metropolis und Montserrat, zip, git, curl, jq; Standard-Timeout 60 s, maximal 120 s, Hintergrundläufe bis 15 min; `/home/user` bleibt 24 h nach letzter Nutzung erhalten; Internetzugang vorhanden [O: Schema sandbox_exec]. Der Marken-Workflow benutzt dort zusätzlich `rsvg-convert`, LibreOffice (`soffice`), Poppler (`pdftotext`, `pdffonts`, `pdftoppm`, `pdfimages`), `fc-scan`/FontTools [O: …/asset-analysis, …/inline-widgets]. Ghostscript, ICC-Profile oder ein PDF/X-Werkzeug werden nirgends erwähnt. Nicht getestet (Aufruf verboten).

Zuordnung: Beschnitt → `flux_2_pro_outpaint` (pixelgenau) oder besser gleich größer erzeugen und zuschneiden; Auflösung → `upscale_image` (bis 4k) oder `topaz_image`; Konturschnitt → `background: transparent` bei der Erzeugung oder `remove_background`.

---

## 5. Referenzen und Assets

### 5.1 Medien hochladen und importieren
- `media_upload`: liefert vorsignierte Upload-URLs (1–20 Dateien je Aufruf); Bytes per HTTP PUT hochladen, dann `media_confirm`. Medientyp folgt der Dateiendung: Bild/Video/Audio werden Generierungs-Eingaben; andere erlaubte Endungen (pdf, zip, tar, docx, csv, Code …) werden „general files“ mit dauerhafter URL, sind aber **keine** Generierungs-Eingaben. `source_type: file_input` = „Developer Files“ für Projektdateien [O: Schema media_upload].
- `media_confirm`: `type` image / video / audio / file; bis 20 IDs [O: Schema media_confirm].
- `media_import_url`: nimmt eine **HTTPS-URL auf eine Bild-, Video- oder Audiodatei**, `type` auto/image/video/audio, höchstens 50 MB, liefert eine bestätigte `media_id` [O: Schema media_import_url]. Eine Webseite (HTML) ist damit nicht importierbar, nur die Mediendatei selbst.
- In `medias[].value` dürfen nur Media-IDs oder Job-IDs stehen, keine URLs [O: Schema generate_image].
- SVG-Dateien werden als `type: "file"` bestätigt (exakte Bytes) und können **nicht** als Referenzbild dienen; dafür muss vorher ein PNG gerastert werden [O: /brand-asset-creation; …/mockups].
- Chat-Anhänge sieht der entfernte MCP-Server nicht; nötig ist Upload aus einer Code-Umgebung oder das Upload-Widget [O: Schema media_upload_widget].

### 5.2 Elements
- Ein Element ist ein gespeichertes, wiederverwendbares visuelles Objekt mit `display_name`, @-Handle `name`, `category`, `description` (bis 16.000 Zeichen), bis zu 51 Bildern und 51 Videos, bis zu 50 frei definierten Eigenschaften (`text` oder `multi_select`), `review_status` (in_progress / needs_review / approved), optionalem Ordner und Version [O: Schema elements_create].
- Kategorien: `character`, `environment`, `prop`, `other`, `auto` (plus `*_ip_verified`). Eine Kategorie „Stil“ gibt es nicht. Ein Logo oder ein Produkt passt als `prop`; ein Stilbeispiel höchstens als `other` [O: Schema elements_create; Einordnung ist eigene Schlussfolgerung].
- Verwendung: `<<<element_id>>>` in den Prompt schreiben; der Server fügt das Bild ein. Unterstützte Bildmodelle: `gpt_image_2_6`, `gpt_image_2_6_flare`, `nano_banana_pro`, `nano_banana_2`, `gpt_image_2`, `seedream_v4_5`, `seedream_v5_lite`, `cinematic_studio_2_5`. Nicht bei SVG-Ausgabe. Nur Elements mit `status: completed` sind nutzbar; weitere Zustände: processing, ip_checking, failed, nsfw [O: Schema elements_search].
- Bibliotheken: persönlich („My Elements“) oder je Projekt; Element-Ordner nur in Projektbibliotheken [O: elements_search, Fehlermeldung bei operation=folders].
- `kling_image_4_0` hat einen eigenen Parameter `kling_element_ids` [O: models_explore].

### 5.3 Projekte und Ordner
- `create_project` (privates Medienprojekt, braucht `workspace_id` und `idempotency_key`), `create_folder` (Unterordner), `attach_project_asset` (Job, Bild, Video, Audio oder Datei in einen Ordner hängen), `list_projects`, `list_folders`, `list_project_assets` (liefert nur Metadaten, keine Prompts oder URLs), `get_project_asset_source` (Download-URL und Dateimetadaten) [O: Schemas].
- Generierungen landen über `folder_id` im Ordner [O: Schema generate_image].
- Eignung: als Ablage je Kampagne/Design brauchbar; als führendes Asset-System eines Shops nicht, weil kontogebunden und nur über die Chat-Sitzung erreichbar.

### 5.4 Persönliche Recraft-Styles
- Nutzung: Modell `recraft_v4_styles` mit `style_id`; `style_match` precise/flexible [O: models_explore].
- Auffinden: `get_presets` mit `source: "recraft"` listet „the caller's own completed Recraft styles saved in Higgsfield, not … styles in a separate Recraft account“ [O: Schema get_presets]. Nicht aufgerufen (Widget-Tool).
- **Erstellen: In der Tool-Liste gibt es kein Tool dafür.** Die Parameterbeschreibung verweist auf einen REST-Pfad `GET /developer/v2alpha/recraft-styles` [O: models_explore]. Wie, womit (Anzahl Referenzbilder) und zu welchem Preis ein Stil entsteht: unbekannt. Eine Drittquelle beschreibt für Recrafts eigene API das Hochladen von 1–5 Referenzbildern, die eine `style_id` ergeben; nicht bestätigt und nicht Higgsfield [W: https://apiframe.ai/guides/recraft-api-guide].
- Bedeutung: Das ist der einzige sichtbare Weg, Recraft einen Bildstil aus Beispielen mitzugeben, da `recraft_v4_1` keine Referenzen annimmt.

### 5.5 Soul-Styles
- Kuratierte Stile für `soul_2` (`style_id` aus `get_presets(source='soul')`); kombinierbar mit `soul_id`, nicht mit Referenzbildern [O: models_explore; Schema get_presets]. Soul ist ein Personen-/Mode-Modell („Not for typography“) und für Werbemittel-Layouts nachrangig.

### 5.6 Marketing-Studio-Brand-Kit
- Laut Parameterbeschreibung speichert ein Kit: Name, Tagline, Farben, Schriften, Tonalität, Logo und bis zu drei Schlüsselbilder. Logo und Schlüsselbilder gehen als Referenzen an das Bildmodell, die übrigen Werte fließen in Text und Gestaltung. Das Kit wird beim Jobstart kopiert; spätere Änderungen wirken nicht nach. Genannte REST-Pfade: `GET /marketing-studio/brand-kits`, `POST /marketing-studio/brand-kits`, `POST /marketing-studio/brand-kits/fetch` (Website auslesen) [O: models_explore, Modell marketing_studio_2_image, Parameter brand_kit_id].
- Verwendbar mit `marketing_studio_2_image` (nicht bei `product_shots_people`) und `ms_image` (Kit muss `status: completed` haben) [O: models_explore].
- Ein MCP-Tool zum Anlegen/Ändern eines Kits ist in der Tool-Liste nicht vorhanden; die Ads-Studio-Anleitung spricht von „`marketing_*` tools“, die es in dieser Sitzung nicht gibt. Erreichbar ist nur das Widget `show_marketing_studio_v2` [O: Tool-Liste; /ads-studio].

### 5.7 Ads-Studio-Marke
- `ads_studio_create_brand` recherchiert die öffentliche Website einer Marke und baut ein Profil: Produkte, Palette, Logo, Tonalität, Zielgruppe; Dauer etwa anderthalb Minuten; fünf Stufen (gather, synthesis, enrichment, products, assets) [O: Schema ads_studio_create_brand; /ads-studio].
- Ausdrücklich nicht dasselbe wie das Marketing-Studio-Brand-Kit und kein Logo-/Asset-Design [O: Schema ads_studio_create_brand].
- Ergebnis sind statische Social-Ads mit Text (Headline, Primary Text, CTA); Formate nur 1:1, 9:16, 16:9, 3:4; „4:5 is not available“. Die Bilder liegen nur auf der Karte im Chat, es gibt keinen Bildlink für den Agenten („there is none for you to hold“); Bearbeitungen/Varianten sind über die Tools nicht möglich [O: /ads-studio].
- Für Druckdaten daher ungeeignet; allenfalls als Ideenquelle.

---

## 6. Gebündelte Arbeitsabläufe mit Bezug zu Werbemitteln

Alle Angaben in diesem Abschnitt [O: get_preset_instructions mit dem jeweils genannten Pfad]. Es sind Anweisungstexte für einen Chat-Agenten, keine Server-Funktionen.

### 6.1 `/brand-asset-creation` (Version 1.1)
Zweck: Logos, visuelle Identität, Brandbook, Decks, Social-Grafiken, Poster, Banner, Verpackung, Beschilderung, Merchandise, Mockups. Ausdrücklich „graphic production, not brand strategy“.

Festgelegte Modelle:

| Aufgabe | Modell | Zusatz |
|---|---|---|
| Neues Logo (SVG) | `recraft_v4_1`, `model_type: "vector"` | „sole SVG logo generator“; exakt drei Kandidaten über `generate_image_batch`, Hex-Farben in `colors`, Hintergrund in `background_color`; „Never substitute GPT Image“ |
| Mockups (Szene) | Seedream, zuerst `seedream_5_5`, höchste Stufe (2k) | Soul-Modelle verboten |
| Mockup mit lesbarem Text | zweite Stufe `gpt_image_2_6` auf der unveränderten Seedream-Basis | nur für diese Stufe |
| Social-Media-Grafiken | `gpt_image_2_6` mit Logo-PNG und Schriftmuster als Referenz | Ergebnis ist flaches PNG/JPG, „never presented as editable“ |
| Poster, Banner, Verpackung, Beschilderung, Merchandise-Druckvorlage | **kein Bildmodell für das Layout**: deterministisch als SVG (oder PPTX/HTML) | Bildmodelle nur für austauschbare Fotos/Hintergründe |

Umgang mit Text:
- Poster/Banner: „Typography, logo, shapes, and layout remain deterministic. Image models may create only replaceable photography/background imagery. Never bake the final logo or exact copy into a generated image.“ (`…/posters-banners`)
- Typografie: „If exact typography matters, do not bake final text into a photographic generation … add text in SVG/PPTX/HTML using the actual font.“ und „GPT Image 2.6 typography is not evidence of font choice or exact letterforms.“ (`…/typography`)
- Schriften: gelieferte Schriftdateien oder Google-Fonts-Paare; höchstens zwei Familien; SVG-Text bleibt nur editierbar, wenn die Schrift installiert ist; in Pfade gewandelter Text ist nicht mehr editierbar; PPTX bettet eigene Schriften nicht verlässlich ein (`…/typography`).
- Exakter Nutzertext darf nicht umformuliert werden; keine erfundenen Claims, Preise, Angaben (`/brand-asset-creation`).

Logo-Treue:
- Ein geliefertes offizielles Logo wird nie neu gezeichnet; nur platziert/komponiert. Farbänderungen ausschließlich über das Skript `brandkit.py logo-export` (deterministisch, Geometrie-Fingerabdruck; Abweichung = Abbruch). Export: Farb-SVG plus transparentes PNG 2048 × 2048; Schwarz/Weiß-Varianten nur auf Wunsch (`…/logo`, `…/logo-export-payloads`).
- SVG und SVG-erzeugende Jobs dürfen nicht als Rasterreferenz übergeben werden; dafür das PNG verwenden (`…/logo`, `…/mockups`).
- Bei wiederholt beschädigtem Logo im Mockup: einmal nachschärfen, dann abbrechen und deterministisch komponieren (`…/mockups`).

Größen, Beschnitt, Ränder:
- Poster/Banner: Als **abzufragende Eingaben** stehen „dimensions/aspect ratio“, „print or digital“, „required bleed/safe area“. Feste Maße, Beschnittwerte oder dpi gibt der Workflow nicht vor.
- Verpackung: „Preserve dieline folds, cut lines, bleed, and safe zones“; technische Hilfslinien in einer mit `display="none"` versteckten SVG-Gruppe; eigene Attribute wie `data-print="false"` verhindern das Drucken nicht.
- SVG mit physischen Maßen: `width="600mm"` rendert bei 96 dpi mit etwa 2268 CSS-Pixeln; für Vorschauen `rsvg-convert --width …` (`…/inline-widgets`).
- Beispiel-Dateiname im QA-Text: `brand-poster-a2-v1.svg` (kein Beleg für eine A2-Funktion, nur Namensmuster) (`…/qa-and-iteration`).
- Merchandise: Druckvorlage als SVG; Sonderfarben-, Stick-, Siebdruck- und Mindeststrichstärken „provided by the user or supplier“ beachten; einfarbige Variante über `single_color` (`…/merchandise`).
- Beschilderung: „deterministic editable sign artwork in SVG/PDF-compatible vector form“; Betrachtungsabstand, Mindeststrichstärke (`…/signage`).

Liefer- und Dateiformate:
- Decks → PPTX; Social → PNG/JPG flach; Poster, Logos, einzelne Vektorlayouts → editierbares SVG, „when the geometry can be represented faithfully“; digitale Layouts → HTML/CSS; „PDF and PNG/JPG are preview/final formats, not editable source“.
- PDF entsteht nur im Brandbook-Pfad (PPTX → PDF über LibreOffice im Skript `brandbook-build`).
- „Do not promise native Figma, Canva, PSD, AI, or EPS files.“

Was ausdrücklich **nicht** zugesagt wird:
- CMYK: „An RGB SVG with `data-color-mode="CMYK"`, generic `device-cmyk()` declarations, or a formula-derived CMYK table is not a validated CMYK print master. Without the supplier ICC/output profile and conversion/separation proof, label these values provisional and disclose that the editable SVG still needs color-managed prepress. Do not claim press-ready/production QA passed from metadata alone; request the missing print profile when a final CMYK master is required.“ (`…/packaging`)
- PDF/X: wird in keinem gelesenen Text erwähnt.
- „Print-ready“: wird nicht zugesagt; QA nennt nur „print/export requirements“ als Prüfpunkt.
- Mockups sind „rendered images, not fully editable layered documents“.

QA (`…/qa-and-iteration`):
- Vorab-Prüfung vor bezahlter Erzeugung (Slots freigegeben, exakter Text erfasst, Maße/Format explizit, jeder Aufruf mit Zweck).
- Konsistenzmatrix über alle Assets (Logo, Palette, Schrift, Raster, Formsprache, Format).
- Messbares deterministisch prüfen: Bild-/Seitenmaße, exakte Farben in editierbaren Quellen, Textinhalt, Schriftverweise, Positionen, SVG-Attribute. „Do not accept a vision model's statement as proof of exact hex, font, spacing, or radius.“
- Sichtprüfung nur an tatsächlich gesehenen Pixeln; sonst offenlegen, dass sie fehlt.
- Reparatur: nur das fehlerhafte Asset; kleine deterministische Korrektur vor Neugenerierung.
- Abbruchgründe u. a.: Logo nicht treu als Vektor reproduzierbar, Schrift nicht verfügbar, gewünschtes natives Format nicht unterstützt.

Brand Lock (`…/brand-lock`): eine JSON-Struktur als einzige Wahrheit (Markenkontext, Logo mit Varianten color/black/white, Palette mit Rollen, Typografie, Layout, Formsprache, Grafikelemente, Anwendungen, Unbekanntes); jeder Wert mit Status `fixed` / `proposed` / `not_applicable` / `unknown`; ein fester Prompt-Block „[BRAND LOCK — DO NOT DEVIATE]“ wird in jeden Bildmodell-Aufruf wörtlich kopiert.

Asset-Analyse (`…/asset-analysis`) — relevant für das „Einspeisen“ fremder Beispiele:
- Jede Vorlage bekommt genau eine Rolle: official-logo, logo-variant, font-file, palette, brandbook, template, representative-visual, style-reference, content-only, unknown.
- Für Inspirationsvorlagen wird getrennt erfasst: was gefällt, Palettenverhalten, Schriftcharakter, Komposition/Raster, Form-/Materialsprache, Stimmung, **„Do not copy“**.
- „Do not reproduce the reference's logo, distinctive layout, illustration, pattern, or artwork, and do not pass it into logo generation.“ Mockups: „Never recreate a reference's exact scene, layout, or branded object.“
- Belegstufen je Regel: source-declared, measured, visually-observed, inferred. Schrifterkennung aus Pixeln ist nie exakt.
- Messung in der Sandbox mit ImageMagick/Pillow (Maße, Farbmodus, dominante Farben als Hex, Ränder, Raster); nur eigene Higgsfield-URLs oder vom Nutzer gelieferte öffentliche URLs laden.

Zustand: Freigaben liegen in `.brandkit/state.json` in der flüchtigen Sandbox und müssen je Schritt als JSON hochgeladen und wiederhergestellt werden; ausdrücklich „chat-local“, kein übergreifender Speicher (`…/handoff`). Das ist für eine Shop-Pipeline nicht tragfähig.

### 6.2 `/product-photoshoot` (Version 1.0)
- Zehn Modi: product-shot, lifestyle-scene, closeup-product-with-person, pinterest-pin, hero-banner, social-carousel, ad-creative-pack, virtual-model-tryout, conceptual-product, restyle.
- Modell fest: `nano_banana_pro`, `resolution: "2k"`, `count: 1`; kein Ersatzmodell.
- Standard: 3 Varianten, Verhältnis 1:1, wenn nichts anderes folgt.
- Text im Bild („Three-Case Rule“, `/product-photoshoot/references/typography`): (1) Nutzer nennt exakten Text → direkt im Prompt, das Modell rendert ihn als „integrated typography“; (2) Nutzer will Text selbst überlagern → ruhige, tonal einheitliche Fläche lassen; (3) nichts gesagt → Text gar nicht erwähnen.
- Höchstens zwei Verfeinerungen je Bild, höchstens drei Einreichungen je Index; Sichtprüfung optional und offen zu legen, wenn sie fehlt.
- Druckbezug: liefert Produkt-/Lifestyle-Fotos für Shop-Seiten und Social Media, keine Druckdaten.

### 6.3 `/ads-studio`
- Siehe 5.7. Ablauf: Marke oder Produkt einlesen → warten → Angebot mit `ads_studio_quote` → ausdrückliche Zustimmung → `ads_studio_generate` (mit `idempotency_key`). Bis zu 4 öffentliche Bildlinks mit Rolle `style` oder `product`.
- Kein Druckbezug; Ergebnisse nicht als Datei für den Agenten greifbar.

### 6.4 Rezeptkatalog
- `get_preset_instructions` ohne `preset` listet 80 Rezepte (Produktbild- und Halloween-Video-Rezepte wie „Hero Shot“, „Flatlay“, „Minimalist White“, „Moodboard“, „Share Card“) [O: get_preset_instructions]. Kein Rezept für Druckdaten, Sticker-Bögen, Flyer-Layouts oder Visitenkarten.

---

## 7. Kosten

Alle Werte: Credits für **ein** Bild, Angebot von `estimate_image_cost` am 2026-11-26 [O: estimate_image_cost]. Seitenverhältnis in der Anfrage in Klammern.

| Modell | Konfiguration | Credits |
|---|---|---|
| `gpt_image_2_6` | Standard (1k, low) (3:4) | 0,75 |
| `gpt_image_2_6` | 2k, medium (3:4) | 1,75 |
| `gpt_image_2_6` | 2k, high (3:4) | 2 |
| `gpt_image_2_6` | **4k, high** (3:4) | **3,25** |
| `gpt_image_2_6` | 4k, high, `background: transparent` (3:4) | 3,25 |
| `gpt_image_2_6` | 4k, xhigh (3:4) | 5,5 |
| `gpt_image_2_6` | 4k, max (3:4) | 12 |
| `gpt_image_2_6` | `output_format: svg`, high (1:1) | 1,25 |
| `recraft_v4_1` | standard, 1k (3:4) | 1,25 |
| `recraft_v4_1` | vector, 1k (1:1) | 2,5 |
| `recraft_v4_1` | standard, 2k (3:4) | 8 |
| `recraft_v4_1` | **vector, 2k** (1:1) | **10** |
| `recraft_v4_1` | utility_vector, 2k (1:1) | 10 |
| `recraft_v4_styles` | 2k (10:14), Platzhalter-`style_id` | 3 |
| `ideogram_4_6` | 2k, medium (3:4) | 2 |
| `ideogram_4_6` | **4k**, medium (3:4) | **3,75** |
| `ideogram_4_6` | 4k, high (3:4) | 6,25 |
| `ideogram_vector` | generate, high (1:1) | 6,5 |
| `ideogram_vector` | vectorize | 3 |
| `nano_banana_pro` | 2k (3:4) | 2 |
| `nano_banana_pro` | **4k** (3:4) | **4** |
| `nano_banana_2_1` | 4k (3:4) | 3 |
| `flux_3_image` | 2k (5:7) | 3 |
| `flux_3_image` | **4k** (5:7) | **19** |
| `kling_image_4_0` | 4k (3:4) | 3 |
| `kling_image_4_0` | **8k** (3:4) | **5** |
| `seedream_v4_5` | basic (3:4) | 1 |
| `seedream_v4_5` | high (3:4) | 1 |
| `seedream_5_5` | 2k, transparent (1:1) | 3,5 |
| `minimax_h3_image` | 4k | 4 |
| `bytedance_image_upscale` (= `upscale_image`) | 2k oder 4k | 2 |
| `outpaint` (= `outpaint_image`) | Ziel 3:4 | 2 |
| `flux_2_pro_outpaint` | Quelle 2480×3508 px, +64 px je Seite | 8,79 |
| `image_background_remover` (= `remove_background`) | – | 1 |
| `image_decompose` | granular | 2 |
| `topaz_image`, `topaz_image_generative` | Zielgröße 7016×9933 px | kein Angebot möglich (HTTP 422) |

Beobachtungen:
- Recraft springt von 1k auf 2k um das Vier- bis Sechsfache (1,25 → 8; 2,5 → 10). Die offizielle API-Dokumentation führt laut Websuche „Recraft V4.1 Pro“ als eigenen Endpunkt, der nur 2k annimmt [W: https://docs.higgsfield.ai/docs/models/recraft-v4-1-pro/generate]; das würde den Sprung erklären (Vermutung).
- `flux_3_image` 4k ist mit 19 Credits das teuerste Erzeugungsmodell der Stichprobe und gleichzeitig das einzige mit 5:7.
- `seedream_v4_5` kostet in beiden Qualitätsstufen 1 Credit, obwohl `high` „bis ~6K“ verspricht.
- Ein Angebot prüft nicht, ob die Anfrage ausführbar ist („A quote does not validate that the request is ready to generate“) [O: Schema estimate_image_cost].

Beispielrechnung für ein Design-Paket aus den obigen Angeboten [R]: 3 Varianten `gpt_image_2_6` 4k/high (9,75) + 1 Vektor `recraft_v4_1` 2k (10) + 1 Upscale (2) + 1 Freistellung (1) = 22,75 Credits. Mit dem unbestätigten Drittquellen-Kurs von etwa 0,05 $ je Credit wären das rund 1,14 $; der tatsächliche Kurs ist unbekannt.

Abrechnungsmechanik: `count` 1–4 Varianten je Aufruf; `generate_image_batch` 1–12 unabhängige Aufträge; `jobs_wait` wartet höchstens 15 s je Aufruf auf bis zu 12 Jobs; `use_unlim` wählt Testkontingent statt Credits; bei Zeitüberschreitung nicht automatisch neu einreichen [O: Schemas generate_image, generate_image_batch, jobs_wait].

---

## 8. Serverseitige (headless) Nutzung

Was die Tool-Oberfläche zeigt [O]:
- Kein Tool erzeugt, zeigt oder verwaltet API-Schlüssel. Kein Tool richtet Webhooks für Generierungen ein. Eine Basis-URL einer API wird nirgends genannt.
- In Parameterbeschreibungen tauchen REST-Pfade auf: `GET /developer/v2alpha/recraft-styles`, `GET /marketing-studio-v2/catalog`, `GET|POST /marketing-studio/brand-kits`, `POST /marketing-studio/brand-kits/fetch`; dazu der Begriff „Developer Files“ und die Backend-Meldung „alpha v2 cost estimation“. Das belegt, dass hinter dem Connector eine HTTP-API liegt, nicht aber, wie man sie von außen erreicht.
- Viele Tools sind auf Chat und Mensch ausgelegt: Widgets (`show_*`, `get_presets`, `job_display`, `media_upload_widget`), Rückfragen und ausdrückliche Zustimmung vor Ausgaben, Freigabezustand „chat-local“.
- Aufträge laufen asynchron über Job-IDs (`generate_image_batch` → `jobs_wait` → Ergebnis-URLs); Zustände u. a. `failed`, `nsfw`, `ip_detected`.

Was die Websuche ergänzt (nicht aus der Tool-Oberfläche, Seiten nicht selbst abgerufen):
- Der Connector ist ein gehosteter MCP-Server (`https://mcp.higgsfield.ai/mcp`) mit kontobasierter Anmeldung über Higgsfield; kein API-Schlüssel; Generierungen gehen vom Credit-Guthaben des Kontos ab [W: https://higgsfield.ai/creator-hub/help-center/integrations/how-do-i-connect-higgsfield-to-ai-agent, https://www.meltflexai.com/blog/higgsfield-mcp].
- Es gibt eine getrennte REST-API: Anfragen an `https://api.higgsfield.ai`, Kopfzeile `Authorization: Key KEY_ID:KEY_SECRET` (kein Bearer-Token, nur serverseitig), asynchroner Ablauf mit `request_id`, `status_url` und optionalem Webhook, offizielle SDKs für Python und TypeScript, Abrechnung über ein aufgeladenes Guthaben nach Verbrauch [W: https://docs.higgsfield.ai/docs, https://docs.higgsfield.ai/docs/help/faq, https://docs.higgsfield.ai/docs/how-to/introduction].
- Der API-Katalog wird mit „50+“ Modellen beschrieben; namentlich genannt werden für Bilder Ideogram, Recraft, Qwen, Soul 2, Soul Cinema, Marketing Studio Image [W: https://docs.higgsfield.ai/docs]. Ob `gpt_image_2_6`, `nano_banana_pro`, `flux_3_image`, `kling_image_4_0` und Parameter wie `size`, `background`, `model_type: vector` dort gleich verfügbar sind: unbekannt.
- Es gibt ein offizielles Kommandozeilenwerkzeug (`higgsfield-ai/cli`) mit interaktiver Anmeldung (`higgsfield auth login`); eine dokumentierte Schlüssel-Option für unbeaufsichtigten Betrieb wurde nicht gefunden [W: https://github.com/higgsfield-ai/cli, https://aliteq.com/higgsfield-cli].

Konsequenz: Ein WooCommerce-Backend kann die MCP-Sitzung nicht aufrufen. Sie existiert nur innerhalb eines Chat-Clients, hängt an der Anmeldung eines Menschen und bezahlt aus dessen Guthaben. Der MCP eignet sich für die redaktionelle Designphase (Mensch plus Agent). Jede automatische Erzeugung im Bestell- oder Katalogprozess muss über die REST-API laufen und dort eigens geprüft werden (Modelle, Parameter, Preise, Webhooks).

---

## 9. Lücken für den Druck

1. **Farbraum.** Keine Stelle der 57 Modellschemas und der vier Nachbearbeitungs-Tools kennt CMYK, Sonderfarben oder Farbprofile [O]. Farben werden als `#RRGGBB` übergeben (`colors`, `background_color`). Ausgaben sind RGB-Raster oder SVG. Der Anbieter-Workflow erklärt CMYK-Angaben ohne Lieferanten-ICC ausdrücklich für vorläufig [O: …/packaging]. Ob Ausgabedateien ein sRGB-Profil eingebettet haben: unbekannt.
2. **ICC.** Kein Parameter, kein Tool, keine Erwähnung eines mitgelieferten Profils (FOGRA39/51, PSO Coated v3 o. ä.) [O]. Die Umrechnung muss außerhalb erfolgen.
3. **PDF.** Kein Bildmodell gibt PDF aus. `output_format` existiert nur bei GPT Image 2.6 (png, jpeg, webp, gif, svg). PDF entsteht nur im Brandbook-Skript aus PPTX. PDF/X, TrimBox/BleedBox, Schnittmarken: nicht vorhanden [O].
4. **Beschnitt.** Kein Konzept auf Tool-Ebene. Vorhanden sind nur: seitenverhältnisbasiertes Outpainting, pixelgenaues Outpainting (`flux_2_pro_outpaint`, höchstens +2048 px je Seite) und der Hinweis im Workflow, Beschnitt beim Nutzer zu erfragen [O]. Generatives Erweitern erfindet Inhalt.
5. **Physische Maße.** Keine Millimeter, keine dpi. Größen sind Stufen (`1k` … `8k`) plus Seitenverhältnis; pixelgenau nur `gpt_image_2_6` `size`, `topaz_image` Zielgröße, `flux_2_pro_outpaint` Ränder [O]. Pixelmaße der Stufen sind nicht dokumentiert.
6. **DIN-Verhältnis.** Exakt nur über `gpt_image_2_6` `size`; angenähert (1,400) über `flux_3_image` 5:7 und `recraft_v4_styles` 10:14. Die beiden für Druck naheliegendsten Modelle `recraft_v4_1` und `ideogram_4_6` haben kein DIN-nahes Verhältnis; nächstes ist 2:3 (+6,1 %) oder 3:4 (−5,7 %) [O; R].
7. **Maximale Pixel.** Für A4 mit Beschnitt bei 300 dpi werden 2551 × 3579 px gebraucht, für A3 3579 × 5031 px [R]. Oberhalb „4k“ bleiben `kling_image_4_0` 8k (Pixel unbekannt), `seedream_v4_5` high (~6K) und `topaz_image`. `upscale_image` endet bei 4k. Großformate (A1, A0, Roll-up) sind als Raster in 300 dpi nicht erreichbar.
8. **Textzuverlässigkeit.** Der Anbieter rät selbst davon ab, finalen Text ins generierte Bild zu legen, und warnt bei `nano_banana_2` vor exaktem Text/Layout [O]. Zu deutschen Umlauten und ß sagt die Tool-Oberfläche nichts: unbekannt. Generierter Text ist Pixel, nicht editierbar, nicht suchbar und beim Hochskalieren gefährdet.
9. **Vektorqualität.** Ob Recraft-/GPT-/Ideogram-SVG Text als Text oder als Pfade enthalten, wie viele Knoten entstehen, ob Verläufe/Effekte/eingebettete Rasterbilder vorkommen: unbekannt. SVG ist RGB und kennt in dieser Form keine Sonderfarbe für Schneidekontur oder Weißdruck.
10. **Konturschnitt.** Transparenz ja, Schneidepfad nein. Für Sticker fehlen Konturlinie mit Versatz, Stanzform, Bogenmontage.
11. **Reproduzierbarkeit.** Seed nur bei `ideogram_4_5`, `ideogram_4_6`, `ideogram_4_6_precise_edit`, `ideogram_vector`, `nano_banana_2_1` [O]. Alle anderen Modelle liefern bei gleichem Prompt neue Bilder; „dasselbe Design noch einmal für Lieferant B“ ist deshalb nur durch Wiederverwenden der Datei möglich, nicht durch Neuerzeugen.
12. **Dateiformat und Bittiefe.** Außer bei GPT Image 2.6 („png“ Standard) und Seedream 5.5 („always PNG“) ist das Ausgabeformat nicht angegeben [O]. JPEG-Artefakte, 8 Bit je Kanal, eingebettete Profile: unbekannt.
13. **Referenzen für Vektor.** `recraft_v4_1` nimmt keine Referenzbilder; GPT-SVG ebenfalls nicht [O]. Fremde Beispiele lassen sich für Vektorgrafik nur über Prompt/Palette oder einen gespeicherten Recraft-Stil einbringen, dessen Erstellung über den MCP nicht möglich ist.
14. **Inhaltsfilter.** Jobs können als `nsfw` oder `ip_detected` enden; Elements durchlaufen `ip_checking` [O: Schemas jobs_wait, elements_search]. Fremde Markenbeispiele als Referenz können daran scheitern. Kriterien: unbekannt.
15. **Lieferantenwissen.** Keine der elf Druckereien, keine Datencheck-Regel, keine Vorlagen (Stanzkonturen, Mindestschriftgrößen, Farbauftrag) sind irgendwo hinterlegt [O]. Das Wort „supplier“ kommt nur als Quelle vor, bei der Vorgaben einzuholen sind.
16. **Dauerhaftigkeit.** Lebensdauer von Ergebnis-URLs, Aufbewahrung, Nutzungsrechte: in der Tool-Oberfläche nicht genannt; unbekannt. Sandbox-Dateien halten 24 h [O: Schema sandbox_exec].

---

## 10. Empfehlungen für den Plan

1. **Zwei getrennte Stufen planen.** Higgsfield liefert Bausteine (RGB-Raster, SVG). Die Druckdatenstufe (Endformat in mm, Beschnitt, Sicherheitsabstand, Schriften, CMYK-Wandlung mit dem ICC-Profil des Lieferanten, PDF/X, Preflight) muss ein eigener, deterministischer Baustein außerhalb von Higgsfield sein. Das deckt sich mit dem Anbieter-Workflow.
2. **Text und Logos nie generieren lassen.** Layout als Vorlage mit echtem Vektortext und platziertem Original-Logo; Bildmodelle nur für Motiv und Hintergrund. Generierte Flachdesigns mit Text nur als Entwurf/Moodboard, nicht als Druckvorlage.
3. **Modellzuordnung festschreiben.** Sticker-Art, Logos, Icons: `recraft_v4_1` `vector` mit `colors` (Alternativen `ideogram_vector`, `gpt_image_2_6` svg). Fotoreale Motive: `nano_banana_pro` 4k oder `seedream_v4_5`. Entwürfe mit Text: `gpt_image_2_6` oder `ideogram_4_6`. DIN-Hintergründe: `gpt_image_2_6` mit `size` oder `flux_3_image` 5:7 (teuer). Freisteller: `background: transparent` oder `remove_background`.
4. **Beschnitt durch Übergröße statt durch Outpainting.** Motiv gleich im Verhältnis „Endformat plus Beschnitt“ erzeugen (5:7 passt für A4/A5 mit 3 mm auf 0,2 %) und deterministisch zuschneiden; `flux_2_pro_outpaint` nur als Notlösung für reine Hintergrundränder.
5. **Auflösungsklassen je Produkt definieren.** Bis A4: native 4k-Stufe prüfen. A3: 8k/6K-Modell oder `topaz_image`. Darüber: Vektor oder lieferantenkonforme geringere Auflösung. Für jede Klasse eine Mindest-dpi-Prüfung im eigenen Preflight.
6. **Ein Master, viele Ableitungen.** Weil die meisten Modelle keinen Seed haben, wird je Design genau ein freigegebener Master (Bilddateien + Layoutdefinition) gespeichert; die elf Lieferantenformate entstehen daraus rechnerisch, nicht durch erneutes Generieren.
7. **MCP nur für die Designphase, REST-API für Automatisierung.** Den Shop nicht an die Chat-Sitzung koppeln. Vor einer Backend-Planung in der API-Dokumentation klären: Modellliste, Parameter, Preise, Webhooks, Schlüsselverwaltung.
8. **Referenzbibliothek außerhalb von Higgsfield führen.** Fremde Beispiele mit Rolle, Quelle, Lizenzstatus und den Feldern aus der Asset-Analyse (gefällt, Palette, Schriftcharakter, Komposition, „nicht kopieren“) im eigenen Speicher ablegen; nach Higgsfield nur das hochladen, was als Stil-/Kompositionsreferenz in einen Auftrag geht (`media_import_url`, höchstens 50 MB, oder `media_upload`). Elements (`prop`/`other`, Eigenschaften als Schlagworte) nur als Arbeitskopie für eigene Logos/Produkte.
9. **Brand Lock als Datenmodell übernehmen.** Die JSON-Struktur (Palette mit Rollen, Schriften, Formsprache, Verbote) eignet sich als Vorlage für einen eigenen, dauerhaft gespeicherten Design-Steckbrief je Kunde/Kollektion, der in jeden Prompt kopiert wird.
10. **Budget und Konto klären, bevor getestet wird.** Das Konto hat 0 Credits im Plan `free`. Für die Testreihe unten reichen nach den Angeboten etwa 60–100 Credits [R: Summe der unter 11 genannten Einzeltests bei 1–3 Bildern je Test].
11. **Kleine bezahlte Testreihe einplanen** (schließt die meisten offenen Punkte): je ein Bild `gpt_image_2_6` 4k mit `size` in A4-Proportion, `nano_banana_pro` 4k, `flux_3_image` 4k 5:7, `kling_image_4_0` 8k, `seedream_v4_5` high, `recraft_v4_1` vector 1k und 2k, `ideogram_vector` generate und vectorize, `gpt_image_2_6` svg, je einmal transparent, je einmal mit deutschem Text samt Umlauten; anschließend Pixelmaße, Dateityp, eingebettetes Profil, SVG-Aufbau auslesen.

---

## 11. Offene Punkte

| Offen | Wie schließen |
|---|---|
| Exakte Pixelmaße je Modell, Stufe und Seitenverhältnis | Testbild erzeugen und Maße auslesen; oder API-Dokumentation lesen |
| Grenzen von `size` bei `gpt_image_2_6` auf Higgsfield (längste Kante, Gesamtpixel) | Test mit 2480×3504 und größer |
| Ausgabeformat, Bittiefe, eingebettetes Farbprofil der Modelle ohne `output_format` | Testdatei mit `exiftool`/ImageMagick prüfen |
| Aufbau der SVG (Text oder Pfade, Verläufe, eingebettete Raster, Einhaltung von `colors`) | je ein SVG aus Recraft, GPT, Ideogram analysieren |
| Deutsche Sonderzeichen in generiertem Text | Testprompts mit ä, ö, ü, ß |
| Erstellen persönlicher Recraft-Styles (Weg, Anzahl Referenzen, Preis) | Web-App bzw. Entwickler-API prüfen; `get_presets source=recraft` in einer interaktiven Sitzung öffnen |
| Marketing-Studio-Brand-Kit per Tool anlegen/ändern | in dieser Sitzung kein Tool vorhanden; Web-App oder REST prüfen |
| Text „Unlim configs“ | nur bei aktivem Testkontingent sichtbar |
| Erzeugen mit Plan `free` und 0 Credits möglich? | nicht getestet; Kontoinhaber klärt Plan/Aufladung |
| Kosten und Maximalgröße von `topaz_image` | Angebot über das Tool nicht möglich; Web-App oder Testlauf |
| Preis je Credit in Euro, Laufzeit gekaufter Credits | offizielle Preisseite bzw. Checkout im Konto |
| REST-API: Modellumfang, Parameter, Preise, Webhook-Details, Ratenlimits | API-Dokumentation (docs.higgsfield.ai) durch den dafür zuständigen Rechercheauftrag |
| Lebensdauer der Ergebnis-URLs, Aufbewahrung, kommerzielle Nutzungsrechte, Verhalten des IP-Filters | Nutzungsbedingungen und Hilfe-Center; Test mit eigenem Logo |
| Higgsfield-Marketplace-Apps (`apps_search`) mit möglichem Drucknutzen | ein lesender Aufruf, hier nicht erlaubt |
| Werkzeuge in der Sandbox für Druckvorstufe (Ghostscript, ICC, Inkscape) | `sandbox_exec` mit `which …`, hier nicht erlaubt |
| Pixelmaß der Stufe `8k` bei `kling_image_4_0` | Testbild |

---

## 12. Gesperrte Hosts

Es wurden keine direkten Abrufe (curl, WebFetch, Browser) versucht; daher keine Sperrmeldungen. Alle Web-Angaben stammen aus Suchergebnis-Zusammenfassungen und sind entsprechend vorsichtig zu lesen.
