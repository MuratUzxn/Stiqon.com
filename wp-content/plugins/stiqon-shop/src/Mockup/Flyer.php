<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A flyer drawn as inline SVG: one sheet, optionally on a small stack.
 *
 * The drawing lives in a 120 x 150 box. Sample motifs are designed for the
 * DIN A proportion (1 : 1.414) and are cropped from the centre for the long
 * and the square format.
 */
final class Flyer implements Kind
{
    public const FORMATS = [
        'a6' => ['label' => 'DIN A6', 'width' => 74.0, 'height' => 104.6],
        'a5' => ['label' => 'DIN A5', 'width' => 74.0, 'height' => 104.6],
        'a4' => ['label' => 'DIN A4', 'width' => 74.0, 'height' => 104.6],
        'lang' => ['label' => 'DIN lang', 'width' => 52.0, 'height' => 104.0],
        'quadrat' => ['label' => 'Quadrat', 'width' => 86.0, 'height' => 86.0],
    ];

    public readonly string $uid;

    /** @var array{x: float, y: float, width: float, height: float} */
    public readonly array $sheet;

    public readonly float $tilt;

    public function __construct(
        public readonly string $format = 'a6',
        public readonly string $motif = 'fest',
        public readonly bool $stack = false,
        public readonly bool $heavy = false,
        public readonly bool $gloss = false,
        public readonly ?string $label = null,
    ) {
        if (! isset(self::FORMATS[$format])) {
            throw new InvalidArgumentException("Unknown flyer format [{$format}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown flyer motif [{$motif}].");
        }

        $this->uid = Uid::next('fl');

        $width = self::FORMATS[$format]['width'];
        $height = self::FORMATS[$format]['height'];

        $this->sheet = [
            'x' => (120 - $width) / 2,
            'y' => (150 - $height) / 2,
            'width' => $width,
            'height' => $height,
        ];

        $this->tilt = $stack ? -4.0 : -2.0;
    }

    public static function label(): string
    {
        return 'Flyer';
    }

    public static function keys(): array
    {
        return ['format' => array_keys(self::FORMATS), 'motif' => self::motifs(), 'stack' => null, 'heavy' => null, 'gloss' => null];
    }

    public static function form(): array
    {
        return ['key' => 'format', 'label' => 'Format', 'values' => array_map(static fn (array $format): string => $format['label'], self::FORMATS)];
    }

    /**
     * Format from the product's name. Whether the paper is drawn as the
     * stiffer sheet follows from its grammage (see Catalog\Guess::option()).
     */
    public static function names(): array
    {
        return [
            'form' => [
                'lang' => 'din ?lang|\bdl\b|lang',
                'quadrat' => 'quadrat|square',
                'a6' => '\ba ?6\b',
                'a5' => '\ba ?5\b',
                'a4' => '\ba ?4\b',
            ],
            'options' => [],
            'switches' => ['gloss' => ['gl[aä]nz|gloss', 'matt']],
        ];
    }

    /**
     * @param  array{format?: string, motif?: string, stack?: bool, heavy?: bool, gloss?: bool, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            format: $spec['format'] ?? 'a6',
            motif: $spec['motif'] ?? 'fest',
            stack: (bool) ($spec['stack'] ?? false),
            heavy: (bool) ($spec['heavy'] ?? false),
            gloss: (bool) ($spec['gloss'] ?? false),
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs drawn for flyers.
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
        return Template::render('mockup/flyer', ['mockup' => $this, 'class' => $class]);
    }

    public function describe(): string
    {
        return $this->label ?? 'Flyer '.self::FORMATS[$this->format]['label'].($this->stack ? ', Stapel' : '');
    }
}
