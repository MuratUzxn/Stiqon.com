<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A double-walled drink bottle drawn as inline SVG: standing upright, seen from
 * the front and a little from above, with a screw cap, a carry loop on the cap
 * and a logo on its body.
 *
 * The drawing lives in a 120 x 120 box. Both sizes carry the same cap, as the
 * bottles of one range do; the larger bottle is taller and a little wider in
 * the body, so the two tell apart at a glance. A bottle is narrow, so its
 * height fills the box: the larger one nearly from top to bottom.
 *
 * The logo is printed onto the round body: clipped to it, with the body's
 * light and shade laid over it, so it reads as wrapped around.
 */
final class Bottle implements Kind
{
    /**
     * Width of the body and height of the whole bottle, loop included, in the
     * drawing's units. True to scale, bottles of 7 x 26 cm and 8 x 29 cm would
     * stand like sticks beside the other products: both are drawn stouter, and
     * the larger one a little taller than true, so it stands out.
     */
    public const SIZES = [
        '500' => ['label' => '500 ml', 'width' => 30.0, 'height' => 83.0],
        '750' => ['label' => '750 ml', 'width' => 33.0, 'height' => 95.0],
    ];

    public const COLORS = ['silver', 'black', 'white'];

    /** The colours by their German names, for the description. */
    public const COLOR_NAMES = ['silver' => 'Silber', 'black' => 'Schwarz', 'white' => 'Weiß'];

    /** The screw cap: the same on every size. */
    public const CAP = ['width' => 17.4, 'height' => 10.6];

    /** The carry loop on the cap: how high it rises, how far apart its feet are and how thick it is. */
    public const LOOP = ['height' => 6.6, 'span' => 11.6, 'band' => 1.9];

    /** The bare steel of the bottle's mouth, showing as a narrow band under the cap. */
    public const RING = 1.4;

    /**
     * How much of a circle on the bottle shows from a little above: its
     * height as a share of its width. The cap's top, the cap's rim and the
     * bottle's foot are drawn as ellipses of this shape.
     */
    public const VIEW = 0.16;

    public readonly string $uid;

    public readonly float $width;

    public readonly float $height;

    /** Where the bottle's top (the loop) and its lowest point (the front of the foot) are. */
    public readonly float $top;

    public readonly float $floor;

    /**
     * The cap without its loop: from the middle of its top face down to its
     * rim at the sides.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $cap;

    /** Where the round shoulder meets the straight body. */
    public readonly float $shoulder;

    /** Where the straight body ends at the sides and the foot curves under it. */
    public readonly float $foot;

    /** Outline of the body below the cap: shoulder, straight sides and round foot. */
    public readonly string $outline;

    /** The loop along its middle, from one foot on the cap over to the other. */
    public readonly string $loop;

    /**
     * Where the logo is printed: a square on the straight body, a little above its middle.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $printBox;

    public function __construct(
        public readonly string $size = '500',
        public readonly string $motif = 'aktion',
        public readonly string $color = 'silver',
        public readonly ?string $label = null,
    ) {
        if (! isset(self::SIZES[$size])) {
            throw new InvalidArgumentException("Unknown bottle size [{$size}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown bottle motif [{$motif}].");
        }

        if (! in_array($color, self::COLORS, true)) {
            throw new InvalidArgumentException("Unknown bottle color [{$color}].");
        }

        $this->uid = Uid::next('bt');
        $this->width = self::SIZES[$size]['width'];
        $this->height = self::SIZES[$size]['height'];

        // The bottle centred a little above the middle: its shadow falls below.
        $this->top = round(56.5 - $this->height / 2, 2);
        $this->floor = round($this->top + $this->height, 2);

        $capTop = round($this->top + self::LOOP['height'], 2);
        $capBottom = round($capTop + self::CAP['height'], 2);
        $this->cap = Svg::box(round(60 - self::CAP['width'] / 2, 2), $capTop, self::CAP['width'], self::CAP['height']);

        // The shoulder rounds out from the mouth to the full width; the wider
        // the body, the longer the shoulder.
        $neck = round($capBottom + self::RING, 2);
        $this->shoulder = round($neck + 0.3 * $this->width, 2);
        $this->foot = round($this->floor - self::VIEW * $this->width / 2 - 0.5, 2);

        $p = static fn (float $px, float $py): string => round($px, 2).' '.round($py, 2);
        $half = $this->width / 2;
        $mouth = $this->ring()['width'] / 2;
        $reach = $half - $mouth;
        $drop = $this->shoulder - $neck;
        $left = 60 - $half;
        $right = 60 + $half;

        // The shoulder leaves the mouth almost level and turns down into the
        // straight body; the foot is the front half of the bottom's ellipse, a
        // little deeper than the cap's, as its edge is rounded.
        $this->outline = 'M'.$p(60 - $mouth, $neck)
            .'C'.$p(60 - $mouth - 0.55 * $reach, $neck + 0.04 * $drop).' '.$p($left, $this->shoulder - 0.6 * $drop).' '.$p($left, $this->shoulder)
            .'L'.$p($left, $this->foot)
            .'A'.$p($half, $this->floor - $this->foot).' 0 0 0 '.$p($right, $this->foot)
            .'L'.$p($right, $this->shoulder)
            .'C'.$p($right, $this->shoulder - 0.6 * $drop).' '.$p(60 + $mouth + 0.55 * $reach, $neck + 0.04 * $drop).' '.$p(60 + $mouth, $neck)
            .'z';

        // The loop stands on the cap's top face; a cubic curve whose control
        // points lie straight above its feet reaches three quarters of their height.
        $apex = $this->top + self::LOOP['band'] / 2;
        $lift = ($capTop - $apex) / 0.75;
        $from = 60 - self::LOOP['span'] / 2;
        $to = 60 + self::LOOP['span'] / 2;

        $this->loop = 'M'.$p($from, $capTop)
            .'C'.$p($from, $capTop - $lift).' '.$p($to, $capTop - $lift).' '.$p($to, $capTop);

        $print = round(0.7 * $this->width, 2);
        $middle = $this->shoulder + 0.44 * ($this->foot - $this->shoulder);
        $this->printBox = Svg::box(round(60 - $print / 2, 2), round($middle - $print / 2, 2), $print, $print);
    }

    public static function label(): string
    {
        return 'Trinkflasche';
    }

    public static function keys(): array
    {
        return ['size' => array_map('strval', array_keys(self::SIZES)), 'motif' => self::motifs(), 'color' => self::COLORS];
    }

    /**
     * The sizes are named by their volume. As array keys "500" and "750" turn
     * into numbers; keys() lists them as the strings that are stored.
     */
    public static function form(): array
    {
        return ['key' => 'size', 'label' => 'Füllmenge', 'values' => array_map(static fn (array $size): string => $size['label'], self::SIZES)];
    }

    /**
     * Size from the product's name or from a chosen option ("500 ml",
     * "0,75 l"), not from a larger volume that ends in the same digits
     * ("1500 ml"). Colour from the option. Black and white are asked first:
     * a black bottle is of stainless steel as well ("Edelstahlflasche, schwarz").
     */
    public static function names(): array
    {
        $sizes = [
            '500' => '(?<![\d,.])(?:500 ?ml|0[,.]50? ?l)',
            '750' => '(?<![\d,.])(?:750 ?ml|0[,.]75 ?l)',
        ];

        return [
            'form' => $sizes,
            'options' => [
                'size' => $sizes,
                'color' => [
                    'black' => 'schwarz|black',
                    'white' => 'wei[sß]|white',
                    'silver' => 'silber|silver|edelstahl|alu|stainless',
                ],
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
            size: $spec['size'] ?? '500',
            motif: $spec['motif'] ?? 'aktion',
            color: $spec['color'] ?? 'silver',
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs printed on bottles: the sticker motifs, as a logo.
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
        return Template::render('mockup/bottle', ['mockup' => $this, 'class' => $class]);
    }

    /**
     * How the sample logo is printed: straight onto the bottle, without a
     * ground of its own – in dark ink on steel and on white powder coat, in
     * light ink on black, which would swallow dark ink (see $ink in the motif
     * templates).
     *
     * @return array{bare: true, ink: 'dark'|'light'}
     */
    public function motifVars(): array
    {
        return ['bare' => true, 'ink' => $this->color === 'black' ? 'light' : 'dark'];
    }

    /**
     * The band of bare steel between cap and shoulder: a little narrower than
     * the cap, whose rim overhangs it.
     *
     * @return array{x: float, y: float, width: float, height: float}
     */
    public function ring(): array
    {
        $width = round(self::CAP['width'] - 1.4, 2);

        return Svg::box(round(60 - $width / 2, 2), round($this->cap['y'] + $this->cap['height'], 2), $width, self::RING);
    }

    public function describe(): string
    {
        return $this->label ?? 'Trinkflasche '.self::SIZES[$this->size]['label'].', '.self::COLOR_NAMES[$this->color];
    }
}
