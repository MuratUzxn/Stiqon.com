<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A mug drawn as inline SVG: standing, seen from the side and a little from
 * above, so the rim is an ellipse and a little of the inside shows; the handle
 * on the right and a print on the side facing the viewer.
 *
 * The drawing lives in a 120 x 120 box. The print is laid flat onto the
 * body and clipped to it; a light and shade that runs round the cylinder lies
 * over it, so it reads as wrapped round the mug – the motif itself is never
 * bent.
 */
final class Mug implements Kind
{
    /**
     * Width and height of the body, how thick its wall is at the rim, how far
     * the handle's outer edge reaches out from the body and how thick the
     * handle is, in the drawing's units. Both at one scale: a ceramic mug of
     * 8.2 x 9.5 cm (330 ml) with a sturdy handle, and a slimmer enamel mug of
     * 7.1 x 9.5 cm with a thin wall and a handle of bent sheet steel.
     */
    public const STYLES = [
        'classic' => ['label' => 'Kaffeetasse', 'width' => 67.0, 'height' => 78.0, 'wall' => 1.8, 'reach' => 22.0, 'grip' => 6.4],
        'enamel' => ['label' => 'Emaillebecher', 'width' => 58.0, 'height' => 78.0, 'wall' => 0.8, 'reach' => 20.0, 'grip' => 3.0],
    ];

    public const COLORS = ['white', 'black'];

    /** The colours by their German names, for the description. */
    public const COLOR_NAMES = ['white' => 'Weiß', 'black' => 'Schwarz'];

    /** How far the viewer looks down onto the mug: the rim's height as a share of its width. */
    public const VIEW = 0.19;

    /** The same for the base, which lies further below the eye and so is seen a little rounder. */
    public const BASE_VIEW = 0.23;

    /** The print takes this share of the body's width: a logo on the side facing the viewer. */
    public const PRINT = 0.58;

    public readonly string $uid;

    /**
     * The body: from the left end of the rim to the right, and from the rim's
     * middle down to the middle of the base.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $body;

    /**
     * The rim as an ellipse: its middle and radii.
     *
     * @var array{cx: float, cy: float, rx: float, ry: float}
     */
    public readonly array $rim;

    /**
     * The base as an ellipse; a ceramic mug's foot is a little narrower than its rim.
     *
     * @var array{cx: float, cy: float, rx: float, ry: float}
     */
    public readonly array $base;

    /** The outer wall as far as it can be seen: from the front of the rim down to the front of the base. */
    public readonly string $wall;

    /** The opening inside the rim, through which the inside shows. */
    public readonly string $opening;

    /** The handle's middle line, from where it leaves the body at the top to where it joins it below. */
    public readonly string $handle;

    /**
     * Where the motif is printed: a square in the middle of the wall.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $printBox;

    public function __construct(
        public readonly string $style = 'classic',
        public readonly string $motif = 'aktion',
        public readonly string $color = 'white',
        public readonly ?string $label = null,
    ) {
        if (! isset(self::STYLES[$style])) {
            throw new InvalidArgumentException("Unknown mug style [{$style}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown mug motif [{$motif}].");
        }

        if (! in_array($color, self::COLORS, true)) {
            throw new InvalidArgumentException("Unknown mug color [{$color}].");
        }

        $this->uid = Uid::next('mg');

        ['width' => $width, 'height' => $height, 'wall' => $wall, 'reach' => $reach, 'grip' => $grip] = self::STYLES[$style];
        $classic = $style === 'classic';
        $rx = $width / 2;
        $ry = round($rx * self::VIEW, 2);
        // Ceramic rounds into a foot a little narrower than the mug; pressed steel ends in a crisp edge.
        $baseRx = $classic ? $rx - 1.6 : $rx;
        $baseRy = round($baseRx * self::BASE_VIEW, 2);

        // Mug and handle centred across, a little above the middle: the shadow falls below.
        $x = round(60 - ($width + $reach) / 2, 2);
        $cx = round($x + $rx, 2);
        $top = round(57.5 - ($ry + $height + $baseRy) / 2 + $ry, 2);
        $bottom = round($top + $height, 2);

        $this->body = Svg::box($x, $top, $width, $height);
        $this->rim = ['cx' => $cx, 'cy' => $top, 'rx' => $rx, 'ry' => $ry];
        $this->base = ['cx' => $cx, 'cy' => $bottom, 'rx' => $baseRx, 'ry' => $baseRy];

        $left = $cx - $rx;
        $right = $cx + $rx;
        $p = static fn (float $px, float $py): string => round($px, 2).' '.round($py, 2);

        // From the rim's left end along its front edge to the right, down the side and back along the base.
        $outline = 'M'.$p($left, $top)."A{$rx} {$ry} 0 0 0 ".$p($right, $top);

        if ($classic) {
            // The side turns in over its last few units and runs on, without a
            // kink, into the front edge of the foot.
            $round = 6.5;
            $turn = deg2rad(22.0);
            $meet = [$baseRx * cos($turn), $bottom + $baseRy * sin($turn)];
            $tangent = [-$baseRx * sin($turn), $baseRy * cos($turn)];
            $pull = 3.2 / hypot(...$tangent);
            $before = [$meet[0] - $tangent[0] * $pull, $meet[1] - $tangent[1] * $pull];

            $outline .= 'L'.$p($right, $bottom - $round)
                .'C'.$p($right, $bottom - 0.45 * $round).' '.$p($cx + $before[0], $before[1]).' '.$p($cx + $meet[0], $meet[1])
                ."A{$baseRx} {$baseRy} 0 0 1 ".$p($cx - $meet[0], $meet[1])
                .'C'.$p($cx - $before[0], $before[1]).' '.$p($left, $bottom - 0.45 * $round).' '.$p($left, $bottom - $round);
        } else {
            $outline .= 'L'.$p($right, $bottom)."A{$baseRx} {$baseRy} 0 0 1 ".$p($left, $bottom);
        }

        $this->wall = $outline.'z';

        $innerRx = round($rx - $wall, 2);
        $innerRy = round($ry * $innerRx / $rx, 2);
        $this->opening = 'M'.$p($cx - $innerRx, $top)
            ."A{$innerRx} {$innerRy} 0 1 0 ".$p($cx + $innerRx, $top)
            ."A{$innerRx} {$innerRy} 0 1 0 ".$p($cx - $innerRx, $top).'z';

        // The handle leaves the body a little below the rim and joins it again
        // above the foot, a rounded loop out to the right. Its ends lie inside
        // the body's edge, which covers them.
        $from = $top + 0.16 * $height;
        $to = $top + 0.7 * $height;
        $out = $right + $reach - $grip / 2;
        $this->handle = 'M'.$p($right - 2.5, $from)
            .'C'.$p($right + 0.55 * $reach, $from - 2.5).' '.$p($out, $from + 3.0).' '.$p($out, ($from + $to) / 2)
            .'C'.$p($out, $to - 4.5).' '.$p($right + 0.5 * $reach, $to + 1.5).' '.$p($right - 2.5, $to);

        // Centred on the wall between the rim's front edge and the base's.
        $print = round(self::PRINT * $width, 2);
        $middle = ($top + $ry + $bottom + $baseRy) / 2;
        $this->printBox = Svg::box(round($cx - $print / 2, 2), round($middle - $print / 2, 2), $print, $print);
    }

    public static function label(): string
    {
        return 'Tasse';
    }

    public static function keys(): array
    {
        return ['style' => array_keys(self::STYLES), 'motif' => self::motifs(), 'color' => self::COLORS];
    }

    public static function form(): array
    {
        return ['key' => 'style', 'label' => 'Ausführung', 'values' => array_map(static fn (array $style): string => $style['label'], self::STYLES)];
    }

    /**
     * Style from the product's name, colour from the chosen option. Enamel is
     * asked first: an "Emaillebecher" is a "Becher" as well.
     */
    public static function names(): array
    {
        return [
            'form' => [
                'enamel' => 'emaille|email|camping|enamel',
                'classic' => 'tasse|becher|kaffee|keramik|porzellan',
            ],
            'options' => [
                'color' => [
                    'white' => 'wei[sß]|white',
                    'black' => 'schwarz|black',
                ],
            ],
            'switches' => [],
        ];
    }

    /**
     * @param  array{style?: string, motif?: string, color?: string, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            style: $spec['style'] ?? 'classic',
            motif: $spec['motif'] ?? 'aktion',
            color: $spec['color'] ?? 'white',
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs printed on mugs, as a logo: the sticker
     * motifs that keep their shape printed bare. A contour motif's shape is
     * the sticker's outline – on the glaze "stern" would be its lettering
     * alone, and the mug would look unprinted.
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
        return Template::render('mockup/mug', ['mockup' => $this, 'class' => $class]);
    }

    /**
     * How the sample motif is printed: straight onto the glaze or the enamel,
     * without a ground of its own – in dark ink on a white mug, in light ink
     * on a black one, which would swallow dark ink (see $ink in the motif
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
        return $this->label ?? self::STYLES[$this->style]['label'].', '.self::COLOR_NAMES[$this->color];
    }
}
