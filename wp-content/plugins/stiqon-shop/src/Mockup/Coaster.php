<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A beer mat drawn as inline SVG: a disc or a square of thick board, lying on
 * the table a little askew and seen from above at an angle, so its cut edge
 * shows below the printed top. Optionally on a small stack of mats.
 *
 * The drawing lives in a 120 x 120 box. The top is drawn once in its own
 * coordinates – a square from 0 0, with corners rounded all the way into a
 * circle for the round mat – and put in place by one matrix (see matrix()):
 * turned in its plane, then squashed in height as a flat thing seen at an
 * angle is. The print goes onto the top with the same matrix, so it lies on
 * the mat as real print does.
 *
 * The edge is drawn in the drawing's own coordinates (see outline() and
 * side()), so the board's fibres and its light run level along it.
 */
final class Coaster implements Kind
{
    /**
     * The top in its own coordinates: the side of the square or the
     * diameter, and how round the corners are. A round mat of 107 mm and a
     * square one of 93 mm, true to each other.
     */
    public const SHAPES = [
        'round' => ['label' => 'Rund', 'size' => 94.0, 'radius' => 47.0],
        'square' => ['label' => 'Eckig', 'size' => 82.0, 'radius' => 6.5],
    ];

    /** How far the mat is turned in its plane, in degrees: a mat put down on a table is rarely straight. */
    public const TURN = -9.0;

    /** How much of the mat's depth shows from above: the height of the round mat as a share of its width. */
    public const VIEW = 0.8;

    /** How thick the cut edge of one mat shows – thicker than true, so the board reads as board. */
    public const EDGE = 2.3;

    /**
     * Mats under the top one when they lie on a stack, each pushed a little
     * aside as a stack put together by hand is: how far, from the top one down.
     */
    public const STACK = [0.0, 0.7, -0.5, 0.9];

    public readonly string $uid;

    /** The side of the top in its own coordinates, and the radius of its corners. */
    public readonly float $size;

    public readonly float $radius;

    /**
     * The top of the uppermost mat as the six numbers of an SVG matrix() that
     * maps it, from its own coordinates, into place.
     *
     * @var list<float>
     */
    public readonly array $face;

    /**
     * Where the top is widest across, left and right, in the drawing's
     * coordinates: the edge runs straight down from these two points.
     *
     * @var array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}
     */
    public readonly array $widest;

    /**
     * Where the drawing reaches, the edge of every mat included.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $extent;

    public function __construct(
        public readonly string $shape = 'round',
        public readonly string $motif = 'aktion',
        public readonly bool $stack = false,
        public readonly ?string $label = null,
    ) {
        if (! isset(self::SHAPES[$shape])) {
            throw new InvalidArgumentException("Unknown coaster shape [{$shape}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown coaster motif [{$motif}].");
        }

        $this->uid = Uid::next('co');
        $this->size = self::SHAPES[$shape]['size'];
        $this->radius = self::SHAPES[$shape]['radius'];

        // Turned in its plane, then squashed in height.
        $turn = deg2rad(self::TURN);
        [$a, $b, $c, $d] = [cos($turn), self::VIEW * sin($turn), -sin($turn), self::VIEW * cos($turn)];

        // How far the top reaches from its middle: its corners' circles, pushed
        // out by the stretch of the square between them.
        $straight = $this->size / 2 - $this->radius;
        $across = $straight * (abs($a) + abs($c)) + $this->radius * hypot($a, $c);
        $down = $straight * (abs($b) + abs($d)) + $this->radius * hypot($b, $d);

        // Mats and edges centred, a little above the middle: the shadow falls below.
        $height = 2 * $down + count($this->levels()) * self::EDGE;
        $middle = 57 - $height / 2 + $down;
        $half = $this->size / 2;

        $this->face = array_map(static fn (float $number): float => round($number, 4), [
            $a, $b, $c, $d,
            60 - ($a * $half + $c * $half),
            $middle - ($b * $half + $d * $half),
        ]);

        // Widest across: on the circle of the corner that lies furthest out,
        // where the outline runs straight down.
        $centres = [[$this->radius, $this->radius], [$this->size - $this->radius, $this->radius], [$this->size - $this->radius, $this->size - $this->radius], [$this->radius, $this->size - $this->radius]];
        $reach = static fn (array $centre): float => $a * $centre[0] + $c * $centre[1];
        usort($centres, static fn (array $one, array $other): int => $reach($one) <=> $reach($other));
        $unit = [$a / hypot($a, $c), $c / hypot($a, $c)];

        $this->widest = [
            $this->place($centres[0][0] - $this->radius * $unit[0], $centres[0][1] - $this->radius * $unit[1]),
            $this->place($centres[3][0] + $this->radius * $unit[0], $centres[3][1] + $this->radius * $unit[1]),
        ];

        $shifts = array_map(static fn (int $level): float => self::STACK[$level], $this->levels());
        $this->extent = Svg::box(
            round(60 - $across + min($shifts), 2),
            round($middle - $down, 2),
            round(2 * $across + max($shifts) - min($shifts), 2),
            round($height, 2),
        );
    }

    public static function label(): string
    {
        return 'Bierdeckel';
    }

    public static function keys(): array
    {
        return ['shape' => array_keys(self::SHAPES), 'motif' => self::motifs(), 'stack' => null];
    }

    public static function form(): array
    {
        return ['key' => 'shape', 'label' => 'Zuschnitt', 'values' => array_map(static fn (array $shape): string => $shape['label'], self::SHAPES)];
    }

    /**
     * Shape from the product's name or from a chosen option ("Rund 107 mm",
     * "Eckig 93 × 93 mm"). "Viereckig" is square too, though "eckig" does
     * not start the word there.
     */
    public static function names(): array
    {
        $shapes = [
            'round' => 'rund|kreis|round|circle',
            'square' => 'eckig|quadrat|viereck|square',
        ];

        return [
            'form' => $shapes,
            'options' => ['shape' => $shapes],
            'switches' => [],
        ];
    }

    /**
     * @param  array{shape?: string, motif?: string, stack?: bool, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            shape: $spec['shape'] ?? 'round',
            motif: $spec['motif'] ?? 'aktion',
            stack: (bool) ($spec['stack'] ?? false),
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs printed on beer mats: the sticker motifs.
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
        return Template::render('mockup/coaster', ['mockup' => $this, 'class' => $class]);
    }

    /**
     * How the sample motif is printed: with its own ground, all over the top
     * and up to the edge, as beer mats are printed.
     *
     * @return array{bare: false}
     */
    public function motifVars(): array
    {
        return ['bare' => false];
    }

    /**
     * The mats from the bottom up, by how far each lies below the top one: 0
     * is the top one, the printed one.
     *
     * @return list<int>
     */
    public function levels(): array
    {
        return $this->stack ? range(count(self::STACK) - 1, 0) : [0];
    }

    /** The printed top as an SVG transform (see $face). */
    public function matrix(): string
    {
        return 'matrix('.implode(' ', $this->face).')';
    }

    /**
     * Outline of a mat's top in the drawing's coordinates, a whole number of
     * mats and a share of one edge further down. Turning leaves the circle of
     * each corner a circle, and the squash makes it an ellipse of the same
     * width and VIEW of its height, lying level: one arc command each. The
     * round mat is all corners, with no straight stretch between them.
     */
    public function outline(int $level = 0, float $depth = 0.0): string
    {
        $s = $this->size;
        $r = $this->radius;
        $arc = 'A'.$this->number($r).' '.$this->number(self::VIEW * $r).' 0 0 1 ';
        $at = fn (float $u, float $v): string => implode(' ', array_map([$this, 'number'], $this->place($u, $v, $level, $depth)));
        $line = static fn (string $point): string => $s - 2 * $r > 0.01 ? 'L'.$point : '';

        return 'M'.$at($r, 0).$line($at($s - $r, 0)).$arc.$at($s, $r)
            .$line($at($s, $s - $r)).$arc.$at($s - $r, $s)
            .$line($at($r, $s)).$arc.$at(0, $s - $r)
            .$line($at(0, $r)).$arc.$at($r, 0).'z';
    }

    /**
     * The band between the widest points of a mat's top at two depths (shares
     * of its edge). With the outline at either depth it covers the edge
     * between them: the top and its copy further down, joined at their sides.
     */
    public function side(int $level, float $from, float $to): string
    {
        $points = [];

        foreach ([[$this->widest[0], $from], [$this->widest[1], $from], [$this->widest[1], $to], [$this->widest[0], $to]] as [[$x, $y], $depth]) {
            [$shiftX, $shiftY] = $this->shift($level, $depth);
            $points[] = $this->number($x + $shiftX).','.$this->number($y + $shiftY);
        }

        return implode(' ', $points);
    }

    public function describe(): string
    {
        return $this->label ?? 'Bierdeckel, '.['round' => 'rund', 'square' => 'eckig'][$this->shape].($this->stack ? ', Stapel' : '');
    }

    /**
     * A point of the top, from its own coordinates into the drawing's.
     *
     * @return array{0: float, 1: float}
     */
    private function place(float $u, float $v, int $level = 0, float $depth = 0.0): array
    {
        [$a, $b, $c, $d, $e, $f] = $this->face;
        [$shiftX, $shiftY] = $this->shift($level, $depth);

        return [round($a * $u + $c * $v + $e + $shiftX, 2), round($b * $u + $d * $v + $f + $shiftY, 2)];
    }

    /**
     * How far a mat's top, or a depth within its edge, lies from the top of the uppermost mat.
     *
     * @return array{0: float, 1: float}
     */
    private function shift(int $level, float $depth): array
    {
        return [self::STACK[$level], ($level + $depth) * self::EDGE];
    }

    private function number(float $number): string
    {
        return (string) round($number, 2);
    }
}
