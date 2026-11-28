<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A printed card drawn as inline SVG: a business card, a postcard, a square or
 * a long card, lying flat – alone or on a small stack – or folded and standing
 * like a tent. Its finish shows as shine, as the velvet of soft-touch film or as
 * a frame of hot foil.
 *
 * The drawing lives in a 120 x 120 box. Sample motifs are designed in the
 * business card's proportion (85 : 55) and are cropped from the centre for the
 * other formats, so they keep their essentials in the middle.
 */
final class Card implements Kind
{
    /** Sizes in the drawing: true to each format's proportion, each filling the box. */
    public const FORMATS = [
        'visitenkarte' => ['label' => 'Visitenkarte 85 × 55 mm', 'width' => 94.0, 'height' => 60.8],
        'postkarte' => ['label' => 'Postkarte DIN A6', 'width' => 92.0, 'height' => 65.3],
        'quadrat' => ['label' => 'Quadratisch 148 × 148 mm', 'width' => 80.0, 'height' => 80.0],
        'lang' => ['label' => 'DIN lang 210 × 99 mm', 'width' => 97.0, 'height' => 45.7],
    ];

    public const FINISHES = ['plain', 'softtouch', 'gold', 'silver'];

    /** Half the angle between the two sides of a folded card standing open, in degrees. */
    private const TENT_ANGLE = 24.0;

    /** A folded card is seen from the front and a little from the right and above: depth runs right and up by these shares. */
    private const DEPTH_X = 0.46;

    private const DEPTH_Y = 0.3;

    public readonly string $uid;

    /**
     * The printed side, in its own coordinates from 0 0: the drawing places it
     * with transform().
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $face;

    /** Whether cards lie fanned underneath; never under a folded card (see the constructor). */
    public readonly bool $stack;

    public readonly float $tilt;

    /**
     * Corners of a folded card standing as a tent: the fold on top, the foot of
     * the front side and the foot of the back side, each from left to right.
     *
     * @var array{ridge: array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}, front: array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}, back: array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}}|null
     */
    public readonly ?array $tent;

    public function __construct(
        public readonly string $format = 'visitenkarte',
        public readonly string $motif = 'atelier',
        public readonly bool $fold = false,
        bool $stack = false,
        public readonly bool $gloss = false,
        public readonly string $finish = 'plain',
        public readonly ?string $label = null,
    ) {
        if (! isset(self::FORMATS[$format])) {
            throw new InvalidArgumentException("Unknown card format [{$format}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown card motif [{$motif}].");
        }

        if (! in_array($finish, self::FINISHES, true)) {
            throw new InvalidArgumentException("Unknown card finish [{$finish}].");
        }

        $this->uid = Uid::next('ca');

        // A folded card stands on its own: cards fanned under a tent would only
        // show as clutter at its feet. Only flat cards are drawn on a stack.
        $this->stack = $stack && ! $fold;

        $width = self::FORMATS[$format]['width'];
        $height = self::FORMATS[$format]['height'];

        if ($fold) {
            [$this->face, $this->tent] = self::standing($width, $height);
            $this->tilt = 0.0;
        } else {
            // The stack's fanned cards need room at the corners.
            $scale = $this->stack ? 0.9 : 1.0;
            $this->face = ['x' => 0.0, 'y' => 0.0, 'width' => round($width * $scale, 2), 'height' => round($height * $scale, 2)];
            $this->tent = null;
            $this->tilt = $this->stack ? -4.0 : -3.0;
        }
    }

    public static function label(): string
    {
        return 'Karte';
    }

    public static function keys(): array
    {
        return ['format' => array_keys(self::FORMATS), 'motif' => self::motifs(), 'fold' => null, 'stack' => null, 'gloss' => null, 'finish' => self::FINISHES];
    }

    public static function form(): array
    {
        return ['key' => 'format', 'label' => 'Format', 'values' => array_map(static fn (array $format): string => $format['label'], self::FORMATS)];
    }

    /**
     * Format from the product's name; finish, surface and fold from the names
     * of its options. A business card is asked for before the long format: a
     * "Klappvisitenkarte" is a business card, an "Einladungskarte DIN lang" is
     * long, and "Karte" on its own is drawn as a postcard.
     */
    public static function names(): array
    {
        return [
            'form' => [
                'visitenkarte' => 'visitenkarte|klappvisitenkarte|business',
                'lang' => 'din ?lang|lang',
                'quadrat' => 'quadrat',
                'postkarte' => 'postkarte|a ?6|klappkarte|gru[sß]karte|einladungskarte|karte',
            ],
            'options' => [
                // A film laminate ("Folienkaschierung") brings no finish of its own; whether it
                // shines is read from "matt" or "glänzend". The word mostly ends a longer one.
                'finish' => [
                    'softtouch' => 'soft-?touch|samt',
                    'gold' => 'gold',
                    'silver' => 'silber',
                    'plain' => 'ohne|[a-zäöüß]*kaschier',
                ],
            ],
            'switches' => [
                'gloss' => ['gl[aä]nz', 'matt'],
                'fold' => ['klapp|gefalzt|gefaltet', 'ungefalzt|ungefaltet'],
            ],
        ];
    }

    /**
     * @param  array{format?: string, motif?: string, fold?: bool, stack?: bool, gloss?: bool, finish?: string, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            format: $spec['format'] ?? 'visitenkarte',
            motif: $spec['motif'] ?? 'atelier',
            fold: (bool) ($spec['fold'] ?? false),
            stack: (bool) ($spec['stack'] ?? false),
            gloss: (bool) ($spec['gloss'] ?? false),
            finish: $spec['finish'] ?? 'plain',
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs drawn for cards.
     *
     * @return list<string>
     */
    public static function motifs(): array
    {
        return Template::names('motif/card');
    }

    /**
     * The SVG markup. A width class in $class (w-…, md:w-…) replaces the full-width default.
     */
    public function render(string $class = ''): string
    {
        return Template::render('mockup/card', ['mockup' => $this, 'class' => $class]);
    }

    public function describe(): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        $format = [
            'visitenkarte' => 'Visitenkarte',
            'postkarte' => 'Postkarte DIN A6',
            'quadrat' => 'Quadratische Karte',
            'lang' => 'Karte DIN lang',
        ][$this->format];

        $finish = [
            'plain' => '',
            'softtouch' => ' mit Soft-Touch-Folie',
            'gold' => ' mit Goldprägung',
            'silver' => ' mit Silberprägung',
        ][$this->finish];

        return ($this->fold ? 'Klappkarte, ' : '').$format.$finish.($this->stack ? ', Stapel' : '');
    }

    /** Whether light glances off the surface: soft-touch film swallows every shine. */
    public function shines(): bool
    {
        return $this->gloss && $this->finish !== 'softtouch';
    }

    /**
     * Colours of the hot foil from dark to light and back, as it catches the
     * light along a line; null without foil.
     *
     * @return list<string>|null
     */
    public function foil(): ?array
    {
        return match ($this->finish) {
            'gold' => ['#9a6b1f', '#d9ac4c', '#f7e3a1', '#b98a34', '#ecc977', '#8f621a'],
            'silver' => ['#676d74', '#b3b8bd', '#f1f3f4', '#878d94', '#cdd1d5', '#60666d'],
            default => null,
        };
    }

    /**
     * Where the printed side goes: turned slightly on the page when the card
     * lies flat, slanted into depth when it stands folded.
     */
    public function transform(): string
    {
        $width = $this->face['width'];
        $height = $this->face['height'];

        if ($this->tent === null) {
            return 'rotate('.self::number($this->tilt).' 60 60) translate('.self::number(60 - $width / 2).' '.self::number(60 - $height / 2).')';
        }

        [$left] = $this->tent['ridge'];
        [$foot] = $this->tent['front'];

        // The side's top edge stays as it is; its foot moves forward (down) and towards the viewer's left.
        return 'matrix(1 0 '.self::number(($foot[0] - $left[0]) / $height).' '.self::number(($foot[1] - $left[1]) / $height).' '.self::number($left[0]).' '.self::number($left[1]).')';
    }

    /**
     * Size and corners of a card folded along its top edge and standing open,
     * seen from the front and a little from above and the right. Scaled to fit
     * the box, centred in it.
     *
     * @return array{0: array{x: float, y: float, width: float, height: float}, 1: array{ridge: array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}, front: array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}, back: array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}}}
     */
    private static function standing(float $width, float $height): array
    {
        $angle = deg2rad(self::TENT_ANGLE);
        $rise = cos($angle);
        $reach = sin($angle);

        // How far the tent reaches for a side 1 unit high: across from the front foot's left end
        // to the back foot's right end, down from the fold to the front foot.
        $spanX = $width / $height + 2 * $reach * self::DEPTH_X;
        $spanY = $rise + $reach * self::DEPTH_Y;
        $side = min(100 / $spanX, 82 / $spanY);

        $panelWidth = $side * $width / $height;
        $shiftX = $side * $reach * self::DEPTH_X;
        $shiftY = $side * $reach * self::DEPTH_Y;
        $top = $side * $rise;

        $left = 60 - $panelWidth / 2;
        $ridge = 60 - ($top + $shiftY) / 2;

        $face = ['x' => 0.0, 'y' => 0.0, 'width' => round($panelWidth, 2), 'height' => round($side, 2)];

        $tent = [
            'ridge' => [[round($left, 2), round($ridge, 2)], [round($left + $panelWidth, 2), round($ridge, 2)]],
            'front' => [[round($left - $shiftX, 2), round($ridge + $top + $shiftY, 2)], [round($left + $panelWidth - $shiftX, 2), round($ridge + $top + $shiftY, 2)]],
            'back' => [[round($left + $shiftX, 2), round($ridge + $top - $shiftY, 2)], [round($left + $panelWidth + $shiftX, 2), round($ridge + $top - $shiftY, 2)]],
        ];

        return [$face, $tent];
    }

    /** A number for an SVG attribute: at most three decimals, no trailing zeros. */
    private static function number(float $value): string
    {
        $text = rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');

        return $text === '-0' ? '0' : $text;
    }
}
