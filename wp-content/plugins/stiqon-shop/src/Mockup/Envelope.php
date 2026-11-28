<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * An envelope drawn as inline SVG: the address side of a white envelope, the
 * sender's logo printed in its top left corner, with or without a window.
 *
 * The drawing lives in a 120 x 120 box. The logo is a sticker motif drawn
 * without its own background, so it looks printed on the paper. DIN lang and
 * C5 lie across, C4 stands upright like the large envelopes that take an
 * unfolded A4 sheet – C5 and C4 share a proportion and would otherwise look alike.
 */
final class Envelope implements Kind
{
    /** Sizes in the drawing: true to each format's proportion, each filling the box. */
    public const FORMATS = [
        'lang' => ['label' => 'DIN lang', 'width' => 97.0, 'height' => 48.5],
        'c5' => ['label' => 'DIN C5', 'width' => 92.0, 'height' => 65.1],
        'c4' => ['label' => 'DIN C4', 'width' => 67.0, 'height' => 94.8],
    ];

    /**
     * Where things sit on the envelope, as fractions of its width and height:
     * the window about where DIN 680 puts it (90 x 45 mm, 20 mm from the left
     * edge; on C5 and C4 a little lower, so the logo above it keeps its size),
     * the address where it goes without a window, and how far down the flap
     * on the back reaches. The logo's margin and size are fractions of the
     * shorter side.
     */
    private const LAYOUT = [
        'lang' => ['logo' => [0.09, 0.31], 'window' => [0.091, 0.455, 0.409, 0.409], 'address' => [0.5, 0.56], 'flap' => 0.46],
        'c5' => ['logo' => [0.08, 0.21], 'window' => [0.087, 0.33, 0.393, 0.278], 'address' => [0.5, 0.58], 'flap' => 0.4],
        'c4' => ['logo' => [0.075, 0.2], 'window' => [0.087, 0.215, 0.393, 0.139], 'address' => [0.4, 0.52], 'flap' => 0.26],
    ];

    public readonly string $uid;

    /** @var array{x: float, y: float, width: float, height: float} */
    public readonly array $sheet;

    public readonly float $tilt;

    public function __construct(
        public readonly string $format = 'lang',
        public readonly string $motif = 'aktion',
        public readonly bool $window = false,
        public readonly ?string $label = null,
    ) {
        if (! isset(self::FORMATS[$format])) {
            throw new InvalidArgumentException("Unknown envelope format [{$format}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown envelope motif [{$motif}].");
        }

        $this->uid = Uid::next('en');

        $width = self::FORMATS[$format]['width'];
        $height = self::FORMATS[$format]['height'];

        $this->sheet = [
            'x' => (120 - $width) / 2,
            'y' => (120 - $height) / 2,
            'width' => $width,
            'height' => $height,
        ];

        $this->tilt = -3.0;
    }

    public static function label(): string
    {
        return 'Briefumschlag';
    }

    public static function keys(): array
    {
        return ['format' => array_keys(self::FORMATS), 'motif' => self::motifs(), 'window' => null];
    }

    public static function form(): array
    {
        return ['key' => 'format', 'label' => 'Format', 'values' => array_map(static fn (array $format): string => $format['label'], self::FORMATS)];
    }

    /**
     * Format from the product's name, the window from the names of its
     * options: "mit Fenster" or "Fenster links" draws one, "ohne Fenster"
     * leaves it out.
     */
    public static function names(): array
    {
        return [
            'form' => [
                'lang' => 'din ?lang|lang|dl(?![a-zäöüß])',
                'c5' => 'c ?5',
                'c4' => 'c ?4',
            ],
            'options' => [],
            'switches' => [
                'window' => ['mit (sicht)?fenster|fenster links|fenster rechts|fenster(umschl|kuvert|briefh)', 'ohne (sicht)?fenster'],
            ],
        ];
    }

    /**
     * @param  array{format?: string, motif?: string, window?: bool, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            format: $spec['format'] ?? 'lang',
            motif: $spec['motif'] ?? 'aktion',
            window: (bool) ($spec['window'] ?? false),
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs: the sticker motifs, printed as the sender's logo.
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
        return Template::render('mockup/envelope', ['mockup' => $this, 'class' => $class]);
    }

    public function describe(): string
    {
        return $this->label ?? 'Briefumschlag '.self::FORMATS[$this->format]['label'].($this->window ? ' mit Fenster' : '');
    }

    /**
     * Whether the sample logo is drawn without its own background. The star's
     * lettering is white and needs its red ground: on white paper it would vanish.
     */
    public function bareMotif(): bool
    {
        return $this->motif !== 'stern';
    }

    /**
     * The sender's logo in the top left corner, in the envelope's own coordinates.
     *
     * @return array{x: float, y: float, width: float, height: float}
     */
    public function logo(): array
    {
        $side = min($this->sheet['width'], $this->sheet['height']);
        [$margin, $size] = self::LAYOUT[$this->format]['logo'];

        return Svg::box(round($margin * $side, 2), round($margin * $side, 2), round($size * $side, 2), round($size * $side, 2));
    }

    /**
     * The window, in the envelope's own coordinates; null without one.
     *
     * @return array{x: float, y: float, width: float, height: float}|null
     */
    public function windowBox(): ?array
    {
        if (! $this->window) {
            return null;
        }

        [$x, $y, $width, $height] = self::LAYOUT[$this->format]['window'];

        return Svg::box(
            round($x * $this->sheet['width'], 2),
            round($y * $this->sheet['height'], 2),
            round($width * $this->sheet['width'], 2),
            round($height * $this->sheet['height'], 2),
        );
    }

    /**
     * Lines of the address, as grey bars [x, y, length]: behind the window, or
     * in the lower right of the front without one. The first line is the name.
     *
     * @return list<array{0: float, 1: float, 2: float}>
     */
    public function address(): array
    {
        $window = $this->windowBox();

        if ($window !== null) {
            // The lines keep to the window, however low it is.
            $x = $window['x'] + $window['width'] * 0.1;
            $y = $window['y'] + $window['height'] * 0.3;
            $gap = min(3.4, $window['height'] * 0.2);
            $lengths = [0.62, 0.5, 0.56];
            $scale = $window['width'];
        } else {
            [$left, $top] = self::LAYOUT[$this->format]['address'];
            $x = $left * $this->sheet['width'];
            $y = $top * $this->sheet['height'];
            $gap = 3.4;
            $lengths = $this->format === 'c4' ? [0.46, 0.36, 0.4] : [0.32, 0.25, 0.28];
            $scale = $this->sheet['width'];
        }

        $lines = [];

        foreach ($lengths as $index => $length) {
            // The town stands apart from the street by a little more.
            $lines[] = [round($x, 2), round($y + $index * $gap + ($index === 2 ? $gap * 0.35 : 0), 2), round($length * $scale, 2)];
        }

        return $lines;
    }

    /**
     * Outline of the flap on the back, which shows faintly through the paper:
     * from the two top corners to a point, rounded off along both edges. A
     * pointed flap says "envelope" at a glance, whatever the format. In the
     * envelope's own coordinates.
     */
    public function flap(): string
    {
        $width = $this->sheet['width'];
        $middle = $width / 2;
        $depth = self::LAYOUT[$this->format]['flap'] * $this->sheet['height'];
        $edge = hypot($middle, $depth);
        $alongX = 3.2 * $middle / $edge;
        $alongY = 3.2 * $depth / $edge;

        return 'M0.5 0.5'
            .'L'.round($middle - $alongX, 2).' '.round($depth - $alongY, 2)
            .'Q'.round($middle, 2).' '.round($depth, 2).' '.round($middle + $alongX, 2).' '.round($depth - $alongY, 2)
            .'L'.round($width - 0.5, 2).' 0.5z';
    }
}
