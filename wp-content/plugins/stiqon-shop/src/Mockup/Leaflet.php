<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A folded leaflet drawn as inline SVG: DIN lang panels standing on their
 * edge, partly opened so the fold shows.
 *
 * The drawing lives in a 120 x 120 box and is seen from the front, a little
 * from above. Each panel is turned by its own angle; the drawing follows from
 * those angles: how wide a panel looks, how far back it stands, how much light
 * it catches. The side that shows is the outside – the front panel with the
 * motif, the others with text and a picture.
 *
 * Sample motifs are the flyers' (1 : 1.414), cropped from the centre to the
 * front panel's DIN lang.
 */
final class Leaflet implements Kind
{
    /** The folds by their German names. */
    public const FOLDS = ['wickel' => 'Wickelfalz', 'zickzack' => 'Zickzackfalz', 'einbruch' => 'Einbruchfalz'];

    /**
     * How each fold stands: its panels from left to right, each as how far it
     * is turned away from the viewer (degrees; positive: its right edge stands
     * further back) and what is printed on the side that shows.
     */
    private const POSES = [
        // Rolled: the inner flap turned in half way, the back, the front. Both folds turn the same way.
        'wickel' => [[-72.0, 'page'], [-30.0, 'page'], [18.0, 'cover']],
        // The front, then panels that turn away and back in turn, like an accordion.
        'zickzack' => [[28.0, 'cover'], [-48.0, 'page'], [28.0, 'page']],
        // Folded once down the middle and stood up half open, like a book seen from its spine.
        'einbruch' => [[-42.0, 'page'], [20.0, 'cover']],
    ];

    /** DIN lang: 210 mm high, 99 mm wide. */
    private const RATIO = 210 / 99;

    /** How much higher a point is drawn per unit it stands further back: the leaflet is seen a little from above. */
    private const RISE = 0.2;

    /** Where the light comes from, seen from above: front right (x to the right, z away from the viewer). */
    private const LIGHT = [0.5, -0.866];

    /** The largest the drawing grows in either direction, so it fills its card like a sticker or a flyer. */
    private const EXTENT = 94.0;

    public readonly string $uid;

    public readonly string $motif;

    /** Width and height of one panel, unfolded. */
    public readonly float $panelWidth;

    public readonly float $panelHeight;

    /**
     * The panels from left to right: what is printed on them ("cover" or
     * "page"), where their top corners stand in the drawing, the matrix that
     * lays an unfolded panel (0 0 width height) onto them, and how much light
     * they catch (0 to 1).
     *
     * @var list<array{face: string, left: array{0: float, 1: float}, right: array{0: float, 1: float}, matrix: string, light: float}>
     */
    public readonly array $panels;

    /**
     * The folds between the panels: where they stand, and whether they point
     * towards the viewer (a ridge) or away (a valley).
     *
     * @var list<array{x: float, y: float, ridge: bool}>
     */
    public readonly array $folds;

    public function __construct(
        public readonly string $fold = 'wickel',
        ?string $motif = null,
        public readonly bool $heavy = false,
        public readonly bool $gloss = false,
        public readonly ?string $label = null,
    ) {
        $motif ??= self::motifs()[0];

        if (! isset(self::FOLDS[$fold])) {
            throw new InvalidArgumentException("Unknown leaflet fold [{$fold}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown leaflet motif [{$motif}].");
        }

        $this->uid = Uid::next('lf');
        $this->motif = $motif;

        [$this->panelWidth, $this->panelHeight, $this->panels, $this->folds] = $this->stand(self::POSES[$fold]);
    }

    public static function label(): string
    {
        return 'Faltblatt';
    }

    public static function keys(): array
    {
        return ['fold' => array_keys(self::FOLDS), 'motif' => self::motifs(), 'heavy' => null, 'gloss' => null];
    }

    public static function form(): array
    {
        return ['key' => 'fold', 'label' => 'Falzart', 'values' => self::FOLDS];
    }

    /**
     * Fold from the product's name. Whether the paper is drawn as the stiffer
     * sheet follows from its grammage (see Catalog\Guess::option()).
     */
    public static function names(): array
    {
        return [
            'form' => [
                'wickel' => 'wickel',
                'zickzack' => 'zickzack|leporello',
                'einbruch' => 'einbruch|parallel|klapp',
            ],
            'options' => [],
            'switches' => ['gloss' => ['gl[aä]nz', 'matt']],
        ];
    }

    /**
     * @param  array{fold?: string, motif?: string, heavy?: bool, gloss?: bool, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            fold: $spec['fold'] ?? 'wickel',
            motif: $spec['motif'] ?? null,
            heavy: (bool) ($spec['heavy'] ?? false),
            gloss: (bool) ($spec['gloss'] ?? false),
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs drawn on a leaflet's front panel: the flyers'.
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
        return Template::render('mockup/leaflet', ['mockup' => $this, 'class' => $class]);
    }

    public function describe(): string
    {
        return $this->label ?? 'Faltblatt mit '.self::FOLDS[$this->fold];
    }

    /**
     * How much darker a panel is drawn at its left and at its right edge:
     * the less light it catches, the darker, and darker still into a valley
     * fold, where the panels shade each other.
     *
     * @return array{0: float, 1: float}
     */
    public function shade(int $panel): array
    {
        $base = 0.26 * (1 - $this->panels[$panel]['light']);
        $edge = static fn (?array $fold): float => match (true) {
            $fold === null => $base,
            $fold['ridge'] => max(0.0, $base - 0.04),
            default => $base + 0.09,
        };

        return [round($edge($this->folds[$panel - 1] ?? null), 3), round($edge($this->folds[$panel] ?? null), 3)];
    }

    /**
     * The outline of a panel as a path, in the drawing's coordinates.
     */
    public function outline(int $panel): string
    {
        [$x0, $y0] = $this->panels[$panel]['left'];
        [$x1, $y1] = $this->panels[$panel]['right'];
        $h = $this->panelHeight;

        return "M{$x0} {$y0}L{$x1} {$y1}V".round($y1 + $h, 2)."L{$x0} ".round($y0 + $h, 2).'z';
    }

    /**
     * Stands the panels up: from their angles to where they are drawn.
     *
     * Seen from above, the panels form a chain of unit steps; each step goes
     * cos(angle) to the right and sin(angle) further back. In the drawing a
     * step to the right stays a step to the right, and a step back rises by
     * RISE – a view from a little above without perspective, so every panel
     * stays a parallelogram that an unfolded panel maps onto exactly.
     *
     * @param  list<array{0: float, 1: string}>  $pose
     * @return array{0: float, 1: float, 2: list<array{face: string, left: array{0: float, 1: float}, right: array{0: float, 1: float}, matrix: string, light: float}>, 3: list<array{x: float, y: float, ridge: bool}>}
     */
    private function stand(array $pose): array
    {
        $chain = [[0.0, 0.0]];

        foreach ($pose as [$turn]) {
            [$x, $z] = $chain[array_key_last($chain)];
            $chain[] = [$x + cos(deg2rad($turn)), $z + sin(deg2rad($turn))];
        }

        $depths = array_column($chain, 1);
        $back = max($depths);
        $across = $chain[array_key_last($chain)][0];
        $tall = self::RATIO + ($back - min($depths)) * self::RISE;

        // One panel's width in the drawing, as large as the box allows.
        $unit = min(self::EXTENT / $across, self::EXTENT / $tall);
        $left = (120 - $across * $unit) / 2;
        $top = (120 - $tall * $unit) / 2;
        $width = round($unit, 2);
        $height = round(self::RATIO * $unit, 2);

        $at = static fn (array $point): array => [
            round($left + $point[0] * $unit, 2),
            round($top + ($back - $point[1]) * self::RISE * $unit, 2),
        ];

        $panels = [];
        $folds = [];

        foreach ($pose as $index => [$turn, $face]) {
            $from = $at($chain[$index]);
            $to = $at($chain[$index + 1]);
            $angle = deg2rad($turn);
            // The printed side faces the viewer; its normal, seen from above.
            $light = sin($angle) * self::LIGHT[0] - cos($angle) * self::LIGHT[1];

            $panels[] = [
                'face' => $face,
                'left' => $from,
                'right' => $to,
                'matrix' => 'matrix('.round(($to[0] - $from[0]) / $width, 4).' '.round(($to[1] - $from[1]) / $width, 4).' 0 1 '.$from[0].' '.$from[1].')',
                'light' => round(max(0.0, min(1.0, $light)), 3),
            ];

            if ($index > 0) {
                // A panel turned further away than the one before makes a fold that points at the viewer.
                $folds[] = ['x' => $from[0], 'y' => $from[1], 'ridge' => $turn > $pose[$index - 1][0]];
            }
        }

        return [$width, $height, $panels, $folds];
    }
}
