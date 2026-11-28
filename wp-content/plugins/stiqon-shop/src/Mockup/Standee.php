<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A Kundenstopper drawn as inline SVG: an A-frame for the pavement, its
 * poster held in a silver click frame with mitred corners.
 *
 * The drawing lives in a 120 x 120 box and is seen from the front and a
 * little from the left, so the frame's far side stands higher and the second
 * leg shows behind the first. The poster has the DIN A proportion of the
 * flyer motifs. Both formats stand on the same ground: A0 rises higher.
 */
final class Standee implements Kind
{
    /** Heights of the poster in the drawing's box; A0 is drawn a step taller than A1, not twice as large. */
    public const FORMATS = [
        'a1' => ['label' => 'DIN A1', 'height' => 70.0],
        'a0' => ['label' => 'DIN A0', 'height' => 80.0],
    ];

    /** Width of the frame's profile around the poster. */
    public const PROFILE = 3.4;

    /** How far the legs reach below the frame, and the feet below them. */
    public const LEGS = 8.6;

    public const FEET = 1.8;

    /** Where the feet stand. */
    private const GROUND = 106.0;

    public readonly string $uid;

    /** @var array{x: float, y: float, width: float, height: float} */
    public readonly array $poster;

    /**
     * The frame around the poster, its outer edge.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $frame;

    /**
     * The second leg's foot, behind and to the left of the first.
     *
     * @var array{0: float, 1: float}
     */
    public readonly array $back;

    /** Bottom of the front feet. */
    public readonly float $ground;

    public readonly float $tilt;

    public function __construct(
        public readonly string $format = 'a1',
        public readonly string $motif = 'eroeffnung',
        public readonly ?string $label = null,
    ) {
        if (! isset(self::FORMATS[$format])) {
            throw new InvalidArgumentException("Unknown standee format [{$format}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown standee motif [{$motif}].");
        }

        $this->uid = Uid::next('sd');

        $height = self::FORMATS[$format]['height'];
        $width = round($height / M_SQRT2, 2);
        $frameWidth = round($width + 2 * self::PROFILE, 2);
        $frameHeight = round($height + 2 * self::PROFILE, 2);
        $frameTop = round(self::GROUND - self::FEET - self::LEGS - $frameHeight, 2);

        // The second leg stands further back: from the left it shows beside
        // the first, a little higher up. It spreads with the frame's height.
        $tall = self::GROUND - $frameTop;
        $spread = round(0.13 * $tall, 2);
        $rise = round(0.05 * $tall, 2);

        // Frame and second leg are centred as one.
        $left = round(60 - ($frameWidth + $spread) / 2 + $spread, 2);

        $this->frame = Svg::box($left, $frameTop, $frameWidth, $frameHeight);
        $this->poster = Svg::box(round($left + self::PROFILE, 2), round($frameTop + self::PROFILE, 2), $width, $height);
        $this->back = [round($left - $spread, 2), round(self::GROUND - $rise, 2)];
        $this->ground = self::GROUND;

        // Seen from the left, the far side stands higher.
        $this->tilt = -3.0;
    }

    public static function label(): string
    {
        return 'Kundenstopper';
    }

    public static function keys(): array
    {
        return ['format' => array_keys(self::FORMATS), 'motif' => self::motifs()];
    }

    public static function form(): array
    {
        return ['key' => 'format', 'label' => 'Format', 'values' => array_map(static fn (array $format): string => $format['label'], self::FORMATS)];
    }

    /**
     * Format from the product's name or from a chosen option ("DIN A1").
     */
    public static function names(): array
    {
        $formats = [
            'a1' => 'a ?1',
            'a0' => 'a ?0',
        ];

        return ['form' => $formats, 'options' => ['format' => $formats], 'switches' => []];
    }

    /**
     * @param  array{format?: string, motif?: string, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            format: $spec['format'] ?? 'a1',
            motif: $spec['motif'] ?? 'eroeffnung',
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs drawn on the poster: the flyer motifs, made
     * for the DIN A proportion.
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
        return Template::render('mockup/standee', ['mockup' => $this, 'class' => $class]);
    }

    public function describe(): string
    {
        return $this->label ?? 'Kundenstopper '.self::FORMATS[$this->format]['label'];
    }
}
