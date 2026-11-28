<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A letterhead drawn as inline SVG: one sheet with the company's logo at the
 * top and the lines of a business letter as grey bars, optionally on a small
 * stack of further sheets.
 *
 * The drawing lives in a 120 x 120 box and draws both formats the same size.
 * The letter on it is laid out in the sheet's own 100 x 141.4 box (the DIN A
 * proportion), with type and margins that take up more of the smaller sheet –
 * as they do on paper – so an A5 letter shows fewer, larger lines than an A4 one.
 */
final class Letter implements Kind
{
    /**
     * The formats, with how large type and margins are drawn on the sheet
     * relative to A4.
     */
    public const FORMATS = [
        'a4' => ['label' => 'DIN A4', 'scale' => 1.0],
        'a5' => ['label' => 'DIN A5', 'scale' => 1.22],
    ];

    /** The sheet's height in the drawing; its width follows from the DIN A proportion. */
    private const HEIGHT = 96.0;

    public readonly string $uid;

    /** @var array{x: float, y: float, width: float, height: float} */
    public readonly array $sheet;

    /** How large type and margins are drawn on the sheet (see FORMATS). */
    public readonly float $scale;

    public readonly float $tilt;

    public function __construct(
        public readonly string $format = 'a4',
        public readonly string $motif = 'aktion',
        public readonly bool $stack = false,
        public readonly ?string $label = null,
    ) {
        if (! isset(self::FORMATS[$format])) {
            throw new InvalidArgumentException("Unknown letter format [{$format}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown letter motif [{$motif}].");
        }

        $this->uid = Uid::next('le');

        $width = round(self::HEIGHT / 1.414, 2);

        // A little above the middle: the shadow falls below the sheet and needs the room.
        $this->sheet = [
            'x' => round((120 - $width) / 2, 2),
            'y' => 10.0,
            'width' => $width,
            'height' => self::HEIGHT,
        ];

        $this->scale = self::FORMATS[$format]['scale'];
        $this->tilt = -3.0;
    }

    public static function label(): string
    {
        return 'Briefbogen';
    }

    public static function keys(): array
    {
        return ['format' => array_keys(self::FORMATS), 'motif' => self::motifs(), 'stack' => null];
    }

    public static function form(): array
    {
        return ['key' => 'format', 'label' => 'Format', 'values' => array_map(static fn (array $format): string => $format['label'], self::FORMATS)];
    }

    /**
     * Format from the product's name; a letterhead is A4 unless it says
     * otherwise. The names of options say nothing the drawing shows.
     */
    public static function names(): array
    {
        return [
            'form' => [
                'a4' => 'a ?4',
                'a5' => 'a ?5',
            ],
            'options' => [],
            'switches' => [],
        ];
    }

    /**
     * @param  array{format?: string, motif?: string, stack?: bool, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            format: $spec['format'] ?? 'a4',
            motif: $spec['motif'] ?? 'aktion',
            stack: (bool) ($spec['stack'] ?? false),
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
        return Template::render('mockup/letter', ['mockup' => $this, 'class' => $class]);
    }

    public function describe(): string
    {
        return $this->label ?? 'Briefbogen '.self::FORMATS[$this->format]['label'].($this->stack ? ', Stapel' : '');
    }
}
