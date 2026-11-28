<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A paper carrier bag drawn as inline SVG: standing, open, seen from the
 * front and a little from the right, so its folded gusset shows; two twisted
 * paper handles and a logo printed on the front.
 *
 * The drawing lives in a 120 x 120 box. The front faces the viewer and the
 * depth recedes up and to the right (oblique projection), so the print on
 * the front needs no perspective of its own. The two sizes keep the
 * proportions of the real bags; the small one is drawn a little larger than
 * to scale, so it stays well visible beside the large one.
 */
final class Bag implements Kind
{
    /**
     * Width, height and depth of the bag, and how high its handles rise above
     * the top edge, in the drawing's units.
     */
    public const SIZES = [
        'small' => ['label' => 'Klein', 'width' => 44.0, 'height' => 58.0, 'depth' => 18.0, 'handle' => 15.5, 'cord' => 1.3],
        'large' => ['label' => 'Groß', 'width' => 53.0, 'height' => 68.0, 'depth' => 20.0, 'handle' => 18.0, 'cord' => 1.45],
    ];

    public const COLORS = ['kraft', 'white'];

    /** The colours by their German names, for the description. */
    public const COLOR_NAMES = ['kraft' => 'braun', 'white' => 'weiß'];

    /** How far apart the two ends of a handle are glued: the same on every bag, as the machines make them. */
    private const HANDLE_GAP = 15.0;

    /** Direction in which the depth recedes, in degrees above the horizontal, and how much it is shortened. */
    private const RECEDE_ANGLE = 40.0;

    private const RECEDE_SCALE = 0.62;

    public readonly string $uid;

    /**
     * The front panel.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $panel;

    /**
     * How far the back of the bag lies right of and above its front.
     *
     * @var array{0: float, 1: float}
     */
    public readonly array $recede;

    /** Outline of the whole bag. */
    public readonly string $outline;

    /** The opening between the top edges, where the inside shows. */
    public readonly string $opening;

    /**
     * The gusset in two halves – towards the front and towards the back – folded
     * inwards along its middle.
     *
     * @var array{0: string, 1: string}
     */
    public readonly array $gusset;

    /** Creases left from when the bag lay flat: across the bottom of the front and in the gusset. */
    public readonly string $creases;

    /**
     * The handles, as paths of the cord: the one at the back, then the one at the front.
     *
     * @var array{0: string, 1: string}
     */
    public readonly array $handles;

    /**
     * The strip of paper glued over the back handle's ends, inside the bag.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $patch;

    /**
     * Where the logo is printed.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $printBox;

    public function __construct(
        public readonly string $size = 'small',
        public readonly string $motif = 'aktion',
        public readonly string $color = 'kraft',
        public readonly ?string $label = null,
    ) {
        if (! isset(self::SIZES[$size])) {
            throw new InvalidArgumentException("Unknown bag size [{$size}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown bag motif [{$motif}].");
        }

        if (! in_array($color, self::COLORS, true)) {
            throw new InvalidArgumentException("Unknown bag color [{$color}].");
        }

        $this->uid = Uid::next('bg');

        ['width' => $width, 'height' => $height, 'depth' => $depth, 'handle' => $handle] = self::SIZES[$size];

        $angle = deg2rad(self::RECEDE_ANGLE);
        $dx = round($depth * self::RECEDE_SCALE * cos($angle), 2);
        $dy = round(-$depth * self::RECEDE_SCALE * sin($angle), 2);
        $this->recede = [$dx, $dy];

        // Bag and handles centred, a little above the middle: the shadow falls below.
        $x = round(60 - ($width + $dx) / 2, 2);
        $y = round(57 - ($height + $dy - $handle) / 2, 2);
        $this->panel = Svg::box($x, $y, $width, $height);

        $right = $x + $width;
        $bottom = $y + $height;
        // The gussets fold inwards along their middle, so their top edges dip there.
        $notch = round(0.18 * $depth, 2);
        $p = static fn (float $px, float $py): string => round($px, 2).' '.round($py, 2);

        $this->outline = 'M'.$p($x, $bottom).'L'.$p($x, $y).'L'.$p($x + $dx / 2, $y + $dy / 2 + $notch).'L'.$p($x + $dx, $y + $dy)
            .'L'.$p($right + $dx, $y + $dy).'L'.$p($right + $dx, $bottom + $dy).'L'.$p($right, $bottom).'z';
        $this->opening = 'M'.$p($x, $y).'L'.$p($x + $dx / 2, $y + $dy / 2 + $notch).'L'.$p($x + $dx, $y + $dy)
            .'L'.$p($right + $dx, $y + $dy).'L'.$p($right + $dx / 2, $y + $dy / 2 + $notch).'L'.$p($right, $y).'z';
        $this->gusset = [
            'M'.$p($right, $y).'L'.$p($right + $dx / 2, $y + $dy / 2 + $notch).'L'.$p($right + $dx / 2, $bottom + $dy / 2).'L'.$p($right, $bottom).'z',
            'M'.$p($right + $dx / 2, $y + $dy / 2 + $notch).'L'.$p($right + $dx, $y + $dy).'L'.$p($right + $dx, $bottom + $dy).'L'.$p($right + $dx / 2, $bottom + $dy / 2).'z',
        ];

        // Folded flat, the bottom lay against the front, half the depth high.
        $fold = round($depth / 2, 2);
        $this->creases = 'M'.$p($x, $bottom - $fold).'H'.round($right, 2)
            .'M'.$p($right, $bottom).'L'.$p($right + $dx / 2, $bottom + $dy / 2 - $fold).'L'.$p($right + $dx, $bottom + $dy);

        // A twisted handle rises from inside the bag and bulges a little outwards.
        $left = $x + $width / 2 - self::HANDLE_GAP / 2;
        $flare = 0.42 * self::HANDLE_GAP;
        $loop = static function (float $ox, float $oy, float $start) use ($p, $left, $flare, $y, $handle): string {
            $from = $left + $ox;
            $to = $from + self::HANDLE_GAP;
            // The curve's top reaches three quarters of the way to its control points.
            $rise = ($handle + $start) / 0.75;
            $base = $y + $oy + $start;

            return 'M'.$p($from, $base).'C'.$p($from - $flare, $base - $rise).' '.$p($to + $flare, $base - $rise).' '.$p($to, $base);
        };

        // The back handle's ends show inside the bag, under the patch; the front one's are hidden by the front.
        $this->handles = [$loop($dx, $dy, 3.6), $loop(0.0, 0.0, 5.0)];
        $this->patch = Svg::box(round($left + $dx - 2.6, 2), round($y + $dy + 1.3, 2), round(self::HANDLE_GAP + 5.2, 2), 5.4);

        // A logo a little above the middle, where a bag is printed.
        $print = round(0.52 * $width, 2);
        $this->printBox = Svg::box(round($x + ($width - $print) / 2, 2), round($y + 0.47 * $height - $print / 2, 2), $print, $print);
    }

    public static function label(): string
    {
        return 'Papiertragetasche';
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
     * Size from the product's name or from a chosen option ("Klein (24 × 10
     * × 32 cm)"), colour from the option. White is asked first: a white bag
     * may come "mit braunen Kordeln".
     */
    public static function names(): array
    {
        $sizes = [
            'small' => 'klein|small|\bs\b',
            'large' => 'gro[sß]|large|\bl\b',
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
            size: $spec['size'] ?? 'small',
            motif: $spec['motif'] ?? 'aktion',
            color: $spec['color'] ?? 'kraft',
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs printed on bags: the sticker motifs, as a logo.
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
        return Template::render('mockup/bag', ['mockup' => $this, 'class' => $class]);
    }

    /** Whether the sample motif is drawn without its own background: on kraft the brown shows through. */
    public function bareMotif(): bool
    {
        return $this->color === 'kraft';
    }

    public function describe(): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        $size = ['small' => 'Kleine', 'large' => 'Große'][$this->size];

        return "{$size} Papiertragetasche, ".self::COLOR_NAMES[$this->color];
    }
}
