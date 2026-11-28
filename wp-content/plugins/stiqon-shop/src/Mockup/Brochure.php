<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A brochure drawn as inline SVG: closed, lying slightly turned, the motif on
 * its cover.
 *
 * The drawing lives in a 120 x 120 box and is seen a little from the front
 * left, so that two of its edges show: the spine on the left and the page
 * block along the lower edge. How much they show is the binding – a stapled
 * brochure is a few sheets folded once, with two staples in the fold; a
 * brochure with a glued binding is a block of pages with a square spine.
 *
 * Sample motifs are the flyers' (1 : 1.414), cropped from the centre for the
 * long and the square format.
 */
final class Brochure implements Kind
{
    /** Cover sizes in the drawing's box: A5 and A4 share the DIN A proportion, as they do for flyers. */
    public const FORMATS = [
        'a5' => ['label' => 'DIN A5', 'width' => 63.6, 'height' => 90.0],
        'a4' => ['label' => 'DIN A4', 'width' => 63.6, 'height' => 90.0],
        'lang' => ['label' => 'DIN lang', 'width' => 43.0, 'height' => 91.2],
        'quadrat' => ['label' => 'Quadratisch', 'width' => 82.0, 'height' => 82.0],
    ];

    /** The bindings by their German names. */
    public const BINDINGS = ['staple' => 'Rückstichheftung', 'perfect' => 'Klebebindung'];

    /** How thick the brochure is drawn: a few folded sheets, or a block of pages. */
    private const THICKNESS = ['staple' => 2.7, 'perfect' => 5.0];

    /** How wide a strip of the cover's edge is stretched over the spine (see spineTransform()). */
    private const SPINE_STRIP = 0.8;

    public readonly string $uid;

    public readonly string $motif;

    /** @var array{x: float, y: float, width: float, height: float} */
    public readonly array $cover;

    /**
     * How far the brochure's underside lies left of and below its cover: the
     * width of the spine and the height of the page block in the drawing.
     *
     * @var array{x: float, y: float}
     */
    public readonly array $edge;

    public readonly float $tilt;

    public function __construct(
        public readonly string $format = 'a5',
        ?string $motif = null,
        public readonly string $binding = 'staple',
        public readonly bool $gloss = false,
        public readonly ?string $label = null,
    ) {
        $motif ??= self::motifs()[0];

        if (! isset(self::FORMATS[$format])) {
            throw new InvalidArgumentException("Unknown brochure format [{$format}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown brochure motif [{$motif}].");
        }

        if (! isset(self::BINDINGS[$binding])) {
            throw new InvalidArgumentException("Unknown brochure binding [{$binding}].");
        }

        $this->uid = Uid::next('br');
        $this->motif = $motif;

        $thickness = self::THICKNESS[$binding];
        $this->edge = ['x' => round($thickness * 0.8, 2), 'y' => round($thickness * 0.55, 2)];

        // The cover and the edges below it are centred together, so the whole brochure sits in the middle.
        $width = self::FORMATS[$format]['width'];
        $height = self::FORMATS[$format]['height'];

        $this->cover = Svg::box(
            round((120 - $width - $this->edge['x']) / 2 + $this->edge['x'], 2),
            round((120 - $height - $this->edge['y']) / 2, 2),
            $width,
            $height,
        );

        $this->tilt = -3.0;
    }

    public static function label(): string
    {
        return 'Broschüre';
    }

    public static function keys(): array
    {
        return ['format' => array_keys(self::FORMATS), 'motif' => self::motifs(), 'binding' => array_keys(self::BINDINGS), 'gloss' => null];
    }

    public static function form(): array
    {
        return ['key' => 'format', 'label' => 'Format', 'values' => array_map(static fn (array $format): string => $format['label'], self::FORMATS)];
    }

    /**
     * Format from the product's name or from a chosen option ("DIN A5"),
     * binding from the name of a chosen option. "Hardcover" is drawn as the
     * glued block: of the two, it is the one with a square spine.
     */
    public static function names(): array
    {
        $formats = [
            'a5' => 'a ?5',
            'a4' => 'a ?4',
            'lang' => 'din ?lang|lang',
            'quadrat' => 'quadrat',
        ];

        return [
            'form' => $formats,
            'options' => [
                'format' => $formats,
                'binding' => [
                    'perfect' => 'kleb|softcover|pur|hardcover',
                    'staple' => 'heft|klammer|r[uü]ckstich|draht',
                ],
            ],
            'switches' => ['gloss' => ['gl[aä]nz', 'matt']],
        ];
    }

    /**
     * @param  array{format?: string, motif?: string, binding?: string, gloss?: bool, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            format: $spec['format'] ?? 'a5',
            motif: $spec['motif'] ?? null,
            binding: $spec['binding'] ?? 'staple',
            gloss: (bool) ($spec['gloss'] ?? false),
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs drawn on a brochure's cover: the flyers'.
     *
     * @return list<string>
     */
    public static function motifs(): array
    {
        return Template::names('motif/flyer');
    }

    /**
     * The SVG markup. A width class in $class (w-…, md:w-…) replaces the full-width default.
     */
    public function render(string $class = ''): string
    {
        return Template::render('mockup/brochure', ['mockup' => $this, 'class' => $class]);
    }

    public function describe(): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        $brochure = $this->format === 'quadrat' ? 'Quadratische Broschüre' : 'Broschüre '.self::FORMATS[$this->format]['label'];

        return $brochure.', '.self::BINDINGS[$this->binding];
    }

    /**
     * Where the two staples sit on the spine: the middle of each, in the
     * drawing's coordinates before the tilt. None for a glued binding.
     *
     * @return list<array{x: float, y: float}>
     */
    public function staples(): array
    {
        if ($this->binding !== 'staple') {
            return [];
        }

        $x = $this->cover['x'] - $this->edge['x'] / 2;

        return array_map(
            fn (float $at): array => ['x' => round($x, 2), 'y' => round($this->cover['y'] + $this->cover['height'] * $at + $this->edge['y'] / 2, 2)],
            [0.25, 0.75],
        );
    }

    /**
     * The print runs on round the spine, as a cover's does. This maps a thin
     * strip at the cover's left edge (spineStrip()) onto the spine, turned
     * over at the fold and stretched to the spine's width: the spine takes on
     * the colours of the cover's edge, without a seam and without mirrored
     * pieces of lettering.
     */
    public function spineTransform(): string
    {
        $x = $this->cover['x'];
        $stretch = $this->edge['x'] / self::SPINE_STRIP;
        $slope = $this->edge['y'] / self::SPINE_STRIP;

        return 'matrix('.round(-$stretch, 4).' '.round($slope, 4).' 0 1 '.round($x * (1 + $stretch), 2).' '.round(-$x * $slope, 2).')';
    }

    /**
     * The strip of the cover that spineTransform() lays over the spine, in the
     * cover's coordinates.
     *
     * @return array{x: float, y: float, width: float, height: float}
     */
    public function spineStrip(): array
    {
        return Svg::box($this->cover['x'], $this->cover['y'], self::SPINE_STRIP, $this->cover['height']);
    }
}
