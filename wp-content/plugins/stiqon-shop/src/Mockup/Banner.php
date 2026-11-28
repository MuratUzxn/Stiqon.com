<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A PVC banner drawn as inline SVG: hemmed, with metal eyelets along its
 * border, tied by short ropes at its corners to a frame outside the picture.
 *
 * The drawing lives in a 120 x 120 box. Every format is 100 cm tall and is
 * drawn the same width, so a longer banner shows as a narrower strip. Its
 * sample motifs are drawn for 3 : 1 and keep their essentials in the centre,
 * where 2 : 1 and 4 : 1 crop them.
 */
final class Banner implements Kind
{
    /** Widths in cm; every banner is 100 cm tall. */
    public const FORMATS = [
        '200x100' => ['label' => '200 × 100 cm', 'width' => 200],
        '300x100' => ['label' => '300 × 100 cm', 'width' => 300],
        '400x100' => ['label' => '400 × 100 cm', 'width' => 400],
    ];

    /** Height of every format in cm. */
    private const HEIGHT = 100;

    /** Eyelets are set every 50 cm, as printers do. */
    private const SPACING = 50;

    /** Width of every banner in the drawing, whatever its format. */
    private const WIDTH = 94.0;

    /** Eyelets sit in the hem, this far in from the edge. */
    private const INSET = 2.2;

    /** Tied only at its corners, the banner's long edges are pulled in by this much in their middle, its short edges by less. */
    private const SAG = 0.8;

    /** Length of the ropes from the corner eyelets out to the frame. */
    private const ROPE = 9.0;

    public readonly string $uid;

    /** @var array{x: float, y: float, width: float, height: float} */
    public readonly array $sheet;

    /**
     * Centres of the eyelets, one every 50 cm around the border.
     *
     * @var list<array{0: float, 1: float}>
     */
    public readonly array $eyelets;

    /**
     * The ropes, each from a corner eyelet to where it leaves the picture.
     *
     * @var list<array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}>
     */
    public readonly array $ropes;

    public readonly float $tilt;

    public function __construct(
        public readonly string $format = '200x100',
        public readonly string $motif = 'angebot',
        public readonly ?string $label = null,
    ) {
        if (! isset(self::FORMATS[$format])) {
            throw new InvalidArgumentException("Unknown banner format [{$format}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown banner motif [{$motif}].");
        }

        $this->uid = Uid::next('bn');

        $centimetres = self::FORMATS[$format]['width'];
        $width = self::WIDTH;
        $height = round($width * self::HEIGHT / $centimetres, 2);

        // A little above the middle: the shadow falls below and needs the room.
        $this->sheet = Svg::box(round(60 - $width / 2, 2), round(57 - $height / 2, 2), $width, $height);

        $this->eyelets = $this->border(intdiv($centimetres, self::SPACING), intdiv(self::HEIGHT, self::SPACING));
        $this->ropes = $this->corners();

        // Hung by hand, a banner is rarely quite level.
        $this->tilt = -2.0;
    }

    public static function label(): string
    {
        return 'Banner';
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
     * Format from the product's name or from a chosen option ("300 × 100 cm").
     */
    public static function names(): array
    {
        $formats = [
            '200x100' => '200 ?[x×] ?100',
            '300x100' => '300 ?[x×] ?100',
            '400x100' => '400 ?[x×] ?100',
        ];

        return ['form' => $formats, 'options' => ['format' => $formats], 'switches' => []];
    }

    /**
     * @param  array{format?: string, motif?: string, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            format: $spec['format'] ?? '200x100',
            motif: $spec['motif'] ?? 'angebot',
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs drawn on banners, made for the long format.
     *
     * @return list<string>
     */
    public static function motifs(): array
    {
        return Template::names('motif/banner');
    }

    /**
     * The SVG markup. A width class in $class (w-…, md:w-…) replaces the full-width default.
     */
    public function render(string $class = ''): string
    {
        return Template::render('mockup/banner', ['mockup' => $this, 'class' => $class]);
    }

    public function describe(): string
    {
        return $this->label ?? 'Banner '.self::FORMATS[$this->format]['label'];
    }

    /**
     * The banner's outline: straight from corner to corner where the ropes
     * pull, its edges drawn in a little in between. $inset follows the outline
     * that far inside it, for the hem.
     */
    public function outline(float $inset = 0.0): string
    {
        $left = round($this->sheet['x'] + $inset, 2);
        $top = round($this->sheet['y'] + $inset, 2);
        $right = round($this->sheet['x'] + $this->sheet['width'] - $inset, 2);
        $bottom = round($this->sheet['y'] + $this->sheet['height'] - $inset, 2);
        $middle = round(($left + $right) / 2, 2);
        $level = round(($top + $bottom) / 2, 2);

        // A quadratic curve bends away from its ends by half its control point's offset.
        $long = 2 * self::SAG;
        $short = self::SAG;

        return "M{$left} {$top}"
            .'Q'.$middle.' '.round($top + $long, 2).' '.$right.' '.$top
            .'Q'.round($right - $short, 2).' '.$level.' '.$right.' '.$bottom
            .'Q'.$middle.' '.round($bottom - $long, 2).' '.$left.' '.$bottom
            .'Q'.round($left + $short, 2).' '.$level.' '.$left.' '.$top.'z';
    }

    /**
     * Eyelets around the border: $across steps along the long edges, $down
     * along the short ones, each set in from the drawn-in outline.
     *
     * @return list<array{0: float, 1: float}>
     */
    private function border(int $across, int $down): array
    {
        ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height] = $this->sheet;
        $inner = $width - 2 * self::INSET;
        $tall = $height - 2 * self::INSET;
        $eyelets = [];

        for ($step = 0; $step <= $across; $step++) {
            $along = $step / $across;
            $pull = 4 * $along * (1 - $along) * self::SAG;
            $left = round($x + self::INSET + $along * $inner, 2);

            $eyelets[] = [$left, round($y + self::INSET + $pull, 2)];
            $eyelets[] = [$left, round($y + $height - self::INSET - $pull, 2)];
        }

        // The corners are already there.
        for ($step = 1; $step < $down; $step++) {
            $along = $step / $down;
            $pull = 4 * $along * (1 - $along) * self::SAG / 2;
            $top = round($y + self::INSET + $along * $tall, 2);

            $eyelets[] = [round($x + self::INSET + $pull, 2), $top];
            $eyelets[] = [round($x + $width - self::INSET - $pull, 2), $top];
        }

        return $eyelets;
    }

    /**
     * Ropes leave the corner eyelets diagonally outwards, the way the frame pulls.
     *
     * @return list<array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}>
     */
    private function corners(): array
    {
        ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height] = $this->sheet;
        $reach = self::ROPE * M_SQRT1_2;
        $ropes = [];

        foreach ([[-1, -1], [1, -1], [1, 1], [-1, 1]] as [$right, $down]) {
            $start = [
                round($x + ($right < 0 ? self::INSET : $width - self::INSET), 2),
                round($y + ($down < 0 ? self::INSET : $height - self::INSET), 2),
            ];

            $ropes[] = [$start, [round($start[0] + $right * $reach, 2), round($start[1] + $down * $reach, 2)]];
        }

        return $ropes;
    }
}
