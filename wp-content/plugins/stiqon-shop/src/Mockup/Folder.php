<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A presentation folder drawn as inline SVG: closed, with the motif on its
 * cover and a sheet peeking out, or open, with the pocket on the inside of
 * the back cover.
 *
 * The drawing lives in a 120 x 120 box. A folder of 220 x 310 mm takes A4
 * sheets; the flyer motifs (DIN A proportion) fill its cover. Printed on one
 * side, the folder carries its print on the outside of the cover and on the
 * pocket – folded inwards, the pocket turns its printed side up.
 */
final class Folder implements Kind
{
    public const FORMATS = [
        'a4' => ['label' => 'DIN A4'],
    ];

    /** The folder's width per height: 220 x 310 mm. */
    private const PROPORTION = 220 / 310;

    public readonly string $uid;

    /**
     * The front cover of the closed folder, or the left half of the open one.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $panel;

    /** The gap between the two halves of the open folder: its spine. */
    public readonly float $spine;

    public readonly float $tilt;

    public function __construct(
        public readonly string $format = 'a4',
        public readonly string $motif = 'eroeffnung',
        public readonly bool $open = false,
        public readonly bool $gloss = false,
        public readonly ?string $label = null,
    ) {
        if (! isset(self::FORMATS[$format])) {
            throw new InvalidArgumentException("Unknown folder format [{$format}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown folder motif [{$motif}].");
        }

        $this->uid = Uid::next('fo');
        $this->spine = $open ? 1.6 : 0.0;

        // Closed, the cover stands upright with a sheet peeking out to its right;
        // open, both halves lie side by side. Either sits a little above the
        // middle: the shadow falls below and needs the room.
        $height = $open ? 70.0 : 94.0;
        $width = round($height * self::PROPORTION, 2);
        $across = $open ? 2 * $width + $this->spine : $width + 5.0;

        $this->panel = [
            'x' => round((120 - $across) / 2, 2),
            'y' => $open ? 21.5 : 10.0,
            'width' => $width,
            'height' => $height,
        ];

        $this->tilt = $open ? -2.0 : -3.0;
    }

    public static function label(): string
    {
        return 'Präsentationsmappe';
    }

    public static function keys(): array
    {
        return ['format' => array_keys(self::FORMATS), 'motif' => self::motifs(), 'open' => null, 'gloss' => null];
    }

    public static function form(): array
    {
        return ['key' => 'format', 'label' => 'Format', 'values' => array_map(static fn (array $format): string => $format['label'], self::FORMATS)];
    }

    /**
     * Every folder takes A4 sheets, so a name that says "Mappe" is enough for
     * the format. Whether the cover shines follows from its finish: a gloss
     * laminate or cellophane does, a matt laminate or none does not.
     */
    public static function names(): array
    {
        return [
            'form' => [
                'a4' => 'a ?4|[a-zäöüß]*mappe',
            ],
            'options' => [],
            'switches' => ['gloss' => ['gl[aä]nz|cellophan', 'matt|ohne']],
        ];
    }

    /**
     * @param  array{format?: string, motif?: string, open?: bool, gloss?: bool, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            format: $spec['format'] ?? 'a4',
            motif: $spec['motif'] ?? 'eroeffnung',
            open: (bool) ($spec['open'] ?? false),
            gloss: (bool) ($spec['gloss'] ?? false),
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs, drawn on the cover: the flyer motifs.
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
        return Template::render('mockup/folder', ['mockup' => $this, 'class' => $class]);
    }

    public function describe(): string
    {
        return $this->label ?? 'Präsentationsmappe '.self::FORMATS[$this->format]['label'].($this->open ? ', aufgeklappt' : '').($this->gloss ? ', glänzend' : '');
    }
}
