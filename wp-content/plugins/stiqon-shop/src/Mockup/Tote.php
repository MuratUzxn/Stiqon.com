<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A cotton tote drawn as inline SVG: lying flat and a little askew, with soft
 * folds in the fabric, its two handles laid out above it and a print in the
 * middle.
 *
 * The drawing lives in a 120 x 120 box. Bag and handles fill the card about
 * the same with long and with short handles, so beside long loops the bag
 * itself is drawn smaller – which is what tells the two apart at a glance.
 */
final class Tote implements Kind
{
    /**
     * The bag's width and height, how far the handle loops reach above it and
     * how wide the straps are, in the drawing's units: a tote of 38 x 42 cm
     * with straps of 2.5 cm, long ones of about 70 cm for the shoulder or short
     * ones of about 35 cm for the hand.
     */
    public const HANDLES = [
        'long' => ['label' => 'Lange Henkel', 'width' => 51.0, 'height' => 56.0, 'loop' => 35.0, 'strap' => 3.4],
        'short' => ['label' => 'Kurze Henkel', 'width' => 64.0, 'height' => 70.5, 'loop' => 20.0, 'strap' => 4.2],
    ];

    public const COLORS = ['natural', 'black', 'white'];

    /** The colours by their German names, for the description. */
    public const COLOR_NAMES = ['natural' => 'natur', 'black' => 'schwarz', 'white' => 'weiß'];

    /** A bag laid down on a table is rarely straight. */
    public const TILT = -3.0;

    public readonly string $uid;

    /**
     * The bag without its handles.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $body;

    /** Outline of the bag: the hem straight, the seams a little uneven, as cloth lies. */
    public readonly string $outline;

    /** How deep the hem at the top is, where the straps are sewn in. */
    public readonly float $hem;

    public readonly float $strap;

    /**
     * Where the middle of each strap goes into the hem, left and right.
     *
     * @var array{0: float, 1: float}
     */
    public readonly array $feet;

    /**
     * The two straps as paths along their middle: the one at the back, then
     * the one in front. Their ends lie under the bag, where they are sewn in.
     *
     * @var array{0: string, 1: string}
     */
    public readonly array $straps;

    /**
     * Where the motif is printed: a square in the middle of the bag, below the hem.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $printBox;

    public function __construct(
        public readonly string $handles = 'long',
        public readonly string $motif = 'aktion',
        public readonly string $color = 'natural',
        public readonly ?string $label = null,
    ) {
        if (! isset(self::HANDLES[$handles])) {
            throw new InvalidArgumentException("Unknown tote handles [{$handles}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown tote motif [{$motif}].");
        }

        if (! in_array($color, self::COLORS, true)) {
            throw new InvalidArgumentException("Unknown tote color [{$color}].");
        }

        $this->uid = Uid::next('tt');

        ['width' => $width, 'height' => $height, 'loop' => $loop, 'strap' => $strap] = self::HANDLES[$handles];
        $this->strap = $strap;

        // Bag and handles centred, a little above the middle: the shadow falls below.
        $x = round(60 - $width / 2, 2);
        $y = round(57 - ($height - $loop - $strap / 2) / 2, 2);
        $right = $x + $width;
        $bottom = $y + $height;
        $this->body = Svg::box($x, $y, $width, $height);
        $this->hem = round(0.065 * $height, 2);

        $p = static fn (float $px, float $py): string => round($px, 2).' '.round($py, 2);

        $this->outline = 'M'.$p($x + 0.8, $y)
            .'L'.$p($right - 0.8, $y + 0.25)
            .'Q'.$p($right, $y + 0.25).' '.$p($right + 0.05, $y + 1.2)
            .'C'.$p($right + 0.7, $y + 0.35 * $height).' '.$p($right - 0.35, $y + 0.68 * $height).' '.$p($right + 0.3, $bottom - 1.6)
            .'Q'.$p($right + 0.4, $bottom + 0.1).' '.$p($right - 1.4, $bottom)
            .'C'.$p($x + 0.66 * $width, $bottom + 0.55).' '.$p($x + 0.33 * $width, $bottom - 0.25).' '.$p($x + 1.3, $bottom + 0.15)
            .'Q'.$p($x - 0.3, $bottom + 0.2).' '.$p($x - 0.2, $bottom - 1.5)
            .'C'.$p($x + 0.45, $y + 0.66 * $height).' '.$p($x - 0.6, $y + 0.33 * $height).' '.$p($x, $y + 1)
            .'Q'.$p($x, $y).' '.$p($x + 0.8, $y).'z';

        // The straps are sewn in a quarter of the bag's width from each side.
        $this->feet = [round(60 - $width / 4, 2), round(60 + $width / 4, 2)];

        // A strap lies in a loop from foot to foot; the one at the back has slipped
        // a little to the right and up, so both show.
        $strapLoop = function (float $shiftX, float $lift) use ($p, $y, $loop, $handles): string {
            [$from, $to] = $this->feet;
            $base = $y + $this->hem;
            $half = ($to - $from) / 2;
            $topX = $from + $half + $shiftX;
            $topY = $y - $loop - $lift;
            [$leg, $round] = $handles === 'long' ? [0.62, 0.58] : [0.8, 0.62];

            return 'M'.$p($from, $base)
                .'C'.$p($from - 1.1 + $shiftX * 0.3, $y - $leg * $loop).' '.$p($topX - $round * $half, $topY).' '.$p($topX, $topY)
                .'C'.$p($topX + $round * $half, $topY).' '.$p($to + 1.1 + $shiftX * 0.5, $y - $leg * $loop).' '.$p($to, $base);
        };

        $this->straps = [$strapLoop(3.0, 1.8), $strapLoop(0.0, 0.0)];

        $print = round(0.54 * $width, 2);
        $this->printBox = Svg::box(round(60 - $print / 2, 2), round($y + $this->hem + ($height - $this->hem - $print) / 2, 2), $print, $print);
    }

    public static function label(): string
    {
        return 'Baumwolltasche';
    }

    public static function keys(): array
    {
        return ['handles' => array_keys(self::HANDLES), 'motif' => self::motifs(), 'color' => self::COLORS];
    }

    public static function form(): array
    {
        return ['key' => 'handles', 'label' => 'Henkel', 'values' => array_map(static fn (array $handles): string => $handles['label'], self::HANDLES)];
    }

    /**
     * Handles from the product's name or from a chosen option ("Lange
     * Henkel"), colour from the option. Natural is asked first: "Naturweiß"
     * is unbleached cotton, not white.
     */
    public static function names(): array
    {
        $handles = ['long' => 'lang|long', 'short' => 'kurz|short'];

        return [
            'form' => $handles,
            'options' => [
                'handles' => $handles,
                'color' => [
                    'natural' => 'natur|beige|ecru|creme|ungebleicht|off-?white',
                    'black' => 'schwarz|black',
                    'white' => 'wei[sß]|white',
                ],
            ],
            'switches' => [],
        ];
    }

    /**
     * @param  array{handles?: string, motif?: string, color?: string, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            handles: $spec['handles'] ?? 'long',
            motif: $spec['motif'] ?? 'aktion',
            color: $spec['color'] ?? 'natural',
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs printed on totes: the sticker motifs that
     * keep their shape in print. A contour motif's shape is the sticker's
     * outline (Sticker::CONTOUR_MOTIFS): printed straight onto the cotton,
     * only a few words would be left, and the bag would look unprinted.
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
        return Template::render('mockup/tote', ['mockup' => $this, 'class' => $class]);
    }

    /**
     * How the sample motif is printed: straight onto the cotton, without a
     * ground of its own – in dark ink on natural and white cotton, in light
     * ink on black, which would swallow dark ink (see $ink in the motif
     * templates).
     *
     * @return array{bare: true, ink: 'dark'|'light'}
     */
    public function motifVars(): array
    {
        return ['bare' => true, 'ink' => $this->color === 'black' ? 'light' : 'dark'];
    }

    public function describe(): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        $handles = ['long' => 'langen', 'short' => 'kurzen'][$this->handles];

        return "Baumwolltasche mit {$handles} Henkeln, ".self::COLOR_NAMES[$this->color];
    }
}
