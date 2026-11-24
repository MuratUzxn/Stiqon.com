#!/usr/bin/env python3
"""Derive the web assets from the original Stiqon logo.

The logo was supplied as a raster image on a white background (no vector file,
no transparency). This script produces, from that one file:

  public/img/brand/logo-{400,800}.webp        the wordmark with a transparent background
  public/img/brand/logo-light-{400,800}.webp  letters in off-white for dark surfaces, gold line kept
  public/img/brand/icon-{32,180,192,512}.png  the "S" on an ink tile (favicon, touch icon)
  public/img/brand/logo-original-800.webp     the source as supplied, reduced, for side-by-side review
  public/favicon.ico

How it works: every pixel of the source is treated as a mix of three things –
white paper, black ink and the gold of the inline. Solving for that mix gives a
coverage value (how much of the pixel is logo rather than paper) and a gold
share. The letter shapes are not redrawn. Two clean-ups are applied, both
limited to defects of the source file that are invisible on white but show on
any other background: tiny stray specks outside the letters are dropped, and
pinholes inside the letters are closed.

Re-run after replacing the source file:

    python3 bin/brand/make-logo-assets.py

Requires Pillow and numpy. The generated files are committed, so neither the
build nor the server needs Python.
"""

from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw, ImageFilter

ROOT = Path(__file__).resolve().parents[2]
SOURCE = ROOT / "resources/brand/logo-original.webp"
OUT = ROOT / "public/img/brand"

PAPER = np.array([255, 255, 255], dtype=np.float32)
INK = np.array([7, 7, 7], dtype=np.float32)
GOLD = np.array([214, 185, 44], dtype=np.float32)
LIGHT = np.array([250, 250, 247], dtype=np.float32)
TILE = (10, 10, 10, 255)

# A shape must contain a core at least this thick (px at source resolution) to
# count as part of the logo; anything thinner everywhere is a stray speck.
CORE_RADIUS = 3
# Gaps and pinholes up to twice this wide (px) inside the letters are closed.
CLOSE_RADIUS = 2


def grow(mask: np.ndarray, radius: int) -> np.ndarray:
    image = Image.fromarray((mask * 255).astype(np.uint8))
    return np.asarray(image.filter(ImageFilter.MaxFilter(radius * 2 + 1))) > 0


def shrink(mask: np.ndarray, radius: int) -> np.ndarray:
    image = Image.fromarray((mask * 255).astype(np.uint8))
    return np.asarray(image.filter(ImageFilter.MinFilter(radius * 2 + 1))) > 0


def without_specks(mask: np.ndarray) -> np.ndarray:
    """Keep only shapes that have a solid core; grow the cores back to full shape."""
    kept = shrink(mask, CORE_RADIUS)
    while True:
        grown = grow(kept, 1) & mask
        if np.array_equal(grown, kept):
            return kept
        kept = grown


def unmix(rgb: np.ndarray) -> tuple[np.ndarray, np.ndarray]:
    """Per pixel: coverage (0 = paper, 1 = logo) and gold share within the logo.

    Solves  pixel = paper + coverage * (ink - paper) + gold * (GOLD - INK)
    in the least-squares sense for all pixels at once.
    """
    basis = np.stack([INK - PAPER, GOLD - INK], axis=1)  # 3 x 2
    solver = np.linalg.pinv(basis)  # 2 x 3
    mix = (rgb - PAPER) @ solver.T

    coverage = np.clip(mix[..., 0], 0.0, 1.0)
    gold = np.clip(mix[..., 1] / np.maximum(mix[..., 0], 1e-3), 0.0, 1.0)

    return coverage, gold


def matte(rgb: np.ndarray) -> tuple[np.ndarray, np.ndarray, np.ndarray]:
    """Return (alpha, gold share, mask of pixels whose source colour is kept)."""
    coverage, gold = unmix(rgb)

    shape = without_specks(coverage > 0.5)
    closed = shrink(grow(shape, CLOSE_RADIUS), CLOSE_RADIUS) | shape
    filled = closed & ~shape  # pinholes: paper-coloured in the source

    alpha = coverage.copy()
    alpha[~grow(closed, 1)] = 0.0  # nothing survives away from the letters
    alpha[shrink(closed, 1)] = 1.0  # the inside is fully opaque
    alpha[filled] = 1.0

    gold = gold.copy()
    gold[filled] = 0.0  # pinholes become ink

    # Inside the letters the source pixels are kept as they are; only the soft
    # edge and the closed pinholes are rebuilt from ink and gold.
    keeps_source = shrink(closed, 1) & ~filled

    return alpha, gold, keeps_source


def blend(base: np.ndarray, gold: np.ndarray) -> np.ndarray:
    return base + gold[..., None] * (GOLD - base)


def compose(colour: np.ndarray, alpha: np.ndarray) -> Image.Image:
    rgba = np.dstack([colour, alpha * 255.0])

    return Image.fromarray(np.clip(np.rint(rgba), 0, 255).astype(np.uint8), "RGBA")


def crop_to_content(image: Image.Image, padding: int) -> Image.Image:
    left, top, right, bottom = image.getchannel("A").getbbox()

    return image.crop((
        max(left - padding, 0),
        max(top - padding, 0),
        min(right + padding, image.width),
        min(bottom + padding, image.height),
    ))


def save_widths(image: Image.Image, name: str, widths: tuple[int, ...]) -> None:
    for width in widths:
        height = round(image.height * width / image.width)
        image.resize((width, height), Image.LANCZOS).save(OUT / f"{name}-{width}.webp", lossless=True, quality=100)


def first_letter(alpha: np.ndarray) -> np.ndarray:
    """Mask of the leftmost connected shape (the "S")."""
    shape = alpha > 0.5
    seed = np.zeros_like(shape)
    column = int(np.nonzero(shape.any(axis=0))[0][0])
    seed[:, column] = shape[:, column]

    while True:
        grown = grow(seed, 1) & shape
        if np.array_equal(grown, seed):
            return grow(seed, 2)
        seed = grown


def icon(letter: Image.Image, size: int) -> Image.Image:
    """The light "S" centred on a rounded ink tile."""
    scale = 4  # draw large, then reduce for smooth corners
    canvas = Image.new("RGBA", (size * scale, size * scale), (0, 0, 0, 0))
    ImageDraw.Draw(canvas).rounded_rectangle(
        (0, 0, size * scale - 1, size * scale - 1), radius=round(size * scale * 0.22), fill=TILE
    )

    box = round(size * scale * 0.62)
    ratio = min(box / letter.width, box / letter.height)
    glyph = letter.resize((round(letter.width * ratio), round(letter.height * ratio)), Image.LANCZOS)
    canvas.alpha_composite(glyph, ((canvas.width - glyph.width) // 2, (canvas.height - glyph.height) // 2))

    return canvas.resize((size, size), Image.LANCZOS)


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)

    source = Image.open(SOURCE).convert("RGB")
    source.resize((800, round(source.height * 800 / source.width)), Image.LANCZOS).save(
        OUT / "logo-original-800.webp", quality=92, method=6
    )

    rgb = np.asarray(source, dtype=np.float32)
    alpha, gold, keeps_source = matte(rgb)

    dark_colour = blend(np.broadcast_to(INK, rgb.shape).copy(), gold)
    dark_colour[keeps_source] = rgb[keeps_source]
    light_colour = blend(np.broadcast_to(LIGHT, rgb.shape).copy(), gold)

    dark = crop_to_content(compose(dark_colour, alpha), padding=8)
    light = crop_to_content(compose(light_colour, alpha), padding=8)
    save_widths(dark, "logo", (400, 800))
    save_widths(light, "logo-light", (400, 800))

    letter = crop_to_content(compose(light_colour, alpha * first_letter(alpha)), padding=0)

    for size in (32, 180, 192, 512):
        icon(letter, size).save(OUT / f"icon-{size}.png", optimize=True)

    icon(letter, 64).save(ROOT / "public/favicon.ico", sizes=[(16, 16), (32, 32), (48, 48), (64, 64)])

    print(f"Wordmark: {dark.width}x{dark.height} px cropped from {rgb.shape[1]}x{rgb.shape[0]}")
    for path in sorted(OUT.iterdir()):
        print(f"  {path.relative_to(ROOT)}  {path.stat().st_size / 1024:.1f} kB")


if __name__ == "__main__":
    main()
