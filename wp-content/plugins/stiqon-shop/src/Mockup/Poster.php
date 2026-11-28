<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A poster drawn as inline SVG: one sheet hanging from two black binder clips
 * on a thin string, stretched between two pins.
 *
 * The drawing lives in a 120 x 120 box. Every DIN A format has the proportion
 * of the flyer motifs (1 : 1.414), so they fill the sheet without losing an
 * edge. The clips, the string and its pins keep their size in every format:
 * next to them a small poster looks small.
 */
final class Poster implements Kind
{
    /**
     * Heights of the sheet in the drawing's box. Each format has twice the
     * area of the one before; true to scale, A3 would be a stamp beside A0. So
     * each step is drawn about an eighth larger: A0 nearly fills the card, and
     * A3 is clearly smaller and still a poster.
     */
    public const FORMATS = [
        'a3' => ['label' => 'DIN A3', 'height' => 62.0],
        'a2' => ['label' => 'DIN A2', 'height' => 71.0],
        'a1' => ['label' => 'DIN A1', 'height' => 80.0],
        'a0' => ['label' => 'DIN A0', 'height' => 89.0],
    ];

    /** How far the string runs above the sheet where it passes through the clips' handles. */
    private const HANDLE = 6.0;

    /** How far the string sags from its pins to the clips under the poster's weight. */
    private const SAG = 2.6;

    public readonly string $uid;

    /** @var array{x: float, y: float, width: float, height: float} */
    public readonly array $sheet;

    /**
     * Centres of the two clips on the sheet's top edge.
     *
     * @var array{0: float, 1: float}
     */
    public readonly array $clips;

    /**
     * Where the string is fastened: the left and the right pin.
     *
     * @var array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}
     */
    public readonly array $pins;

    /** Height of the string where it runs through the clips. */
    public readonly float $line;

    public readonly float $tilt;

    public function __construct(
        public readonly string $format = 'a3',
        public readonly string $motif = 'eroeffnung',
        public readonly bool $gloss = false,
        public readonly ?string $label = null,
    ) {
        if (! isset(self::FORMATS[$format])) {
            throw new InvalidArgumentException("Unknown poster format [{$format}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown poster motif [{$motif}].");
        }

        $this->uid = Uid::next('po');

        $height = self::FORMATS[$format]['height'];
        $width = round($height / M_SQRT2, 2);

        // Pins, string and sheet are centred as one, a little above the middle:
        // the shadow falls below and needs the room.
        $top = round(58.0 - $height / 2 + (self::HANDLE + self::SAG) / 2, 2);

        $this->sheet = ['x' => round(60 - $width / 2, 2), 'y' => $top, 'width' => $width, 'height' => $height];

        // Clips sit close to the corners, as a person would put them.
        $inset = max(4.6, round($width * 0.12, 2));
        $this->clips = [round(60 - $width / 2 + $inset, 2), round(60 + $width / 2 - $inset, 2)];

        // The string spans the card in every format, so it is the same line a
        // small and a large poster hang from.
        $this->line = round($top - self::HANDLE, 2);
        $pin = round($this->line - self::SAG, 2);
        $this->pins = [[11.0, $pin], [109.0, $pin]];

        // The string's two pins are rarely level; the poster follows them.
        $this->tilt = -2.0;
    }

    public static function label(): string
    {
        return 'Plakat';
    }

    public static function keys(): array
    {
        return ['format' => array_keys(self::FORMATS), 'motif' => self::motifs(), 'gloss' => null];
    }

    public static function form(): array
    {
        return ['key' => 'format', 'label' => 'Format', 'values' => array_map(static fn (array $format): string => $format['label'], self::FORMATS)];
    }

    /**
     * Format from the product's name or from a chosen option ("DIN A1"). A
     * City-Light-Poster is larger than A0 and is drawn as the largest format.
     * Photo paper and gloss show as a band of light, matt paper does not.
     */
    public static function names(): array
    {
        $formats = [
            'a3' => 'a ?3',
            'a2' => 'a ?2',
            'a1' => 'a ?1',
            'a0' => 'a ?0|city[ -]?light',
        ];

        return [
            'form' => $formats,
            'options' => ['format' => $formats],
            'switches' => ['gloss' => ['gl[aä]nz|foto', 'matt']],
        ];
    }

    /**
     * @param  array{format?: string, motif?: string, gloss?: bool, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            format: $spec['format'] ?? 'a3',
            motif: $spec['motif'] ?? 'eroeffnung',
            gloss: (bool) ($spec['gloss'] ?? false),
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs drawn on posters: the flyer motifs, made for
     * the DIN A proportion.
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
        return Template::render('mockup/poster', ['mockup' => $this, 'class' => $class]);
    }

    public function describe(): string
    {
        return $this->label ?? 'Plakat '.self::FORMATS[$this->format]['label'].($this->gloss ? ', glänzend' : '');
    }
}
