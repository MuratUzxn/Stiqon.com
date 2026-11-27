"""Spike preflight: read a print PDF back and check it against its supplier profile.

usage: python -I preflight.py <profile.json> <file.pdf> [<profile.json> <file.pdf> ...]
Prints a PASS/FAIL table per file and writes out/preflight.json.
Not a PDF/X validator: it checks the profile rules this spike cares about.
"""
import sys, json, os, hashlib, subprocess, tempfile, re
import numpy as np
from PIL import Image
import pikepdf
from shapely.geometry import Polygon, box

MM = 72 / 25.4
BASE = os.path.dirname(os.path.abspath(__file__))


def mul(m, n):  # m x n, PDF matrices [a b c d e f]
    a, b, c, d, e, f = m; A, B, C, D, E, F = n
    return [a * A + b * C, a * B + b * D, c * A + d * C, c * B + d * D, e * A + f * C + E, e * B + f * D + F]


def tp(m, x, y):
    return m[0] * x + m[2] * y + m[4], m[1] * x + m[3] * y + m[5]


def walk(page):
    """Interpret the page content: images placed, strokes, text runs, colour/overprint state."""
    res = page.Resources
    gsr = res.get("/ExtGState", {}); csr = res.get("/ColorSpace", {}); xo = res.get("/XObject", {})
    st = dict(ctm=[1, 0, 0, 1, 0, 0], lw=1.0, OP=False, op=False, OPM=0, scs=None, fcs="DeviceGray",
              fill=None, ca=1.0, CA=1.0)
    stack, ev, path = [], dict(images=[], strokes=[], texts=[], rgb_ops=0, alpha_ops=[]), []
    for ops, op in pikepdf.parse_content_stream(page):
        o = str(op)
        if o == "q": stack.append(dict(st, ctm=list(st["ctm"])))
        elif o == "Q": st = stack.pop()
        elif o == "cm": st["ctm"] = mul([float(v) for v in ops], st["ctm"])
        elif o == "w": st["lw"] = float(ops[0])
        elif o == "gs":
            g = gsr[ops[0]]
            if "/OP" in g:
                st["OP"] = bool(g.OP); st["op"] = bool(g.get("/op", g.OP))
            if "/op" in g: st["op"] = bool(g.op)
            if "/OPM" in g: st["OPM"] = int(g.OPM)
            for k in ("ca", "CA"):
                if "/" + k in g:
                    st[k] = float(g["/" + k])
                    if st[k] < 1: ev["alpha_ops"].append((k, st[k]))
        elif o == "CS": st["scs"] = str(ops[0])
        elif o == "cs": st["fcs"] = str(ops[0])
        elif o == "k": st["fill"] = ("DeviceCMYK", [float(v) for v in ops]); st["fcs"] = "DeviceCMYK"
        elif o in ("rg", "RG"): ev["rgb_ops"] += 1
        elif o in ("sc", "scn"): st["fill"] = (st["fcs"], [float(v) for v in ops if not isinstance(v, pikepdf.Name)])
        elif o in ("m", "l"):
            path.append(tp(st["ctm"], float(ops[0]), float(ops[1])))
        elif o in ("re",):
            path = []
        elif o in ("S", "s", "B", "b", "B*", "b*"):
            sc = abs(st["ctm"][0] * st["ctm"][3] - st["ctm"][1] * st["ctm"][2]) ** 0.5
            cs = csr.get(st["scs"]) if st["scs"] in csr else st["scs"]
            ev["strokes"].append(dict(cs=cs, width_pt=st["lw"] * sc, OP=st["OP"], OPM=st["OPM"],
                                      closed=o in ("s", "b", "b*") or (len(path) > 2 and path[0] == path[-1]),
                                      pts=list(path)))
            path = []
        elif o in ("f", "F", "f*", "n"): path = []
        elif o == "h" and path: path.append(path[0])
        elif o in ("Tj", "TJ", "'", '"'):
            ev["texts"].append(dict(fill=st["fill"], op=st["op"], OPM=st["OPM"]))
        elif o == "Do":
            x = xo[ops[0]]
            if x.Subtype == "/Image":
                m = st["ctm"]
                wpt = (m[0] ** 2 + m[1] ** 2) ** 0.5; hpt = (m[2] ** 2 + m[3] ** 2) ** 0.5
                ev["images"].append(dict(cs=str(x.get("/ColorSpace")), bpc=int(x.BitsPerComponent),
                                         px=[int(x.Width), int(x.Height)], placed_mm=[wpt / MM, hpt / MM],
                                         ppi=[int(x.Width) / (wpt / 72), int(x.Height) / (hpt / 72)],
                                         rect=[m[4], m[5], m[4] + wpt, m[5] + hpt],
                                         smask="/SMask" in x or "/Mask" in x, filt=str(x.get("/Filter"))))
    return ev


def font_report(page):
    out = []
    for k, f in page.Resources.get("/Font", {}).items():
        fd = f.get("/FontDescriptor")
        if f.Subtype == "/Type0":
            fd = f.DescendantFonts[0].get("/FontDescriptor")
        emb = fd is not None and any(x in fd for x in ("/FontFile", "/FontFile2", "/FontFile3"))
        out.append((str(f.get("/BaseFont")), emb))
    return out


def gs_render(pdf, dev, res, outpat):
    subprocess.run(["gs", "-q", "-dSAFER", "-dBATCH", "-dNOPAUSE", f"-sDEVICE={dev}", f"-r{res}",
                    "-dUseTrimBox=false", f"-sOutputFile={outpat}", pdf], check=True, capture_output=True)


def check(profp, pdfp):
    prof = json.load(open(profp)); R = []
    add = lambda name, ok, val: R.append((name, "PASS" if ok is True else ("FAIL" if ok is False else ok), val))
    pdf = pikepdf.open(pdfp); pg = pdf.pages[0]
    mb = [float(v) for v in pg.MediaBox]; bb = [float(v) for v in pg.obj.get("/BleedBox", pg.MediaBox)]
    tb = [float(v) for v in pg.obj.get("/TrimBox", pg.MediaBox)]
    b = prof["bleed_mm"]
    inset = [(tb[0] - bb[0]) / MM, (tb[1] - bb[1]) / MM, (bb[2] - tb[2]) / MM, (bb[3] - tb[3]) / MM]
    tw, th = (tb[2] - tb[0]) / MM, (tb[3] - tb[1]) / MM
    add("PDF version", pdf.pdf_version == prof["pdf_version"], pdf.pdf_version)
    add("Media = BleedBox", all(abs(a - c) < 0.01 for a, c in zip(mb, bb)), f"{(mb[2]-mb[0])/MM:.2f} x {(mb[3]-mb[1])/MM:.2f} mm")
    add("Bleed je Seite", all(abs(i - b) < 0.05 for i in inset), "/".join(f"{i:.2f}" for i in inset) + f" mm (soll {b})")
    if prof["kind"] == "flyer":
        add("TrimBox = Endformat", abs(tw - prof["trim_mm"][0]) < 0.1 and abs(th - prof["trim_mm"][1]) < 0.1, f"{tw:.2f} x {th:.2f} mm")
    else:
        add("TrimBox = Kontur-BBox, max 60 mm", abs(max(tw, th) - prof["size_mm"]) < 0.5, f"{tw:.2f} x {th:.2f} mm")
    # PDF/X identification
    info = pdf.trailer.get("/Info", {})
    xv = str(info.get("/GTS_PDFXVersion", ""))
    want = {"X-4": "PDF/X-4", "X-1a": "PDF/X-1a:2003"}[prof["pdfx"]]
    xmp = pdf.open_metadata()
    xmpv = xmp.get("{http://www.npes.org/pdfx/ns/id/}GTS_PDFXVersion", "")
    add("GTS_PDFXVersion Info+XMP", xv == want and (xmpv == want or prof["pdfx"] == "X-1a"), f"Info={xv} XMP={xmpv or '-'}")
    add("Trapped/ID/Dates/Title", str(info.get("/Trapped")) in ("/True", "/False") and all(k in info for k in ("/Trapped", "/CreationDate", "/ModDate", "/Title")) and "/ID" in pdf.trailer,
        f"Trapped={info.get('/Trapped')} ID={'ja' if '/ID' in pdf.trailer else 'nein'}")
    # output intent
    ois = pdf.Root.get("/OutputIntents", [])
    oi = [o for o in ois if o.get("/S") == "/GTS_PDFX"]
    if oi:
        prof_bytes = oi[0].DestOutputProfile.read_bytes()
        same = hashlib.md5(prof_bytes).hexdigest() == hashlib.md5(open(os.path.join(BASE, prof["icc_file"]), "rb").read()).hexdigest()
        add("OutputIntent GTS_PDFX + ICC", int(oi[0].DestOutputProfile.N) == 4 and same,
            f"{oi[0].OutputConditionIdentifier}, N=4, {len(prof_bytes)} B, ICC==Profil:{same}")
    else:
        add("OutputIntent GTS_PDFX + ICC", False, "fehlt")
    ev = walk(pg)
    # images
    for i, im in enumerate(ev["images"]):
        covers = im["rect"][0] <= bb[0] + 0.01 and im["rect"][1] <= bb[1] + 0.01 and im["rect"][2] >= bb[2] - 0.01 and im["rect"][3] >= bb[3] - 0.01
        add(f"Bild{i} Farbraum", im["cs"] == "/DeviceCMYK", f"{im['cs']} {im['bpc']} bit {im['filt']}")
        add(f"Bild{i} eff. ppi >= {prof['min_ppi']}", min(im["ppi"]) >= prof["min_ppi"] - 0.5, f"{im['ppi'][0]:.1f} x {im['ppi'][1]:.1f} ppi bei {im['placed_mm'][0]:.1f} x {im['placed_mm'][1]:.1f} mm")
        add(f"Bild{i} deckt BleedBox", covers, "ja" if covers else "nein")
    add("kein DeviceRGB/ICCBased im Inhalt", ev["rgb_ops"] == 0 and all(im["cs"] == "/DeviceCMYK" for im in ev["images"]), f"rg/RG-Ops={ev['rgb_ops']}")
    # fonts / text
    fr = font_report(pg)
    add("Fonts eingebettet", bool(fr) and all(e for _, e in fr), ", ".join(f"{n}:{'emb' if e else 'NICHT emb'}" for n, e in fr))
    t_ok = ev["texts"] and all(t["fill"] and t["fill"][0] == "DeviceCMYK" and t["fill"][1] == [0, 0, 0, prof["text_k"] / 100] for t in ev["texts"])
    add("Text 100 K, Ueberdrucken", bool(t_ok), f"{len(ev['texts'])} Textlauf/-läufe, fill={ev['texts'][0]['fill'] if ev['texts'] else None}, op={ev['texts'][0]['op'] if ev['texts'] else None}")
    words = text_boxes(pdfp, mb)
    # spot colour / cut contour
    cut_poly = None
    if prof["kind"] == "sticker":
        cu = prof["cut"]; seps = []
        for s in ev["strokes"]:
            cs = s["cs"]
            if isinstance(cs, pikepdf.Array) and cs[0] == "/Separation":
                seps.append((str(cs[1])[1:], s))
        ok = [s for n, s in seps if n == cu["spot_name"]]
        add(f"Schmuckfarbe '{cu['spot_name']}'", bool(ok), ", ".join(sorted({n for n, _ in seps})) or "keine Separation")
        if ok:
            s = ok[0]
            add("Kontur Linie/Overprint/geschlossen", abs(s["width_pt"] - cu["line_pt"]) < 0.01 and s["OP"] == cu["overprint"] and s["closed"],
                f"{s['width_pt']:.3f} pt, OP={s['OP']}, OPM={s['OPM']}, closed={s['closed']}, {len(s['pts'])} Punkte")
            cut_poly = Polygon(s["pts"])
            d = [(cut_poly.bounds[0] - tb[0]) / MM, (cut_poly.bounds[1] - tb[1]) / MM, (tb[2] - cut_poly.bounds[2]) / MM, (tb[3] - cut_poly.bounds[3]) / MM]
            add("Kontur-BBox = TrimBox", all(abs(x) < 0.05 for x in d), "Abw. " + "/".join(f"{x:.3f}" for x in d) + " mm")
        safe = cut_poly.buffer(-prof["safe_mm"] * MM) if cut_poly is not None else None
    else:
        safe = box(tb[0] + prof["safe_mm"] * MM, tb[1] + prof["safe_mm"] * MM, tb[2] - prof["safe_mm"] * MM, tb[3] - prof["safe_mm"] * MM)
    if safe is not None and words:
        inside = all(safe.contains(w) for w in words)
        add(f"Text in Sicherheitszone ({prof['safe_mm']} mm)", inside, f"{len(words)} Wörter (pdftotext -bbox)")
    # transparency
    has_t = bool(ev["alpha_ops"]) or any(im["smask"] for im in ev["images"]) or "/Group" in pg.obj
    add("Transparenz", (not has_t) or prof["transparency_allowed"], ("vorhanden " + str(ev["alpha_ops"][:2])) if has_t else "keine")
    # ink coverage via ghostscript renders
    with tempfile.TemporaryDirectory() as td:
        gs_render(pdfp, "tiff32nc", 150, f"{td}/c.tif")
        a = np.asarray(Image.open(f"{td}/c.tif")).astype(np.float32) / 255 * 100
        tac = a.sum(axis=2)
        add(f"TAC max <= {prof['tac_max']} %", float(np.percentile(tac, 99.9)) <= prof["tac_max"] + 1,
            f"p99.9={np.percentile(tac, 99.9):.1f} %, max={tac.max():.1f} %, Mittel C/M/Y/K={'/'.join(f'{v:.1f}' for v in a.reshape(-1, 4).mean(0))}")
        if prof["kind"] == "sticker":
            gs_render(pdfp, "tiffsep", 72, f"{td}/s.tif")
            sep = [f for f in os.listdir(td) if f.startswith("s(")]
            names = sorted(re.sub(r"^s\((.*)\)\.tif$", r"\1", f) for f in sep)
            add("gs tiffsep: Spot-Auszug vorhanden", prof["cut"]["spot_name"] in names, ", ".join(names))
    return R


def text_boxes(pdfp, mb):
    r = subprocess.run(["pdftotext", "-bbox", pdfp, "-"], capture_output=True, text=True).stdout
    H = float(mb[3]); out = []
    for m in re.finditer(r'xMin="([\d.]+)" yMin="([\d.]+)" xMax="([\d.]+)" yMax="([\d.]+)"', r):
        x0, y0, x1, y1 = map(float, m.groups())
        if "<page" in r[max(0, m.start() - 6):m.start()]:
            continue
        out.append(box(x0, H - y1, x1, H - y0))
    return out


if __name__ == "__main__":
    args = sys.argv[1:]; allres = {}
    for profp, pdfp in zip(args[::2], args[1::2]):
        R = check(profp, pdfp); allres[os.path.basename(pdfp)] = R
        print(f"\n== {os.path.basename(pdfp)}  (Profil {os.path.basename(profp)})")
        for n, s, v in R:
            print(f"  {s:4}  {n:38} {v}")
        print(f"  -> {'BESTANDEN' if all(s != 'FAIL' for _, s, _ in R) else 'NICHT BESTANDEN'} ({sum(s=='PASS' for _, s, _ in R)}/{len(R)} PASS)")
    json.dump(allres, open(os.path.join(BASE, "out", "preflight.json"), "w"), indent=1, default=str)
