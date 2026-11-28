"""Measurements: colour shift master RGB -> CMYK in the PDF -> Lab (via output profile), timings, sizes.
usage: python -I measure.py   (after run_all.sh)"""
import json, os, sys, time
import numpy as np, pikepdf
from PIL import Image, ImageCms
BASE = os.path.dirname(os.path.abspath(__file__)); sys.path.insert(0, BASE)
import preflight  # noqa: E402  (own script in this directory)

SRGB = "/usr/share/color/icc/sRGB.icc"
SAMPLES = {"neon_green": (57, 255, 20), "electric_blue": (0, 90, 255),
           "skin_orange": (224, 150, 110), "black_text": (0, 0, 0)}
PATCH_CENTRES = {k: (225 + i * 270, 1550) for i, k in enumerate(SAMPLES)}   # master px, see make_masters.py
LAB = ImageCms.createProfile("LAB", 5000)
RI = {"relative": 1, "perceptual": 0}


def to_lab(img, prof_path, intent=1):
    t = ImageCms.buildTransform(prof_path, LAB, img.mode, "LAB", renderingIntent=intent)
    a = np.asarray(ImageCms.applyTransform(img, t)).astype(np.float64)
    s8 = lambda v: (v + 128) % 256 - 128   # Pillow LAB: a/b are signed 8-bit
    return np.stack([a[..., 0] * 100 / 255, s8(a[..., 1]), s8(a[..., 2])], -1)


def de2000(l1, l2):
    L1, a1, b1 = l1; L2, a2, b2 = l2
    C1, C2 = np.hypot(a1, b1), np.hypot(a2, b2); Cb = (C1 + C2) / 2
    G = 0.5 * (1 - np.sqrt(Cb ** 7 / (Cb ** 7 + 25 ** 7)))
    a1p, a2p = (1 + G) * a1, (1 + G) * a2
    C1p, C2p = np.hypot(a1p, b1), np.hypot(a2p, b2)
    h1p, h2p = np.degrees(np.arctan2(b1, a1p)) % 360, np.degrees(np.arctan2(b2, a2p)) % 360
    dLp, dCp = L2 - L1, C2p - C1p
    dh = h2p - h1p
    dh = dh - 360 if dh > 180 else dh + 360 if dh < -180 else dh
    dHp = 2 * np.sqrt(C1p * C2p) * np.sin(np.radians(dh / 2))
    Lbp, Cbp = (L1 + L2) / 2, (C1p + C2p) / 2
    hbp = (h1p + h2p) / 2 if abs(h1p - h2p) <= 180 else (h1p + h2p + 360) / 2
    T = 1 - 0.17 * np.cos(np.radians(hbp - 30)) + 0.24 * np.cos(np.radians(2 * hbp)) + 0.32 * np.cos(np.radians(3 * hbp + 6)) - 0.20 * np.cos(np.radians(4 * hbp - 63))
    dth = 30 * np.exp(-((hbp - 275) / 25) ** 2)
    RC = 2 * np.sqrt(Cbp ** 7 / (Cbp ** 7 + 25 ** 7))
    SL = 1 + 0.015 * (Lbp - 50) ** 2 / np.sqrt(20 + (Lbp - 50) ** 2); SC = 1 + 0.045 * Cbp; SH = 1 + 0.015 * Cbp * T
    RT = -np.sin(np.radians(2 * dth)) * RC
    return float(np.sqrt((dLp / SL) ** 2 + (dCp / SC) ** 2 + (dHp / SH) ** 2 + RT * (dCp / SC) * (dHp / SH)))


def pdf_image(pdfp):
    pdf = pikepdf.open(pdfp)
    for _, x in pdf.pages[0].images.items():
        return pikepdf.PdfImage(x).as_pil_image()


def main():
    out = {"colour": {}, "files": {}}
    sanity = to_lab(Image.new("RGB", (1, 1), (255, 255, 255)), SRGB)[0, 0]
    out["lab_sanity_white"] = [round(v, 1) for v in sanity]
    for pid in ("flyer_A", "flyer_B"):
        prof = json.load(open(f"{BASE}/profiles/{pid}.json")); icc = f"{BASE}/{prof['icc_file']}"
        log = json.load(open(f"{BASE}/out/{pid}.pdf.log.json"))
        img = pdf_image(f"{BASE}/out/{pid}.pdf")
        x0, y0 = log["crop_px"][:2]; f = log["upscaled_factor"]
        rows = {}
        for k, rgb in SAMPLES.items():
            cx, cy = PATCH_CENTRES[k]; px, py = int((cx - x0) * f), int((cy - y0) * f)
            cm = np.asarray(img.crop((px - 10, py - 10, px + 10, py + 10))).reshape(-1, 4).mean(0)
            cmyk = Image.new("CMYK", (1, 1), tuple(int(round(v)) for v in cm))
            lab_src = to_lab(Image.new("RGB", (1, 1), rgb), SRGB)[0, 0]
            lab_pdf = to_lab(cmyk, icc)[0, 0]
            # same colour with perceptual intent for comparison (not in PDF)
            t = ImageCms.buildTransform(SRGB, icc, "RGB", "CMYK", renderingIntent=0)
            cp = ImageCms.applyTransform(Image.new("RGB", (1, 1), rgb), t)
            lab_perc = to_lab(cp, icc)[0, 0]
            rows[k] = dict(rgb=rgb, cmyk_pdf_pct=[round(v / 2.55) for v in cm], tac=round(sum(cm) / 2.55),
                           lab_master=[round(v, 1) for v in lab_src], lab_print=[round(v, 1) for v in lab_pdf],
                           dE00_relbpc=round(de2000(lab_src, lab_pdf), 1),
                           cmyk_perceptual_pct=[round(v / 2.55) for v in cp.getpixel((0, 0))],
                           dE00_perceptual=round(de2000(lab_src, lab_perc), 1))
        k100 = to_lab(Image.new("CMYK", (1, 1), (0, 0, 0, 255)), icc)[0, 0]
        rows["live_text_100K"] = dict(cmyk_pdf_pct=[0, 0, 0, 100], lab_print=[round(v, 1) for v in k100],
                                      dE00_vs_rgb_black=round(de2000(to_lab(Image.new("RGB", (1, 1), (0, 0, 0)), SRGB)[0, 0], k100), 1))
        out["colour"][pid] = rows
    for pid in ("flyer_A", "flyer_B", "sticker_A", "sticker_B"):
        log = json.load(open(f"{BASE}/out/{pid}.pdf.log.json"))
        t0 = time.perf_counter(); R = preflight.check(f"{BASE}/profiles/{pid}.json", f"{BASE}/out/{pid}.pdf")
        out["files"][pid] = dict(export_s=log["t_total_s"], preflight_s=round(time.perf_counter() - t0, 2),
                                 bytes=os.path.getsize(f"{BASE}/out/{pid}.pdf"), image_px=log["image_px"],
                                 native_ppi=log.get("native_ppi"), upscaled=log.get("upscaled_factor"),
                                 contour_nodes=log.get("contour_nodes"),
                                 pass_=sum(s == "PASS" for _, s, _ in R), checks=len(R))
    # negative test: wrong profile for a file must fail
    neg = {}
    for prof, f in (("flyer_B", "flyer_A"), ("sticker_B", "sticker_A")):
        R = preflight.check(f"{BASE}/profiles/{prof}.json", f"{BASE}/out/{f}.pdf")
        neg[f"{f}.pdf vs {prof}"] = [n for n, s, _ in R if s == "FAIL"]
    out["negative_tests"] = neg
    json.dump(out, open(f"{BASE}/out/measure.json", "w"), indent=1)
    print(json.dumps(out, indent=1))


if __name__ == "__main__":
    main()
