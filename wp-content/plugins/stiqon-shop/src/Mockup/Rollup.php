<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A roll-up drawn as inline SVG: the printed banner standing up out of its
 * cassette, clamped at the top by a thin rail, the pole behind it.
 *
 * The drawing lives in a 120 x 120 box. Every format is 200 cm tall, so the
 * banner keeps its height and only its width changes, to scale. The sample
 * motifs are the flyer motifs (DIN A proportion), cropped from the centre: a
 * roll-up is narrower than a sheet of paper.
 */
final class Rollup implements Kind
{
    /** Widths in cm; every banner is 200 cm tall. */
    public const FORMATS = [
        '85x200' => ['label' => '85 × 200 cm', 'width' => 85],
        '100x200' => ['label' => '100 × 200 cm', 'width' => 100],
        '120x200' => ['label' => '120 × 200 cm', 'width' => 120],
    ];

    /** The cassettes by their German names: a plain silver box on two flat feet, or a wider, rounded one in chrome. */
    public const MODELS = ['basic' => 'Basic', 'premium' => 'Premium'];

    /** Height of every format in cm. */
    private const HEIGHT = 200;

    /** Drawing units per centimetre: the banner's 200 cm fill most of the card's height. */
    private const SCALE = 0.47;

    /** How far each cassette reaches past the banner on either side, and how tall it is drawn. */
    private const CASSETTES = [
        'basic' => ['overhang' => 3.2, 'height' => 7.0],
        'premium' => ['overhang' => 6.6, 'height' => 9.6],
    ];

    public readonly string $uid;

    /**
     * The printed banner, from under the rail down into the cassette.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $banner;

    /** @var array{x: float, y: float, width: float, height: float} */
    public readonly array $rail;

    /** @var array{x: float, y: float, width: float, height: float} */
    public readonly array $cassette;

    public function __construct(
        public readonly string $format = '85x200',
        public readonly string $motif = 'eroeffnung',
        public readonly string $model = 'basic',
        public readonly ?string $label = null,
    ) {
        if (! isset(self::FORMATS[$format])) {
            throw new InvalidArgumentException("Unknown rollup format [{$format}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown rollup motif [{$motif}].");
        }

        if (! isset(self::MODELS[$model])) {
            throw new InvalidArgumentException("Unknown rollup model [{$model}].");
        }

        $this->uid = Uid::next('ru');

        $width = round(self::FORMATS[$format]['width'] * self::SCALE, 2);
        $height = round(self::HEIGHT * self::SCALE, 2);
        $x = round(60 - $width / 2, 2);
        $top = 8.8;

        $this->banner = Svg::box($x, $top, $width, $height);

        // The rail clamps the banner's top edge and is a little wider than it.
        $this->rail = Svg::box(round($x - 0.8, 2), round($top - 1.4, 2), round($width + 1.6, 2), 2.6);

        // The banner disappears into the cassette's slot, which hides its last centimetres.
        $cassette = self::CASSETTES[$model];
        $this->cassette = Svg::box(
            round($x - $cassette['overhang'], 2),
            round($top + $height - 1.6, 2),
            round($width + 2 * $cassette['overhang'], 2),
            $cassette['height'],
        );
    }

    public static function label(): string
    {
        return 'Roll-up';
    }

    public static function keys(): array
    {
        return ['format' => array_keys(self::FORMATS), 'motif' => self::motifs(), 'model' => array_keys(self::MODELS)];
    }

    public static function form(): array
    {
        return ['key' => 'format', 'label' => 'Format', 'values' => array_map(static fn (array $format): string => $format['label'], self::FORMATS)];
    }

    /**
     * Format from the product's name or from a chosen option ("100 × 200 cm"),
     * the cassette from an option: suppliers call the plain one "Basic",
     * "Classic" or "Standard".
     */
    public static function names(): array
    {
        $formats = [
            '85x200' => '85 ?[x×] ?200',
            '100x200' => '100 ?[x×] ?200',
            '120x200' => '120 ?[x×] ?200',
        ];

        return [
            'form' => $formats,
            'options' => [
                'format' => $formats,
                'model' => ['premium' => 'premium', 'basic' => 'basic|classic|standard'],
            ],
            'switches' => [],
        ];
    }

    /**
     * @param  array{format?: string, motif?: string, model?: string, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            format: $spec['format'] ?? '85x200',
            motif: $spec['motif'] ?? 'eroeffnung',
            model: $spec['model'] ?? 'basic',
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs drawn on roll-ups: the flyer motifs, made for
     * the DIN A proportion and cropped to the banner's.
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
        return Template::render('mockup/rollup', ['mockup' => $this, 'class' => $class]);
    }

    public function describe(): string
    {
        return $this->label ?? 'Roll-up '.self::FORMATS[$this->format]['label'].($this->model === 'premium' ? ', Modell Premium' : '');
    }
}
