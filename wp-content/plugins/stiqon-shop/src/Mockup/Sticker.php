<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A sticker drawn as inline SVG: backing material, printed area, finish.
 *
 * The drawing lives in a 120 x 120 box; the sticker itself is centred with
 * room around it for the CSS shadow. The printed area shows a sample motif
 * and, on top of it, the visitor's own motif once one is chosen in the browser.
 */
final class Sticker implements Kind
{
    public const SHAPES = ['circle', 'square', 'rect', 'oval', 'contour'];

    /** The shapes by their German names, for the administration. */
    public const SHAPE_NAMES = ['circle' => 'Rund', 'square' => 'Quadratisch', 'rect' => 'Rechteckig', 'oval' => 'Oval', 'contour' => 'Konturschnitt'];

    /** The materials by their German names, for the administration. */
    public const MATERIAL_NAMES = ['vinyl' => 'weißes Vinyl', 'clear' => 'transparente Folie', 'holo' => 'holografische Folie', 'kraft' => 'Kraftpapier'];

    public const MATERIALS = ['vinyl', 'clear', 'holo', 'kraft'];

    public const FINISHES = ['gloss', 'matte'];

    /**
     * Sample motifs made for contour-cut stickers: their shape is the sticker's
     * outline (see CONTOURS). Drawn on anything else they lose it – printed on
     * paper, "stern" is white lettering only.
     */
    public const CONTOUR_MOTIFS = ['sprechblase', 'stern'];

    /** Silhouettes for contour-cut stickers, in the motif's 100 x 100 box; one per motif in CONTOUR_MOTIFS. */
    private const CONTOURS = [
        'sprechblase' => 'M50 8C26 8 8 24 8 45c0 12 6 22 16 29l-4 18 19-11c4 1 7 1 11 1 24 0 42-16 42-37S74 8 50 8z',
        'stern' => 'M50 6l11 27 29 2-22 19 7 29-25-16-25 16 7-29L10 35l29-2z',
    ];

    public readonly string $uid;

    public readonly string $outline;

    public readonly string $printArea;

    /** @var array{x: float, y: float, width: float, height: float} */
    public readonly array $printBox;

    public readonly ?string $contour;

    public readonly bool $peel;

    public function __construct(
        public readonly string $shape = 'circle',
        public readonly string $material = 'vinyl',
        public readonly string $finish = 'gloss',
        public readonly string $motif = 'siegel',
        bool $peel = false,
        public readonly ?string $label = null,
    ) {
        if (! in_array($shape, self::SHAPES, true)) {
            throw new InvalidArgumentException("Unknown sticker shape [{$shape}].");
        }

        if (! in_array($material, self::MATERIALS, true)) {
            throw new InvalidArgumentException("Unknown sticker material [{$material}].");
        }

        if (! in_array($finish, self::FINISHES, true)) {
            throw new InvalidArgumentException("Unknown sticker finish [{$finish}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown sticker motif [{$motif}].");
        }

        $this->uid = Uid::next('st');
        $this->contour = $shape === 'contour' ? (self::CONTOURS[$motif] ?? self::CONTOURS['sprechblase']) : null;

        // Clear film has no white border: the print runs to the edge.
        $border = $material === 'clear' ? 0.0 : 4.0;

        [$this->outline, $this->printArea, $this->printBox] = $this->geometry($border);

        // Only shapes with a straight bottom-right corner can show a peeled corner.
        $this->peel = $peel && in_array($shape, ['square', 'rect'], true);
    }

    public static function label(): string
    {
        return 'Sticker';
    }

    public static function keys(): array
    {
        return ['shape' => self::SHAPES, 'material' => self::MATERIALS, 'finish' => self::FINISHES, 'motif' => self::motifs(), 'peel' => null];
    }

    public static function form(): array
    {
        return ['key' => 'shape', 'label' => 'Form', 'values' => self::SHAPE_NAMES];
    }

    /**
     * Shape from the product's name, material from the names of its options.
     * Whether the surface is matte or glossy follows rules of its own (see
     * Catalog\Guess::option()): kraft paper does not shine, whatever it is called.
     */
    public static function names(): array
    {
        return [
            // Checked in this order: a rectangle "mit abgerundeten Ecken" is not round.
            'form' => [
                'contour' => 'kontur|freiform|formgestanzt|die[ -]?cut',
                'oval' => 'oval|ellip',
                'rect' => 'rechteck|rectang',
                'square' => 'quadrat|square',
                'circle' => 'rund|kreis|round|circle',
            ],
            'options' => [
                'material' => [
                    'holo' => 'holo|regenbogen|irisierend',
                    'clear' => 'transparent|durchsichtig|glasklar|clear',
                    'kraft' => 'kraft|naturpapier|graspapier',
                    'vinyl' => 'vinyl|weiß|weiss|white',
                ],
            ],
            'switches' => [],
        ];
    }

    /**
     * @param  array{shape?: string, material?: string, finish?: string, motif?: string, peel?: bool, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            shape: $spec['shape'] ?? 'circle',
            material: $spec['material'] ?? 'vinyl',
            finish: $spec['finish'] ?? 'gloss',
            motif: $spec['motif'] ?? 'siegel',
            peel: (bool) ($spec['peel'] ?? false),
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs drawn for stickers.
     *
     * @return list<string>
     */
    public static function motifs(): array
    {
        return Template::names('motif');
    }

    /**
     * The SVG markup. A width class in $class (w-…, md:w-…) replaces the full-width default.
     */
    public function render(string $class = ''): string
    {
        return Template::render('mockup/sticker', ['mockup' => $this, 'class' => $class]);
    }

    /** Whether the sample motif is drawn without its own background. */
    public function bareMotif(): bool
    {
        return $this->material !== 'vinyl';
    }

    public function describe(): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        $shape = [
            'circle' => 'Runder Sticker',
            'square' => 'Quadratischer Sticker',
            'rect' => 'Rechteckiger Sticker',
            'oval' => 'Ovaler Sticker',
            'contour' => 'Konturgeschnittener Sticker',
        ][$this->shape];

        $material = [
            'vinyl' => 'weißes Vinyl',
            'clear' => 'transparente Folie',
            'holo' => 'holografische Folie',
            'kraft' => 'Kraftpapier',
        ][$this->material];

        return "{$shape}, {$material}";
    }

    /**
     * Corner of the peeled flap: [fold start, fold end, tip of the folded flap].
     *
     * @return array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}, 2: array{0: float, 1: float}}|null
     */
    public function peelCorner(): ?array
    {
        if (! $this->peel) {
            return null;
        }

        [$x, $y, $width, $height] = $this->shape === 'square' ? [14.0, 14.0, 92.0, 92.0] : [10.0, 28.0, 100.0, 64.0];
        $right = $x + $width;
        $bottom = $y + $height;
        $fold = 22.0;

        return [[$right - $fold, $bottom], [$right, $bottom - $fold], [$right - $fold, $bottom - $fold]];
    }

    /**
     * @return array{0: string, 1: string, 2: array{x: float, y: float, width: float, height: float}}
     */
    private function geometry(float $border): array
    {
        return match ($this->shape) {
            'circle' => [
                self::ellipse(60, 60, 46, 46),
                self::ellipse(60, 60, 46 - $border, 46 - $border),
                self::box(14 + $border, 14 + $border, 92 - 2 * $border, 92 - 2 * $border),
            ],
            'square' => [
                self::roundedRect(14, 14, 92, 92, 11),
                self::roundedRect(14 + $border, 14 + $border, 92 - 2 * $border, 92 - 2 * $border, max(11 - $border, 2)),
                self::box(14 + $border, 14 + $border, 92 - 2 * $border, 92 - 2 * $border),
            ],
            'rect' => [
                self::roundedRect(10, 28, 100, 64, 9),
                self::roundedRect(10 + $border, 28 + $border, 100 - 2 * $border, 64 - 2 * $border, max(9 - $border, 2)),
                self::box(10 + $border, 28 + $border, 100 - 2 * $border, 64 - 2 * $border),
            ],
            'oval' => [
                self::ellipse(60, 60, 50, 34),
                self::ellipse(60, 60, 50 - $border, 34 - $border),
                self::box(10 + $border, 26 + $border, 100 - 2 * $border, 68 - 2 * $border),
            ],
            // Contour stickers are drawn from the motif's silhouette in the template.
            'contour' => ['', '', self::box(10, 10, 100, 100)],
        };
    }

    /**
     * @return array{x: float, y: float, width: float, height: float}
     */
    private static function box(float $x, float $y, float $width, float $height): array
    {
        return ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height];
    }

    private static function ellipse(float $cx, float $cy, float $rx, float $ry): string
    {
        $left = $cx - $rx;
        $width = 2 * $rx;

        return "M{$left} {$cy}a{$rx} {$ry} 0 1 0 {$width} 0a{$rx} {$ry} 0 1 0 -{$width} 0z";
    }

    private static function roundedRect(float $x, float $y, float $width, float $height, float $radius): string
    {
        $innerWidth = $width - 2 * $radius;
        $innerHeight = $height - 2 * $radius;
        $startX = $x + $radius;

        return "M{$startX} {$y}h{$innerWidth}a{$radius} {$radius} 0 0 1 {$radius} {$radius}v{$innerHeight}"
            ."a{$radius} {$radius} 0 0 1 -{$radius} {$radius}h-{$innerWidth}"
            ."a{$radius} {$radius} 0 0 1 -{$radius} -{$radius}v-{$innerHeight}"
            ."a{$radius} {$radius} 0 0 1 {$radius} -{$radius}z";
    }
}
