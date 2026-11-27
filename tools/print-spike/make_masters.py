"""Synthetic stand-ins for AI output (Higgsfield would deliver RGB PNG)."""
import sys, numpy as np
from PIL import Image, ImageDraw, ImageFont, ImageFilter
out = sys.argv[1] if len(sys.argv) > 1 else "masters"
FONT = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"
# sample colours used again by measure.py
SAMPLES = {"neon_green": (57, 255, 20), "electric_blue": (0, 90, 255),
           "skin_orange": (224, 150, 110), "black_text": (0, 0, 0)}
W, H = 1287, 1795
y = np.linspace(0, 1, H)[:, None, None]
top, bot = np.array([20, 0, 90]), np.array([0, 90, 255])   # violet -> electric blue
img = (top * (1 - y) + bot * y).repeat(W, axis=1).astype(np.uint8)
im = Image.fromarray(img, "RGB"); d = ImageDraw.Draw(im)
d.ellipse([-200, 900, 900, 2000], fill=SAMPLES["neon_green"])            # big neon disc into bleed
d.polygon([(0, 300), (W, 0), (W, 380), (0, 700)], fill=(255, 0, 200))     # magenta diagonal band
# flat measurement patches (inside safe area)
for i, (k, c) in enumerate(SAMPLES.items()):
    d.rectangle([120 + i * 270, 1450, 330 + i * 270, 1650], fill=c, outline=(255, 255, 255), width=6)
d.text((120, 820), "AI SALE", font=ImageFont.truetype(FONT, 150), fill=(0, 0, 0))  # raster black text
im.save(f"{out}/flyer_master_rgb.png")
# sticker: non-trivial silhouette on transparency, drawn 4x and downsampled for AA edges
S = 900 * 4; m = Image.new("L", (S, S), 0); md = ImageDraw.Draw(m)
cx = cy = S // 2
pts = []
for k in range(14):  # 7-point star with rounded-ish arms
    r = 1500 if k % 2 == 0 else 900
    a = np.pi / 7 * k - np.pi / 2
    pts.append((cx + r * np.cos(a), cy + r * np.sin(a)))
md.polygon(pts, fill=255)
md.ellipse([cx - 1050, cy - 1050, cx + 1050, cy + 1050], fill=255)
md.ellipse([cx + 300, cy - 200, cx + 900, cy + 400], fill=0)             # concave bite
md.rectangle([cx - 40, 60, cx + 40, cy], fill=255)                         # thin spike (tests smoothing)
mask = m.resize((900, 900), Image.LANCZOS)
g = np.linspace(0, 1, 900)[None, :, None]
rgb = (np.array([57, 255, 20]) * (1 - g) + np.array([0, 90, 255]) * g).repeat(900, axis=0).astype(np.uint8)
st = Image.fromarray(rgb, "RGB"); sd = ImageDraw.Draw(st)
sd.ellipse([330, 330, 570, 570], fill=SAMPLES["skin_orange"])
st.putalpha(mask)
st.save(f"{out}/sticker_master_rgba.png")
print("ok", im.size, st.size)
