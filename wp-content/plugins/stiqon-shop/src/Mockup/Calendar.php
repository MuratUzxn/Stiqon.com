<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A calendar drawn as inline SVG: a wall calendar on a nail, a desk calendar
 * standing like a tent, or a year planner pinned to the wall.
 *
 * The drawing lives in a 120 x 120 box. The motif is the picture of the
 * month – on the year planner, the picture at its head. The sticker motifs
 * are square and bring their own background, so they fill the picture and
 * are cropped from the centre where it is wider than high.
 *
 * The calendar itself – month grid, weekday bars, the planner's columns – is
 * the same for every customer, so it is drawn here and not printed through
 * the motif.
 */
final class Calendar implements Kind
{
    public const VARIANTS = [
        'wall' => 'Wandkalender',
        'desk' => 'Tischkalender',
        'planner' => 'Jahresplaner',
    ];

    /** Days per month of a year like 2027, which begins on a Friday (0 = Monday). */
    private const MONTHS = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    private const NEW_YEAR = 4;

    /**
     * What the year planner shows entered: month, first and last day, and
     * what it is – school holidays, an event of a few days, a single date.
     */
    private const ENTRIES = [
        [2, 15, 19, 'holiday'],
        [3, 18, 18, 'mark'],
        [4, 6, 9, 'event'],
        [5, 26, 26, 'mark'],
        [7, 19, 31, 'holiday'],
        [8, 1, 6, 'holiday'],
        [9, 22, 24, 'event'],
        [10, 25, 29, 'holiday'],
        [11, 12, 12, 'mark'],
        [12, 23, 31, 'holiday'],
    ];

    public readonly string $uid;

    /**
     * The calendar's page. On the wall and on the planner it lies in the
     * drawing's box; the desk calendar's slanted page has a box of its own at
     * 0 0, placed in the drawing by $face.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $sheet;

    /**
     * Where the motif goes, in the coordinates of $sheet.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $picture;

    /**
     * What the days fill, in the coordinates of $sheet: the month grid on the
     * wall and desk calendar, the twelve month columns on the planner.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $grid;

    /**
     * How the desk calendar's page lies in the drawing: turned, slanted and
     * placed, as the values a b c d e f of an SVG matrix. Null for the wall
     * calendar and the planner, which are drawn face on.
     *
     * @var array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float}|null
     */
    public readonly ?array $face;

    /**
     * The desk calendar's open side, as points in the drawing: the top of the
     * ridge, the foot of the page, the foot of the cardboard stand behind it.
     *
     * @var array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}, 2: array{0: float, 1: float}}|null
     */
    public readonly ?array $side;

    /**
     * What the calendar hangs on: the nail of the wall calendar, the two pins of the planner.
     *
     * @var list<array{0: float, 1: float}>
     */
    public readonly array $fixings;

    public readonly float $tilt;

    public function __construct(
        public readonly string $variant = 'wall',
        public readonly string $motif = 'aktion',
        public readonly ?string $label = null,
    ) {
        if (! isset(self::VARIANTS[$variant])) {
            throw new InvalidArgumentException("Unknown calendar variant [{$variant}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown calendar motif [{$motif}].");
        }

        $this->uid = Uid::next('cl');

        match ($variant) {
            'wall' => $this->hangOnTheWall(),
            'desk' => $this->standOnTheDesk(),
            'planner' => $this->pinUpTheYear(),
        };
    }

    public static function label(): string
    {
        return 'Kalender';
    }

    public static function keys(): array
    {
        return ['variant' => array_keys(self::VARIANTS), 'motif' => self::motifs()];
    }

    public static function form(): array
    {
        return ['key' => 'variant', 'label' => 'Ausführung', 'values' => self::VARIANTS];
    }

    /**
     * The kind of calendar from the product's name. Checked in this order: a
     * "Wandplaner" is a planner, not a wall calendar, and a calendar that
     * names neither desk nor planner hangs on the wall ("Streifenkalender").
     */
    public static function names(): array
    {
        return [
            'form' => [
                'desk' => 'tisch|aufstell',
                'planner' => '[a-zäöüß]*planer|jahres',
                'wall' => 'wand|monat|[a-zäöüß]*kalender',
            ],
            'options' => [],
            'switches' => [],
        ];
    }

    /**
     * @param  array{variant?: string, motif?: string, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            variant: $spec['variant'] ?? 'wall',
            motif: $spec['motif'] ?? 'aktion',
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs, drawn as the picture of the month: the sticker motifs.
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
        return Template::render('mockup/calendar', ['mockup' => $this, 'class' => $class]);
    }

    public function describe(): string
    {
        return $this->label ?? self::VARIANTS[$this->variant];
    }

    /**
     * The days of the month on the wall or desk calendar, seven to a row in
     * five rows: a month of 31 days that begins on a Wednesday. "kind" is
     * "blank" before its first and after its last day, "mark" and "note" for
     * two dates someone has marked, "sunday" or "day" otherwise.
     *
     * @return list<array{x: float, y: float, width: float, height: float, kind: string}>
     */
    public function month(): array
    {
        [$gap, $columnPitch, $rowPitch] = $this->monthPitch();
        $cells = [];

        foreach (range(0, 34) as $index) {
            $day = $index - 1;
            $column = $index % 7;

            $cells[] = [
                'x' => round($this->grid['x'] + $column * $columnPitch, 2),
                'y' => round($this->grid['y'] + intdiv($index, 7) * $rowPitch, 2),
                'width' => round($columnPitch - $gap, 2),
                'height' => round($rowPitch - $gap, 2),
                'kind' => match (true) {
                    $day < 1 || $day > 31 => 'blank',
                    $day === 10 => 'mark',
                    $day === 23 => 'note',
                    $column === 6 => 'sunday',
                    default => 'day',
                },
            ];
        }

        return $cells;
    }

    /**
     * The names of the weekdays above the month grid, as short bars over its columns.
     *
     * @return list<array{x: float, y: float, width: float, height: float, kind: string}>
     */
    public function weekdays(): array
    {
        [$gap, $columnPitch] = $this->monthPitch();
        $width = round(($columnPitch - $gap) * 0.42, 2);
        $days = [];

        foreach (range(0, 6) as $column) {
            $days[] = [
                'x' => round($this->grid['x'] + $column * $columnPitch + ($columnPitch - $gap - $width) / 2, 2),
                'y' => round($this->grid['y'] - 2.4, 2),
                'width' => $width,
                'height' => 0.9,
                'kind' => $column === 6 ? 'sunday' : 'day',
            ];
        }

        return $days;
    }

    /**
     * The year planner's twelve columns: a heading for each month and a cell
     * for each of its days. "kind" is "heading", what is entered on that day
     * (see ENTRIES: "holiday", "event", "mark"), "weekend" or "day".
     *
     * @return list<array{x: float, y: float, width: float, height: float, kind: string}>
     */
    public function year(): array
    {
        $gap = 0.8;
        $columnPitch = ($this->grid['width'] + $gap) / 12;
        $heading = 3.0;
        $rowPitch = ($this->grid['height'] - $heading) / 31;
        $weekday = self::NEW_YEAR;
        $cells = [];

        foreach (self::MONTHS as $month => $days) {
            $x = round($this->grid['x'] + $month * $columnPitch, 2);
            $cells[] = ['x' => $x, 'y' => $this->grid['y'], 'width' => round(($columnPitch - $gap) * 0.62, 2), 'height' => 1.3, 'kind' => 'heading'];

            foreach (range(1, $days) as $day) {
                $cells[] = [
                    'x' => $x,
                    'y' => round($this->grid['y'] + $heading + ($day - 1) * $rowPitch, 2),
                    'width' => round($columnPitch - $gap, 2),
                    'height' => round($rowPitch * 0.74, 2),
                    'kind' => self::entry($month + 1, $day) ?? ($weekday >= 5 ? 'weekend' : 'day'),
                ];

                $weekday = ($weekday + 1) % 7;
            }
        }

        return $cells;
    }

    /**
     * Cells as one path for each kind: a planner has 377 cells, and a page
     * shows many drawings, so they are a handful of elements and not one for
     * every day.
     *
     * @param  list<array{x: float, y: float, width: float, height: float, kind: string}>  $cells
     * @return array<string, string>  kind => path data, in the order the kinds first appear
     */
    public static function paths(array $cells, float $radius = 0.0): array
    {
        $paths = [];

        foreach ($cells as $cell) {
            $paths[$cell['kind']] = ($paths[$cell['kind']] ?? '').self::rect($cell['x'], $cell['y'], $cell['width'], $cell['height'], $radius);
        }

        return $paths;
    }

    private static function rect(float $x, float $y, float $width, float $height, float $radius): string
    {
        $r = min($radius, $width / 2, $height / 2);

        if ($r <= 0) {
            return "M{$x} {$y}h{$width}v{$height}h-{$width}z";
        }

        $startX = round($x + $r, 2);
        $innerWidth = round($width - 2 * $r, 2);
        $innerHeight = round($height - 2 * $r, 2);

        return "M{$startX} {$y}h{$innerWidth}a{$r} {$r} 0 0 1 {$r} {$r}v{$innerHeight}a{$r} {$r} 0 0 1 -{$r} {$r}"
            ."h-{$innerWidth}a{$r} {$r} 0 0 1 -{$r} -{$r}v-{$innerHeight}a{$r} {$r} 0 0 1 {$r} -{$r}z";
    }

    private static function entry(int $month, int $day): ?string
    {
        foreach (self::ENTRIES as [$entryMonth, $first, $last, $kind]) {
            if ($entryMonth === $month && $day >= $first && $day <= $last) {
                return $kind;
            }
        }

        return null;
    }

    /**
     * Gap between the cells of the month grid – a fiftieth of its width, so a
     * smaller grid keeps its look – and the distance from one column and from
     * one row to the next.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    private function monthPitch(): array
    {
        $gap = $this->grid['width'] / 50;

        return [$gap, ($this->grid['width'] + $gap) / 7, ($this->grid['height'] + $gap) / 5];
    }

    /**
     * A portrait calendar, A3 in proportion, on a nail: its binding carries a
     * wire hanger in the middle. It hangs a little askew, as calendars on one
     * nail do.
     */
    private function hangOnTheWall(): void
    {
        $width = 62.0;
        $height = 87.6;
        $x = 60 - $width / 2;
        $y = 20.2;

        $this->sheet = ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height];
        $this->picture = ['x' => $x + 4, 'y' => $y + 5.5, 'width' => $width - 8, 'height' => 46.0];
        $this->grid = ['x' => $x + 4, 'y' => $y + 62.5, 'width' => $width - 8, 'height' => 21.5];
        $this->face = null;
        $this->side = null;
        $this->fixings = [[60.0, 11.6]];
        $this->tilt = -2.0;
    }

    /**
     * A landscape desk calendar on a folded cardboard stand, seen from the
     * front left and a little from above: the page leans back to the ridge
     * with the binding, and the open side shows the stand behind it.
     */
    private function standOnTheDesk(): void
    {
        $width = 80.0;
        $height = 54.0;

        // The page's x runs along the ridge, rising slightly to the right; its
        // y runs down the slanted page, towards the viewer and so a little to
        // the right. The stand runs down from the ridge away from the viewer:
        // its foot lies further left than the page's, and higher.
        $along = [0.986, -0.052];
        $down = [0.11, 0.88];
        $stand = [-0.25, 0.6];
        $ridge = [24.3, 37.9];

        $this->face = [$along[0], $along[1], $down[0], $down[1], $ridge[0], $ridge[1]];
        $this->sheet = ['x' => 0.0, 'y' => 0.0, 'width' => $width, 'height' => $height];
        $this->picture = ['x' => 3.5, 'y' => 5.0, 'width' => 40.0, 'height' => 45.5];
        $this->grid = ['x' => 47.0, 'y' => 20.5, 'width' => 29.5, 'height' => 30.0];
        $this->side = [
            $ridge,
            [round($ridge[0] + $down[0] * $height, 2), round($ridge[1] + $down[1] * $height, 2)],
            [round($ridge[0] + $stand[0] * $height, 2), round($ridge[1] + $stand[1] * $height, 2)],
        ];
        $this->fixings = [];
        $this->tilt = 0.0;
    }

    /**
     * A landscape year planner, A1 in proportion, pinned up at its two top corners.
     */
    private function pinUpTheYear(): void
    {
        $width = 98.0;
        $height = 69.3;
        $x = 60 - $width / 2;
        $y = round(59 - $height / 2, 2);

        $this->sheet = ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height];
        $this->picture = ['x' => $x + 5, 'y' => $y + 5, 'width' => 17.0, 'height' => 17.0];
        $this->grid = ['x' => $x + 5, 'y' => $y + 26.0, 'width' => $width - 10, 'height' => $height - 29.5];
        $this->face = null;
        $this->side = null;
        $this->fixings = [[$x + 3.2, $y + 3.2], [$x + $width - 3.2, $y + 3.2]];
        $this->tilt = -2.0;
    }
}
