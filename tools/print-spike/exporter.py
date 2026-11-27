"""Spike exporter: RGB master + supplier profile JSON -> print PDF.

usage: python -I exporter.py <master.png> <profile.json> <out.pdf>
Writes <out.pdf> and <out.pdf>.log.json (timings, ppi, upscale, contour nodes).
"""
import sys, json, time, math, uuid, datetime, os
import numpy as np, cv2
from PIL import Image, ImageCms
from shapely.geometry import Polygon
from reportlab import rl_config
rl_config.useA85 = 0                       # binary streams, no ASCII85 overhead
from reportlab.pdfgen import canvas
from reportlab.lib.colors import CMYKColor, CMYKColorSep
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
import pikepdf

MM = 72 / 25.4
SRGB = "/usr/share/color/icc/sRGB.icc"
FONT_PATH = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"
pdfmetrics.registerFont(TTFont("SpikeSans", FONT_PATH))
INTENTS = {"perceptual": 0, "relative": 1, "saturation": 2, "absolute": 3}


def rgb_to_cmyk(img, prof, base):
    src = ImageCms.ImageCmsProfile(SRGB)          # untagged AI output -> assume sRGB
    if "icc_profile" in img.info:
        import io
        src = ImageCms.ImageCmsProfile(io.BytesIO(img.info["icc_profile"]))
    flags = int(ImageCms.Flags.BLACKPOINTCOMPENSATION) if prof.get("bpc") else 0
    t = ImageCms.buildTransform(src, os.path.join(base, prof["icc_file"]), "RGB", "CMYK",
                                renderingIntent=INTENTS[prof["rendering_intent"]], flags=flags)
    return ImageCms.applyTransform(img.convert("RGB"), t)


def cover_crop(img, page_w_mm, page_h_mm, min_ppi, log):
    """scale-to-cover: centre crop to page aspect, upscale (Lanczos) only if below min_ppi."""
    W, H = img.size
    ar = page_w_mm / page_h_mm
    if W / H > ar:
        cw, ch = round(H * ar), H
    else:
        cw, ch = W, round(W / ar)
    x0, y0 = (W - cw) // 2, (H - ch) // 2
    img = img.crop((x0, y0, x0 + cw, y0 + ch))
    ppi = cw / (page_w_mm / 25.4)
    log.update(crop_px=[x0, y0, cw, ch], cropped_away_px=[W - cw, H - ch], native_ppi=round(ppi, 1))
    if ppi < min_ppi - 0.5:
        nw = math.ceil(page_w_mm / 25.4 * min_ppi); nh = math.ceil(page_h_mm / 25.4 * min_ppi)
        img = img.resize((nw, nh), Image.LANCZOS)
        log["upscaled_factor"] = round(nw / cw, 4)
    else:
        log["upscaled_factor"] = 1.0
    return img


def flyer(master, prof, c, base, log):
    tw, th = prof["trim_mm"]; b = prof["bleed_mm"]; s = prof["safe_mm"]
    pw, ph = tw + 2 * b, th + 2 * b
    rgb = cover_crop(Image.open(master).convert("RGB"), pw, ph, prof["min_ppi"], log)
    t0 = time.perf_counter(); cmyk = rgb_to_cmyk(rgb, prof, base)
    log["t_icc_s"] = round(time.perf_counter() - t0, 3)
    # translucent white panel behind the live text, inside the safe zone
    panel_mm = (b + s, b + s + 18, tw - 2 * s, 16)             # x, y(from bottom), w, h
    alpha = 0.55
    if not prof["transparency_allowed"]:
        # flatten exactly as DeviceCMYK normal blending would: C' = C*(1-a) + 0*a
        a = np.asarray(cmyk).astype(np.float32)
        ppmm_x, ppmm_y = cmyk.size[0] / pw, cmyk.size[1] / ph
        x0 = int(round(panel_mm[0] * ppmm_x)); x1 = int(round((panel_mm[0] + panel_mm[2]) * ppmm_x))
        y1 = cmyk.size[1] - int(round(panel_mm[1] * ppmm_y)); y0 = cmyk.size[1] - int(round((panel_mm[1] + panel_mm[3]) * ppmm_y))
        a[y0:y1, x0:x1] *= (1 - alpha)
        cmyk = Image.fromarray(np.clip(a + 0.5, 0, 255).astype(np.uint8), "CMYK")
        log["transparency"] = "flattened into raster"
    c.drawImage(ImageReader(cmyk), 0, 0, pw * MM, ph * MM)    # image covers full bleed box
    if prof["transparency_allowed"]:
        c.saveState(); c.setFillColor(CMYKColor(0, 0, 0, 0)); c.setFillAlpha(alpha)
        c.rect(*[v * MM for v in panel_mm], stroke=0, fill=1); c.restoreState()
        log["transparency"] = "live (ExtGState ca)"
    c.setFillColor(CMYKColor(0, 0, 0, prof["text_k"] / 100)); c.setFillOverprint(True); c.setOverprintMask(1)
    txt = "Live-Text 100 K: Größe 105×148 mm"
    avail = (tw - 2 * s - 6) * MM                       # 3 mm padding inside the panel on each side
    size = min(13.0, 13.0 * avail / pdfmetrics.stringWidth(txt, "SpikeSans", 13))
    log["text_pt"] = round(size, 2)
    c.setFont("SpikeSans", size)
    c.drawString((b + s + 3) * MM, (b + s + 23.5) * MM, txt)
    log["image_px"] = list(cmyk.size)
    return pw, ph, (b, b, tw, th)


def sticker(master, prof, c, base, log):
    im = Image.open(master).convert("RGBA")
    a = np.asarray(im)[:, :, 3]
    sil = (a >= 128).astype(np.uint8)
    ys, xs = np.nonzero(sil)
    D = max(xs.max() - xs.min() + 1, ys.max() - ys.min() + 1)
    off, b, sm = prof["contour_offset_mm"], prof["bleed_mm"], prof["smooth_mm"]
    ppmm = D / (prof["size_mm"] - 2 * off)
    log["native_ppi"] = round(ppmm * 25.4, 1)
    cs, _ = cv2.findContours(sil, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_NONE)
    cnt = max(cs, key=cv2.contourArea)[:, 0, :].astype(float)
    Hh = im.size[1]
    poly = Polygon([(x / ppmm, (Hh - y) / ppmm) for x, y in cnt]).buffer(0)
    cut = poly.buffer(off, quad_segs=16)
    cut = cut.buffer(sm, quad_segs=16).buffer(-sm, quad_segs=16)      # closing: round inner corners
    cut = cut.buffer(-sm, quad_segs=16).buffer(sm, quad_segs=16)      # opening: drop needles < 2*sm
    cut = cut.simplify(0.02)
    if cut.geom_type != "Polygon":
        cut = max(cut.geoms, key=lambda g: g.area)
    minx, miny, maxx, maxy = cut.bounds
    log["contour_nodes"] = len(cut.exterior.coords) - 1
    log["contour_bbox_mm"] = [round(maxx - minx, 3), round(maxy - miny, 3)]
    pw, ph = (maxx - minx) + 2 * b, (maxy - miny) + 2 * b
    ox, oy = minx - b, miny - b                                         # mm origin of page in master mm
    # raster for the whole page at ppmm; place master, edge-extend colours outward
    PW, PH = math.ceil(pw * ppmm), math.ceil(ph * ppmm)
    px0 = int(round(-ox * ppmm)); py0 = int(round(PH - (Hh / ppmm - oy) * ppmm))
    canvas_rgb = np.zeros((PH, PW, 3), np.uint8); opaque = np.zeros((PH, PW), np.uint8)
    rgb = np.asarray(im)[:, :, :3]
    ys0, xs0 = max(0, py0), max(0, px0)
    ys1, xs1 = min(PH, py0 + Hh), min(PW, px0 + im.size[0])
    canvas_rgb[ys0:ys1, xs0:xs1] = rgb[ys0 - py0:ys1 - py0, xs0 - px0:xs1 - px0]
    opaque[ys0:ys1, xs0:xs1] = sil[ys0 - py0:ys1 - py0, xs0 - px0:xs1 - px0]
    t0 = time.perf_counter()
    src = (1 - opaque).astype(np.uint8)
    _, labels = cv2.distanceTransformWithLabels(src, cv2.DIST_L2, 5, labelType=cv2.DIST_LABEL_PIXEL)
    zero = opaque.astype(bool)
    lut = np.zeros((labels.max() + 1, 2), np.int64)
    lut[labels[zero]] = np.argwhere(zero)
    nn = lut[labels]
    ext = canvas_rgb[nn[..., 0], nn[..., 1]]
    # outside cut+bleed -> paper white
    bl = cut.buffer(b, quad_segs=16)
    pts = np.array([[(x - ox) * ppmm, PH - (y - oy) * ppmm] for x, y in bl.exterior.coords], np.int32)
    keep = np.zeros((PH, PW), np.uint8); cv2.fillPoly(keep, [pts], 1)
    ext[keep == 0] = 255
    log["t_edge_extend_s"] = round(time.perf_counter() - t0, 3)
    t0 = time.perf_counter(); cmyk = rgb_to_cmyk(Image.fromarray(ext, "RGB"), prof, base)
    log["t_icc_s"] = round(time.perf_counter() - t0, 3)
    c.drawImage(ImageReader(cmyk), 0, 0, PW / ppmm * MM, PH / ppmm * MM)
    log["image_px"] = [PW, PH]
    # live text 100K
    c.saveState()
    c.setFillColor(CMYKColor(0, 0, 0, prof["text_k"] / 100)); c.setFillOverprint(True); c.setOverprintMask(1)
    c.setFont("SpikeSans", 9)
    cx = (poly.centroid.x - ox) * MM; cy = (poly.centroid.y - oy) * MM
    c.drawCentredString(cx, cy - 3, "Spike 60 mm")
    c.restoreState()
    # cut contour: closed path, Separation colour space, overprint
    cu = prof["cut"]
    p = c.beginPath(); co = list(cut.exterior.coords)[:-1]
    p.moveTo((co[0][0] - ox) * MM, (co[0][1] - oy) * MM)
    for x, y in co[1:]:
        p.lineTo((x - ox) * MM, (y - oy) * MM)
    p.close()
    c.saveState()
    c.setStrokeColor(CMYKColorSep(*cu["alt_cmyk"], spotName=cu["spot_name"], density=1))
    c.setStrokeOverprint(bool(cu["overprint"])); c.setOverprintMask(1)
    c.setLineWidth(cu["line_pt"]); c.setLineJoin(1)
    c.drawPath(p, stroke=1, fill=0)
    c.restoreState()
    return pw, ph, (b, b, maxx - minx, maxy - miny)


def pdfdate(dt):
    return dt.strftime("D:%Y%m%d%H%M%S+00'00'")


def finish(tmp, out, prof, base, pw, ph, trim, log):
    pdf = pikepdf.open(tmp)
    pg = pdf.pages[0]
    pg.MediaBox = [0, 0, pw * MM, ph * MM]
    pg.BleedBox = [0, 0, pw * MM, ph * MM]
    x, y, w, h = trim
    pg.TrimBox = [x * MM, y * MM, (x + w) * MM, (y + h) * MM]
    # reportlab opens every page with "BT /F1 12 Tf ... ET" (Helvetica, never shown, not embeddable here):
    # drop BT..ET blocks without show operators, then drop fonts that no remaining Tf selects
    ops = list(pikepdf.parse_content_stream(pg)); keep, blk = [], None
    for ins in ops:
        o = str(ins[1])
        if o == "BT": blk = [ins]; continue
        if blk is not None:
            blk.append(ins)
            if o == "ET":
                if any(str(i[1]) in ("Tj", "TJ", "'", '"') for i in blk): keep += blk
                else: log["dropped_empty_text_blocks"] = log.get("dropped_empty_text_blocks", 0) + 1
                blk = None
            continue
        keep.append(ins)
    pg.Contents = pdf.make_stream(pikepdf.unparse_content_stream(keep))
    used = {str(i[0][0]) for i in keep if str(i[1]) == "Tf"}
    fonts = pg.Resources.get("/Font", {})
    for k in list(fonts.keys()):
        if k not in used:
            del fonts[k]
            log.setdefault("removed_unused_fonts", []).append(k)
    if "/ArtBox" in pg.obj:
        del pg.obj["/ArtBox"]
    if "/CropBox" in pg.obj:
        del pg.obj["/CropBox"]
    icc = pikepdf.Stream(pdf, open(os.path.join(base, prof["icc_file"]), "rb").read())
    icc.N = 4
    oi = pikepdf.Dictionary(Type=pikepdf.Name.OutputIntent, S=pikepdf.Name.GTS_PDFX,
                            OutputConditionIdentifier=prof["output_condition_identifier"],
                            OutputCondition=prof["output_condition"], RegistryName="http://www.color.org",
                            Info=prof["output_condition"], DestOutputProfile=icc)
    pdf.Root.OutputIntents = pikepdf.Array([oi])
    now = datetime.datetime.now(datetime.timezone.utc).replace(microsecond=0)
    xver = {"X-4": "PDF/X-4", "X-1a": "PDF/X-1a:2003"}[prof["pdfx"]]
    title = f"Spike {prof['id']}"
    docid = f"uuid:{uuid.uuid4()}"
    try:
        with pdf.open_metadata(set_pikepdf_as_editor=False, update_docinfo=False) as m:
            m["dc:title"] = title
            m["xmp:CreateDate"] = now.isoformat(); m["xmp:ModifyDate"] = now.isoformat()
            m["xmp:MetadataDate"] = now.isoformat(); m["xmp:CreatorTool"] = "spike exporter.py"
            m["pdf:Producer"] = "reportlab + pikepdf"; m["pdf:Trapped"] = "False"
            m["xmpMM:DocumentID"] = docid; m["xmpMM:InstanceID"] = docid
            m["xmpMM:VersionID"] = "1"; m["xmpMM:RenditionClass"] = "default"
            m["{http://www.npes.org/pdfx/ns/id/}GTS_PDFXVersion"] = xver
        log["xmp"] = "ok"
    except Exception as e:  # noqa
        log["xmp"] = f"failed: {e!r}"
    info = pikepdf.Dictionary(Title=title, Creator="spike exporter.py", Producer="reportlab + pikepdf",
                              CreationDate=pdfdate(now), ModDate=pdfdate(now),
                              Trapped=pikepdf.Name("/False"),
                              GTS_PDFXVersion=xver)
    pdf.trailer.Info = pdf.make_indirect(info)
    pdf.save(out, force_version=prof["pdf_version"], fix_metadata_version=True,
             object_stream_mode=pikepdf.ObjectStreamMode.disable, compress_streams=True)
    os.remove(tmp)


def main():
    master, profp, out = sys.argv[1:4]
    base = os.path.dirname(os.path.abspath(__file__))
    prof = json.load(open(profp)); log = {"profile": prof["id"], "master": master}
    t0 = time.perf_counter(); tmp = out + ".tmp.pdf"
    c = canvas.Canvas(tmp, pageCompression=1)
    pw, ph, trim = (flyer if prof["kind"] == "flyer" else sticker)(master, prof, c, base, log)
    c.setPageSize((pw * MM, ph * MM)); c.showPage(); c.save()
    finish(tmp, out, prof, base, pw, ph, trim, log)
    log["t_total_s"] = round(time.perf_counter() - t0, 3); log["bytes"] = os.path.getsize(out)
    json.dump(log, open(out + ".log.json", "w"), indent=1)
    print(json.dumps(log))


if __name__ == "__main__":
    main()
