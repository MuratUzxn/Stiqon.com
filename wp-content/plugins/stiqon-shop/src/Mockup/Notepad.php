<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A notepad drawn as inline SVG: a glued pad seen from above, with the binding
 * strip at the top, the company's logo under it and the ruling below.
 *
 * The drawing lives in a 120 x 120 box and draws every format the same size.
 * The binding strip and the ruling keep their size in millimetres, so a
 * smaller pad shows a wider strip and fewer lines – the way it looks on a desk.
 */
final class Notepad implements Kind
{
    /** The formats, with their width in millimetres. */
    public const FORMATS = [
        'a5' => ['label' => 'DIN A5', 'width' => 148.0],
        'a6' => ['label' => 'DIN A6', 'width' => 105.0],
        'a4' => ['label' => 'DIN A4', 'width' => 210.0],
    ];

    /** The rulings by their German names, for the administration and for screen readers. */
    public const RULINGS = ['lined' => 'liniert', 'grid' => 'kariert', 'blank' => 'blanko'];

    /** The top sheet's height in the drawing; its width follows from the DIN A proportion. */
    private const HEIGHT = 92.0;

    public readonly string $uid;

    /** @var array{x: float, y: float, width: float, height: float} the top sheet */
    public readonly array $sheet;

    /** @var array{x: float, y: float} how far the edge of the pad shows below and to the right of the top sheet */
    public readonly array $depth;

    public readonly float $tilt;

    public function __construct(
        public readonly string $format = 'a5',
        public readonly string $motif = 'aktion',
        public readonly string $ruling = 'lined',
        public readonly ?string $label = null,
    ) {
        if (! isset(self::FORMATS[$format])) {
            throw new InvalidArgumentException("Unknown notepad format [{$format}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown notepad motif [{$motif}].");
        }

        if (! isset(self::RULINGS[$ruling])) {
            throw new InvalidArgumentException("Unknown notepad ruling [{$ruling}].");
        }

        $this->uid = Uid::next('np');

        $width = round(self::HEIGHT / 1.414, 2);
        $this->depth = ['x' => 1.6, 'y' => 2.6];

        // A little above the middle: the shadow falls below the pad and needs the room.
        $this->sheet = [
            'x' => round((120 - $width - $this->depth['x']) / 2, 2),
            'y' => 10.0,
            'width' => $width,
            'height' => self::HEIGHT,
        ];

        $this->tilt = -3.0;
    }

    public static function label(): string
    {
        return 'Notizblock';
    }

    public static function keys(): array
    {
        return ['format' => array_keys(self::FORMATS), 'motif' => self::motifs(), 'ruling' => array_keys(self::RULINGS)];
    }

    public static function form(): array
    {
        return ['key' => 'format', 'label' => 'Format', 'values' => array_map(static fn (array $format): string => $format['label'], self::FORMATS)];
    }

    /**
     * Format from the product's name or from an option ("DIN A6"), ruling from
     * the options. "unliniert" is not "liniert": patterns match at the start of
     * a word only.
     */
    public static function names(): array
    {
        $formats = [
            'a5' => 'a ?5',
            'a6' => 'a ?6',
            'a4' => 'a ?4',
        ];

        return [
            'form' => $formats,
            'options' => [
                'format' => $formats,
                'ruling' => [
                    'grid' => 'kariert|karo',
                    'blank' => 'blanko|unliniert|leer',
                    'lined' => 'liniert',
                ],
            ],
            'switches' => [],
        ];
    }

    /**
     * @param  array{format?: string, motif?: string, ruling?: string, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            format: $spec['format'] ?? 'a5',
            motif: $spec['motif'] ?? 'aktion',
            ruling: $spec['ruling'] ?? 'lined',
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs, drawn as the company's logo: the sticker
     * motifs that keep their shape on paper.
     *
     * @return list<string>
     */
    public static function motifs(): array
    {
        return array_values(array_diff(Template::names('motif'), Sticker::CONTOUR_MOTIFS));
    }

    /**
     * The SVG markup. A width class in $class (w-…, md:w-…) replaces the full-width default.
     */
    public function render(string $class = ''): string
    {
        return Template::render('mockup/notepad', ['mockup' => $this, 'class' => $class]);
    }

    /** How many of the sheet's 100 units across one millimetre takes. */
    public function unitsPerMillimetre(): float
    {
        return 100 / self::FORMATS[$this->format]['width'];
    }

    public function describe(): string
    {
        return $this->label ?? 'Notizblock '.self::FORMATS[$this->format]['label'].', '.self::RULINGS[$this->ruling];
    }
}
