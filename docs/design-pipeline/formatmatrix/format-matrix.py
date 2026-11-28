"""Pixel needs of common Werbemittel formats and how far the preset aspect ratios
of the Higgsfield image models are from them. Ratios from models_explore (2026-11-26).
ASSUMPTION: a resolution tier 'Nk' yields a long edge of N*1024 px (4k = 4096 px).
This is not documented anywhere and must be measured with one real image per model."""
import json
from fractions import Fraction

MODELS = {
    'flux_3_image': (['21:9', '2:1', '16:9', '3:2', '7:5', '4:3', '5:4', '1:1', '4:5', '3:4', '5:7', '2:3', '9:16', '1:2'], 4),
    'ideogram_4_6': (['1:1', '1:2', '2:1', '2:3', '3:2', '4:5', '5:4', '9:16', '16:9', '5:8', '8:5', '3:4', '4:3', '9:21', '21:9', '9:22', '22:9', '9:23', '23:9', '3:8', '8:3', '5:12', '12:5', '1:3', '3:1'], 4),
    'nano_banana_pro': (['1:1', '3:2', '2:3', '4:3', '3:4', '4:5', '5:4', '9:16', '16:9', '21:9'], 4),
    'kling_image_4_0': (['16:9', '9:16', '1:1', '4:3', '3:4', '3:2', '2:3', '21:9'], 8),
    'recraft_v4_1': (['1:1', '3:4', '4:3', '4:5', '5:4', '3:2', '2:3', '16:9', '9:16'], 2),
}
# name, trim w x h in mm, bleed per side in mm, target dpi
FORMATS = [
    ('Sticker rund 50 mm', 50, 50, 2, 300),
    ('Visitenkarte 85 x 55', 85, 55, 2, 300),
    ('DIN A6 (Flyer, Postkarte)', 105, 148, 2, 300),
    ('DIN lang 99 x 210', 99, 210, 2, 300),
    ('DIN A5', 148, 210, 2, 300),
    ('Quadrat 148 x 148', 148, 148, 2, 300),
    ('DIN A4', 210, 297, 3, 300),
    ('DIN A3 (Plakat)', 297, 420, 3, 300),
    ('DIN A1 (Plakat)', 594, 841, 3, 150),
    ('Roll-up 85 x 200 cm', 850, 2000, 5, 100),
]

def ratio(s):
    a, b = (int(x) for x in s.split(':'))
    return max(a, b) / min(a, b)

rows = []
for name, w, h, bleed, dpi in FORMATS:
    W, H = w + 2 * bleed, h + 2 * bleed
    long_mm, short_mm = max(W, H), min(W, H)
    r_t = long_mm / short_mm
    px_long = round(long_mm / 25.4 * dpi); px_short = round(short_mm / 25.4 * dpi)
    best = []
    for model, (ratios, tier) in MODELS.items():
        cand = [(abs(1 - min(ratio(r), r_t) / max(ratio(r), r_t)), r) for r in ratios]
        loss, r = min(cand)
        L = tier * 1024
        r_g = ratio(r)
        final_long = L if r_g <= r_t else L * r_t / r_g
        eff_dpi = final_long / (long_mm / 25.4)
        best.append({'model': model, 'ratio': r, 'crop_loss_pct': round(loss * 100, 1), 'tier': f'{tier}k', 'eff_dpi_assumed': round(eff_dpi)})
    best.sort(key=lambda b: (b['crop_loss_pct'], -b['eff_dpi_assumed']))
    rows.append({'format': name, 'with_bleed_mm': f'{W} x {H}', 'bleed_mm': bleed, 'ratio': round(r_t, 4), 'dpi': dpi,
                 'px': f'{px_short} x {px_long}', 'mp': round(px_long * px_short / 1e6, 1), 'models': best})

json.dump(rows, open(__file__.replace('.py', '.json'), 'w'), indent=1, ensure_ascii=False)
print('| Format | Maß mit Beschnitt | Verhältnis | dpi | Pixel nötig | MP | bestes Preset (Verlust) | Annahme: dpi bei 4k (8k) |')
print('|---|---|---|---|---|---|---|---|')
for r in rows:
    top = r['models'][0]
    by = {m['model']: m for m in r['models']}
    print(f"| {r['format']} | {r['with_bleed_mm']} mm | 1:{r['ratio']:.3f} | {r['dpi']} | {r['px']} | {r['mp']} | {top['ratio']} {top['model']} ({top['crop_loss_pct']} %) | {by['nano_banana_pro']['eff_dpi_assumed']} ({by['kling_image_4_0']['eff_dpi_assumed']}) |")
print()
for r in rows:
    print(r['format'], '->', ', '.join(f"{m['model']} {m['ratio']} {m['crop_loss_pct']}% {m['eff_dpi_assumed']}dpi" for m in r['models']))
