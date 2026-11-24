<?php

namespace App\View\Components\Mockup;

use App\Support\Uid;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;

/**
 * A flyer drawn as inline SVG: one sheet, optionally on a small stack.
 *
 * The drawing lives in a 120 x 150 box. Sample motifs are designed for the
 * DIN A proportion (1 : 1.414) and are cropped from the centre for the long
 * and the square format.
 */
class Flyer extends Component
{
    public const FORMATS = [
        'a6' => ['label' => 'DIN A6', 'width' => 74.0, 'height' => 104.6],
        'a5' => ['label' => 'DIN A5', 'width' => 74.0, 'height' => 104.6],
        'a4' => ['label' => 'DIN A4', 'width' => 74.0, 'height' => 104.6],
        'lang' => ['label' => 'DIN lang', 'width' => 52.0, 'height' => 104.0],
        'quadrat' => ['label' => 'Quadrat', 'width' => 86.0, 'height' => 86.0],
    ];

    public string $uid;

    /** @var array{x: float, y: float, width: float, height: float} */
    public array $sheet;

    public float $tilt;

    public function __construct(
        public string $format = 'a6',
        public string $motif = 'fest',
        public bool $stack = false,
        public bool $heavy = false,
        public bool $gloss = false,
        public ?string $label = null,
    ) {
        if (! isset(self::FORMATS[$format])) {
            throw new InvalidArgumentException("Unknown flyer format [{$format}].");
        }

        $this->uid = Uid::next('fl');

        $width = self::FORMATS[$format]['width'];
        $height = self::FORMATS[$format]['height'];

        $this->sheet = [
            'x' => (120 - $width) / 2,
            'y' => (150 - $height) / 2,
            'width' => $width,
            'height' => $height,
        ];

        $this->tilt = $stack ? -4.0 : -2.0;
    }

    public function render(): View
    {
        return view('components.mockup.flyer');
    }

    public function describe(): string
    {
        return $this->label ?? 'Flyer '.self::FORMATS[$this->format]['label'].($this->stack ? ', Stapel' : '');
    }
}
