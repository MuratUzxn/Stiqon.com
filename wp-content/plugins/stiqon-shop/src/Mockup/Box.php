<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A shipping box drawn as inline SVG: closed and taped, seen from above one
 * corner, with a logo printed on its front.
 *
 * The drawing lives in a 120 x 120 box. The carton is drawn in parallel
 * projection: each face is a rectangle of its own size that one matrix turns
 * into place (see faces()). The print is laid onto the front with the same
 * matrix, so it follows the face as real print does.
 *
 * The sizes are not drawn to scale – true to scale, S would look like a
 * matchbox beside L – but each keeps its character: S is small and flat, L
 * big and deep. The tape is as wide on every size, so on S it covers more
 * of the lid.
 */
final class Box implements Kind
{
    /** Length (the front), depth (the side) and height, in the drawing's units. */
    public const SIZES = [
        's' => ['label' => 'S', 'length' => 72.0, 'depth' => 45.0, 'height' => 29.0],
        'm' => ['label' => 'M', 'length' => 76.0, 'depth' => 54.0, 'height' => 38.0],
        'l' => ['label' => 'L', 'length' => 79.0, 'depth' => 62.0, 'height' => 48.0],
    ];

    public const COLORS = ['kraft', 'white'];

    /** The colours by their German names, for the description. */
    public const COLOR_NAMES = ['kraft' => 'braun', 'white' => 'weiß'];

    /** Width of the packing tape over the lid. */
    public const TAPE = 9.0;

    /** How far the box is turned away from the viewer, in degrees. */
    private const TURN = 32.0;

    /** How steeply the viewer looks down on it, in degrees. */
    private const VIEW = 24.0;

    public readonly string $uid;

    public readonly float $length;

    public readonly float $depth;

    public readonly float $height;

    /**
     * Each face as the six numbers of an SVG matrix() that maps a rectangle of
     * the face's own size into place: the front (length x height) and the side
     * (depth x height) from their top left corner downwards, the lid (length x
     * depth) from its front left corner backwards.
     *
     * @var array{front: list<float>, side: list<float>, top: list<float>}
     */
    public readonly array $faces;

    /**
     * Corners of the drawing, in the drawing's coordinates: "near" is the edge
     * closest to the viewer, "left" the far end of the front, "back" the far
     * end of the side; each at the "top" and at the "bottom".
     *
     * @var array<string, array{0: float, 1: float}>
     */
    public readonly array $corners;

    /**
     * Where the logo is printed, in the front's own coordinates.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $printBox;

    public function __construct(
        public readonly string $size = 's',
        public readonly string $motif = 'aktion',
        public readonly string $color = 'kraft',
        public readonly ?string $label = null,
    ) {
        if (! isset(self::SIZES[$size])) {
            throw new InvalidArgumentException("Unknown box size [{$size}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown box motif [{$motif}].");
        }

        if (! in_array($color, self::COLORS, true)) {
            throw new InvalidArgumentException("Unknown box color [{$color}].");
        }

        $this->uid = Uid::next('bx');
        $this->length = self::SIZES[$size]['length'];
        $this->depth = self::SIZES[$size]['depth'];
        $this->height = self::SIZES[$size]['height'];

        [$this->faces, $this->corners] = $this->project();

        // The logo stays clear of the edges and of the lid's seam.
        $print = round(min(0.82 * $this->height, 0.45 * $this->length), 2);
        $this->printBox = Svg::box(round(($this->length - $print) / 2, 2), round(($this->height - $print) / 2, 2), $print, $print);
    }

    public static function label(): string
    {
        return 'Versandkarton';
    }

    public static function keys(): array
    {
        return ['size' => array_keys(self::SIZES), 'motif' => self::motifs(), 'color' => self::COLORS];
    }

    public static function form(): array
    {
        return ['key' => 'size', 'label' => 'Größe', 'values' => array_map(static fn (array $size): string => $size['label'], self::SIZES)];
    }

    /**
     * Size from the product's name or from a chosen option ("M (35 × 25 × 15
     * cm)"), colour from the option. White is asked first: a white box is
     * often sold as "weiß, innen braun".
     */
    public static function names(): array
    {
        $sizes = [
            's' => '\bs\b|klein|small',
            'm' => '\bm\b|mittel|medium',
            'l' => '\bl\b|gro[sß]|large',
        ];

        return [
            'form' => $sizes,
            'options' => [
                'size' => $sizes,
                'color' => ['white' => 'wei[sß]|white', 'kraft' => 'braun|kraft|natur|brown'],
            ],
            'switches' => [],
        ];
    }

    /**
     * @param  array{size?: string, motif?: string, color?: string, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            size: $spec['size'] ?? 's',
            motif: $spec['motif'] ?? 'aktion',
            color: $spec['color'] ?? 'kraft',
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs printed on boxes: the sticker motifs, as a logo.
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
        return Template::render('mockup/box', ['mockup' => $this, 'class' => $class]);
    }

    /**
     * How the sample logo is printed: straight onto the board, without a
     * ground of its own, so the brown or the white shows through. In dark ink
     * on both (see $ink in the motif templates): white lettering would vanish
     * on white board and fade into kraft, and so would a small gold label.
     *
     * @return array{bare: true, ink: 'dark'|'light'}
     */
    public function motifVars(): array
    {
        return ['bare' => true, 'ink' => 'dark'];
    }

    /** A face as an SVG transform (see $faces). */
    public function matrix(string $face): string
    {
        return 'matrix('.implode(' ', $this->faces[$face]).')';
    }

    public function describe(): string
    {
        return $this->label ?? 'Versandkarton '.self::SIZES[$this->size]['label'].', '.self::COLOR_NAMES[$this->color];
    }

    /**
     * Faces and corners, centred in the drawing a little above the middle:
     * the shadow falls below and needs the room.
     *
     * @return array{0: array{front: list<float>, side: list<float>, top: list<float>}, 1: array<string, array{0: float, 1: float}>}
     */
    private function project(): array
    {
        $turn = deg2rad(self::TURN);
        $view = deg2rad(self::VIEW);

        // One unit along the front (towards the near edge), along the side
        // (away from it) and down, as seen from the viewer.
        $along = [cos($turn), sin($turn) * sin($view)];
        $across = [sin($turn), -cos($turn) * sin($view)];
        $down = cos($view);

        $corners = [
            'left-top' => [0.0, 0.0],
            'near-top' => [$this->length * $along[0], $this->length * $along[1]],
        ];
        $corners['back-top'] = [$corners['near-top'][0] + $this->depth * $across[0], $corners['near-top'][1] + $this->depth * $across[1]];
        $corners['far-top'] = [$this->depth * $across[0], $this->depth * $across[1]];

        foreach (['left', 'near', 'back'] as $corner) {
            $corners[$corner.'-bottom'] = [$corners[$corner.'-top'][0], $corners[$corner.'-top'][1] + $this->height * $down];
        }

        $xs = array_column($corners, 0);
        $ys = array_column($corners, 1);
        $shiftX = 60 - (min($xs) + max($xs)) / 2;
        $shiftY = 58 - (min($ys) + max($ys)) / 2;

        $placed = array_map(static fn (array $point): array => [round($point[0] + $shiftX, 2), round($point[1] + $shiftY, 2)], $corners);

        $axis = static fn (array $vector): array => [round($vector[0], 4), round($vector[1], 4)];
        [$alongX, $alongY] = $axis($along);
        [$acrossX, $acrossY] = $axis($across);
        $down = round($down, 4);

        $faces = [
            'front' => [$alongX, $alongY, 0.0, $down, ...$placed['left-top']],
            'side' => [$acrossX, $acrossY, 0.0, $down, ...$placed['near-top']],
            'top' => [$alongX, $alongY, $acrossX, $acrossY, ...$placed['left-top']],
        ];

        return [$faces, $placed];
    }
}
