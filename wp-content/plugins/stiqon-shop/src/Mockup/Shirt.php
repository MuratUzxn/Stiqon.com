<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;
use Stiqon\Shop\Template;

/**
 * A T-shirt or a hoodie drawn as inline SVG: laid out flat and seen from
 * above, as in a product photo, a little askew, with soft folds in the cloth
 * and a print on the chest.
 *
 * The drawing lives in a 120 x 120 box. Each cut is measured in centimetres,
 * as a size M lies flat, and drawn at a scale of its own (see CUTS): the
 * hoodie's long sleeves and its hood take more room than the T-shirt's short
 * sleeves, so its centimetre is drawn smaller and both fill the card about
 * the same. The sleeves lie a little differently on either side, as when
 * laid out by hand.
 */
final class Shirt implements Kind
{
    /**
     * The cuts with their German names, how many drawing units one
     * centimetre of each is drawn as, and where the line through the top of
     * its shoulders lies in the drawing – the hood rises above it.
     */
    public const CUTS = [
        'tshirt' => ['label' => 'T-Shirt', 'scale' => 1.2, 'top' => 14.0],
        'hoodie' => ['label' => 'Hoodie', 'scale' => 1.06, 'top' => 25.5],
    ];

    public const COLORS = ['white', 'black', 'navy', 'grey'];

    /** The colours by their German names, for the description. */
    public const COLOR_NAMES = ['white' => 'Weiß', 'black' => 'Schwarz', 'navy' => 'Navy', 'grey' => 'Grau meliert'];

    /** Cloth that would swallow dark ink. */
    public const DARK_COLORS = ['black', 'navy'];

    /** A garment laid out by hand is rarely quite straight. */
    public const TILT = -2.0;

    public readonly string $uid;

    /**
     * The body without sleeves and hood: as wide as the chest, from the line
     * through the top of the shoulders down to the hem.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $body;

    /** Outline of the garment lying flat: body and sleeves, without the hood. */
    public readonly string $outline;

    /**
     * The garment's parts as path data, by name. A T-shirt has "inside" (the
     * back, seen from inside through the neck), "backCollar", "collar",
     * "collarRib", "collarSeam", "armholes" and "hems" (their stitching). A
     * hoodie has "hood", "opening" (the inside of the hood, down to where its
     * front edges cross), "edges" (the two front edges, the left one last),
     * "eyelets", "strings" and "tips" (one each per drawstring), "pocket",
     * "pocketSeams", "pocketShade" (along the hand openings), "cuffs",
     * "band", "ribSeams" and "armholes". The ribbed bands – "collarRib",
     * "cuffs", "band" – are their middle line ("d") and how wide they are
     * ("width"): drawn as a dashed stroke that wide, the dashes are the ribs.
     *
     * @var array<string, mixed>
     */
    public readonly array $parts;

    /**
     * Soft folds in the cloth, each an ellipse turned into place.
     *
     * @var list<array{x: float, y: float, rx: float, ry: float, angle: float}>
     */
    public readonly array $folds;

    /**
     * Where the motif is printed: a square centred on the chest – on the
     * hoodie between the ends of the drawstrings and the kangaroo pocket.
     *
     * @var array{x: float, y: float, width: float, height: float}
     */
    public readonly array $printBox;

    /**
     * The hoodie's kangaroo pocket, as the box round it; null for a T-shirt.
     *
     * @var array{x: float, y: float, width: float, height: float}|null
     */
    public readonly ?array $pocket;

    public function __construct(
        public readonly string $cut = 'tshirt',
        public readonly string $motif = 'aktion',
        public readonly string $color = 'white',
        public readonly ?string $label = null,
    ) {
        if (! isset(self::CUTS[$cut])) {
            throw new InvalidArgumentException("Unknown shirt cut [{$cut}].");
        }

        if (! in_array($motif, self::motifs(), true)) {
            throw new InvalidArgumentException("Unknown shirt motif [{$motif}].");
        }

        if (! in_array($color, self::COLORS, true)) {
            throw new InvalidArgumentException("Unknown shirt color [{$color}].");
        }

        $this->uid = Uid::next('sh');

        $laid = $cut === 'hoodie' ? $this->hoodie() : $this->tshirt();

        $this->body = $this->box(-$laid['chest'] / 2, 0.0, $laid['chest'], $laid['length']);
        $this->outline = $laid['outline'];
        $this->parts = $laid['parts'];
        $this->folds = array_map(fn (array $fold): array => $this->fold(...$fold), $laid['folds']);
        $this->printBox = $this->box(...$laid['print']);
        $this->pocket = $laid['pocket'] !== null ? $this->box(...$laid['pocket']) : null;
    }

    public static function label(): string
    {
        return 'Textil';
    }

    public static function keys(): array
    {
        return ['cut' => array_keys(self::CUTS), 'motif' => self::motifs(), 'color' => self::COLORS];
    }

    public static function form(): array
    {
        return ['key' => 'cut', 'label' => 'Schnitt', 'values' => array_map(static fn (array $cut): string => $cut['label'], self::CUTS)];
    }

    /**
     * Cut from the product's name, colour from the chosen option. The hoodie
     * is asked first: a "Sweatshirt" is drawn as one, and so is a
     * "Kapuzenpullover". Sizes (S, M, XL …) change nothing in the drawing.
     */
    public static function names(): array
    {
        return [
            'form' => [
                'hoodie' => 'hoodie|kapuze|kapuzen|sweat|pullover',
                'tshirt' => 't-?shirt|shirt|polo',
            ],
            'options' => [
                'color' => [
                    'white' => 'wei[sß]|white',
                    'black' => 'schwarz|black',
                    'navy' => 'navy|marine|dunkelblau|nachtblau',
                    'grey' => 'grau|meliert|grey|gray|heather',
                ],
            ],
            'switches' => [],
        ];
    }

    /**
     * @param  array{cut?: string, motif?: string, color?: string, label?: ?string}  $spec
     */
    public static function fromArray(array $spec): self
    {
        return new self(
            cut: $spec['cut'] ?? 'tshirt',
            motif: $spec['motif'] ?? 'aktion',
            color: $spec['color'] ?? 'white',
            label: $spec['label'] ?? null,
        );
    }

    /**
     * Names of the sample motifs printed on shirts: the sticker motifs, as a chest print.
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
        return Template::render('mockup/shirt', ['mockup' => $this, 'class' => $class]);
    }

    /**
     * How the sample motif is printed: straight onto the cloth, without a
     * ground of its own – in dark ink on white and grey cloth, in light ink on
     * black and navy, which would swallow dark ink (see $ink in the motif
     * templates).
     *
     * @return array{bare: true, ink: 'dark'|'light'}
     */
    public function motifVars(): array
    {
        return ['bare' => true, 'ink' => in_array($this->color, self::DARK_COLORS, true) ? 'light' : 'dark'];
    }

    public function describe(): string
    {
        return $this->label ?? self::CUTS[$this->cut]['label'].', '.self::COLOR_NAMES[$this->color];
    }

    /** A length of the garment, given in centimetres, in the drawing's units. */
    public function cm(float $length): float
    {
        return round($length * self::CUTS[$this->cut]['scale'], 2);
    }

    /**
     * A T-shirt with a crew neck and short sleeves: 52 cm across the chest,
     * 72 cm long. Through the neck the inside of the back shows, below the
     * back of the collar.
     *
     * @return array{chest: float, length: float, outline: string, parts: array<string, mixed>, folds: list<list<float>>, print: list<float>, pocket: null}
     */
    private function tshirt(): array
    {
        $chest = 52.0;
        $length = 72.0;
        $neck = 9.0;
        $drop = 9.0;
        $rib = 1.8;
        $shoulder = [23.0, 4.5];

        /*
         * One half from the middle of the back collar round to the middle of the
         * hem: the collar, the shoulder, the sleeve's top, its opening and its
         * underside, the side and the hem. $angle is how steeply the sleeve lies
         * below the horizontal.
         */
        $half = static function (float $angle) use ($chest, $length, $neck, $shoulder): array {
            $sleeve = 20.0;
            $opening = 17.0;
            $along = [cos(deg2rad($angle)), sin(deg2rad($angle))];
            $end = [$shoulder[0] + $sleeve * $along[0], $shoulder[1] + $sleeve * $along[1]];
            $under = [$end[0] - $opening * $along[1], $end[1] + $opening * $along[0]];
            // The underside of the sleeve runs back, parallel to its top, into the armpit.
            $armpit = [$chest / 2, $under[1] - ($under[0] - $chest / 2) * $along[1] / $along[0]];
            $hem = [$chest / 2 + 0.3, $length];

            return [
                'chain' => [
                    [0.0, 1.4],
                    [[4.5, 1.4], [7.6, 0.6], [$neck, 0.0]],
                    [[13.5, 1.1], [18.5, 2.7], $shoulder],
                    [self::between($shoulder, $end, 0.35, -0.5), self::between($shoulder, $end, 0.7, -0.4), $end],
                    [self::between($end, $under, 0.33, 0.25), self::between($end, $under, 0.67, 0.25), $under],
                    [self::between($under, $armpit, 0.4, 0.2), self::between($under, $armpit, 0.75, 0.6), $armpit],
                    [[$armpit[0] - 0.6, $armpit[1] + 12.0], [$hem[0] - 0.5, $length - 16.0], $hem],
                    [[$hem[0] - 9.0, $length + 0.25], [9.0, $length + 0.55], [0.0, $length + 0.4]],
                ],
                'shoulder' => $shoulder,
                'end' => $end,
                'under' => $under,
                'armpit' => $armpit,
                'along' => $along,
            ];
        };

        $sides = [[$half(31.0), 1.0], [$half(34.0), -1.0]];
        $outline = 'M'.$this->chain($sides[0][0]['chain']).$this->chain(self::mirrored($sides[1][0]['chain']), false).'z';

        // The collar: a band of rib knit round the neck. In front its outer edge
        // is the neckline seam, 9 cm below the shoulders; the back of the collar
        // is seen from inside, above the inside of the back.
        $inner = $neck - $rib + 0.2;
        $collarOuter = [[-$neck, 0.0], [[-$neck - 0.2, 5.2], [-5.6, $drop], [0.0, $drop]], [[5.6, $drop], [$neck + 0.2, 5.2], [$neck, 0.0]]];
        $collarInner = [[$inner, 0.6], [[$inner + 0.1, 4.4], [4.4, $drop - $rib], [0.0, $drop - $rib]], [[-4.4, $drop - $rib], [-$inner - 0.1, 4.4], [-$inner, 0.6]]];
        $collarMiddle = [[-$neck + $rib / 2 - 0.1, 0.3], [[-$neck + 0.8, 4.8], [-5.0, $drop - $rib / 2], [0.0, $drop - $rib / 2]], [[5.0, $drop - $rib / 2], [$neck - 0.8, 4.8], [$neck - $rib / 2 + 0.1, 0.3]]];
        $backTop = [[$neck, 0.0], [[7.6, 0.6], [4.5, 1.4], [0.0, 1.4]], [[-4.5, 1.4], [-7.6, 0.6], [-$neck, 0.0]]];
        $backBottom = [[-$inner, 0.6], [[-4.6, 3.2], [-2.6, 3.2], [0.0, 3.2]], [[2.6, 3.2], [4.6, 3.2], [$inner, 0.6]]];
        $seam = [[-$neck - 0.55, 0.15], [[-$neck - 0.7, 5.6], [-5.9, $drop + 0.55], [0.0, $drop + 0.55]], [[5.9, $drop + 0.55], [$neck + 0.7, 5.6], [$neck + 0.55, 0.15]]];

        // The sleeves are hemmed 2 cm in from their openings, the shirt 2 cm above its hem.
        $hems = 'M'.$this->at(-$chest / 2 + 0.3, $length - 2.0).'C'.$this->at(-9.0, $length - 1.6).' '.$this->at(9.0, $length - 1.5).' '.$this->at($chest / 2, $length - 1.85);
        $armholes = '';

        foreach ($sides as [$side, $sign]) {
            [$alongX, $alongY] = $side['along'];
            $hems .= 'M'.$this->at($sign * ($side['end'][0] - 2.0 * $alongX), $side['end'][1] - 2.0 * $alongY)
                .'L'.$this->at($sign * ($side['under'][0] - 2.0 * $alongX), $side['under'][1] - 2.0 * $alongY);

            [$sx, $sy] = $side['shoulder'];
            [$ax, $ay] = $side['armpit'];
            $armholes .= 'M'.$this->at($sign * $sx, $sy).'C'.$this->at($sign * ($sx - 0.9), $sy + 8.0).' '.$this->at($sign * ($ax - 1.4), $ay - 6.0).' '.$this->at($sign * $ax, $ay);
        }

        // A third of the shirt's width across the sleeves, 5 cm below the collar.
        $print = 27.0;

        return [
            'chest' => $chest,
            'length' => $length,
            'outline' => $outline,
            'parts' => [
                'inside' => 'M'.$this->chain($backBottom).$this->chain($collarInner, false).'z',
                'backCollar' => 'M'.$this->chain($backTop).'L'.$this->at(-$inner, 0.6).$this->chain($backBottom, false).'z',
                'collar' => 'M'.$this->chain($collarOuter).'L'.$this->at($inner, 0.6).$this->chain($collarInner, false).'z',
                'collarRib' => ['d' => 'M'.$this->chain($collarMiddle), 'width' => $this->cm($rib - 0.25)],
                'collarSeam' => 'M'.$this->chain($seam),
                'armholes' => $armholes,
                'hems' => $hems,
            ],
            // Where the cloth was smoothed out by hand: drag lines from the armpits,
            // broad waves across the body and one along each sleeve – middle,
            // length and breadth in centimetres, and the angle.
            'folds' => [
                [-13.0, 31.0, 24.0, 2.4, 36.0],
                [14.0, 30.0, 22.0, 2.2, -38.0],
                [-6.0, 48.0, 34.0, 3.6, -16.0],
                [8.0, 60.0, 38.0, 4.2, 10.0],
                [31.0, 15.5, 12.0, 2.4, 31.0],
                [-31.0, 16.0, 12.0, 2.4, -34.0],
            ],
            'print' => [-$print / 2, $drop + 5.0, $print, $print],
            'pocket' => null,
        ];
    }

    /**
     * A hoodie: 56 cm across the chest, 70 cm long with its ribbed hem band.
     * The shoulders are dropped, the long sleeves lie along the body with a
     * soft bend at the elbow and end in ribbed cuffs. The hood lies behind the
     * neck with its opening towards the viewer; the drawstrings hang from two
     * eyelets, and the kangaroo pocket sits above the hem band.
     *
     * @return array{chest: float, length: float, outline: string, parts: array<string, mixed>, folds: list<list<float>>, print: list<float>, pocket: list<float>}
     */
    private function hoodie(): array
    {
        $chest = 56.0;
        $length = 70.0;
        $band = 6.5;
        $seamY = $length - $band;
        $neck = 10.5;
        $shoulder = [28.5, 5.0];

        /*
         * One half from the middle of the hood's seam at the back of the neck
         * round to the middle of the hem: the shoulder, the sleeve's outer edge
         * bending at the elbow, the cuff, the sleeve's inner edge up to the
         * armpit, the side and the hem band. $reach moves the forearm out or in.
         */
        $half = static function (float $reach) use ($chest, $length, $seamY, $neck, $shoulder): array {
            $elbow = [41.0 + $reach, 26.0];
            $wrist = [44.0 + $reach, 55.0];
            $wristIn = [34.3 + $reach * 0.8, 56.4];
            $cuffOut = [44.6 + $reach, 62.0];
            $cuffIn = [35.4 + $reach * 0.8, 63.2];
            $armpit = [$chest / 2, 27.5];

            return [
                'chain' => [
                    [0.0, 0.2],
                    [[4.0, 0.2], [8.0, 0.0], [$neck, 0.0]],
                    [[16.0, 0.6], [23.0, 2.2], $shoulder],
                    [[34.0, 7.5], [39.0, 14.0], $elbow],
                    [[42.6 + $reach, 36.0], [43.6 + $reach, 46.0], $wrist],
                    [[44.4 + $reach, 57.0], [44.7 + $reach, 60.0], $cuffOut],
                    [[41.6 + $reach, 62.8], [38.4 + $reach, 63.4], $cuffIn],
                    [[35.1 + $reach * 0.8, 61.0], [34.6 + $reach * 0.8, 58.5], $wristIn],
                    [[32.2, 45.0], [29.6, 33.0], $armpit],
                    [[$chest / 2 + 0.3, 40.0], [$chest / 2 + 0.4, 55.0], [$chest / 2 - 0.2, $seamY - 1.0]],
                    [[$chest / 2 - 0.6, $seamY - 0.3], [$chest / 2 - 1.4, $seamY], [$chest / 2 - 2.0, $seamY]],
                    [[$chest / 2 - 2.1, $seamY + 2.0], [$chest / 2 - 2.3, $length - 2.0], [$chest / 2 - 2.4, $length]],
                    [[15.0, $length + 0.2], [7.0, $length + 0.3], [0.0, $length + 0.3]],
                ],
                'wrist' => $wrist,
                'wristIn' => $wristIn,
                'cuffOut' => $cuffOut,
                'cuffIn' => $cuffIn,
                'armpit' => $armpit,
                'shoulder' => $shoulder,
            ];
        };

        $sides = [[$half(0.0), 1.0], [$half(-0.8), -1.0]];
        $outline = 'M'.$this->chain($sides[0][0]['chain']).$this->chain(self::mirrored($sides[1][0]['chain']), false).'z';

        // The hood lies flat behind the neck; its opening faces the viewer,
        // framed by the doubled front edge with the drawstring inside. The two
        // front edges meet and cross in front of the neck.
        $hood = 'M'.$this->at(-17.0, 3.0)
            .'C'.$this->at(-19.4, -6.5).' '.$this->at(-11.2, -13.2).' '.$this->at(0.0, -13.2)
            .'C'.$this->at(11.2, -13.2).' '.$this->at(19.4, -6.5).' '.$this->at(17.0, 3.0).'z';
        $opening = 'M'.$this->at(0.0, 8.4)
            .'C'.$this->at(-4.6, 5.2).' '.$this->at(-12.0, 1.4).' '.$this->at(-12.0, -3.4)
            .'C'.$this->at(-12.0, -7.8).' '.$this->at(-6.4, -9.6).' '.$this->at(0.0, -9.6)
            .'C'.$this->at(6.4, -9.6).' '.$this->at(12.0, -7.8).' '.$this->at(12.0, -3.4)
            .'C'.$this->at(12.0, 1.4).' '.$this->at(4.6, 5.2).' '.$this->at(0.0, 8.4).'z';
        $edges = [
            [[-1.4, 9.4], [[4.6, 6.0], [13.4, 2.0], [13.4, -3.6]], [[13.4, -8.9], [7.0, -10.9], [0.0, -10.9]]],
            [[1.4, 9.4], [[-4.6, 6.0], [-13.4, 2.0], [-13.4, -3.6]], [[-13.4, -8.9], [-7.0, -10.9], [0.0, -10.9]]],
        ];

        // The drawstrings come out of two eyelets and fall a little outwards,
        // ending above the print; their tips are the last 2 cm.
        $eyelets = '';

        foreach ([-4.4, 4.4] as $eyelet) {
            [$ex, $ey] = $this->point($eyelet, 6.0);
            $radius = $this->cm(0.6);
            $eyelets .= 'M'.round($ex - $radius, 2).' '.$ey.'a'.$radius.' '.$radius.' 0 1 0 '.round(2 * $radius, 2).' 0a'.$radius.' '.$radius.' 0 1 0 '.round(-2 * $radius, 2).' 0z';
        }

        $strings = [
            [[-4.4, 6.0], [[-4.7, 9.6], [-6.3, 13.4], [-7.9, 16.4]]],
            [[4.4, 6.0], [[4.6, 9.9], [6.0, 13.6], [7.2, 16.3]]],
        ];
        $tips = array_map(static function (array $string): array {
            [, [, $toward, $end]] = $string;

            return [self::between($end, $toward, 2.0 / hypot($end[0] - $toward[0], $end[1] - $toward[1])), $end];
        }, $strings);

        // The kangaroo pocket: a straight top, the hand openings curving out to its sides.
        $pocketTop = 43.5;
        $pocketBottom = $seamY - 0.2;
        $pocket = 'M'.$this->at(-12.0, $pocketTop).'L'.$this->at(12.0, $pocketTop)
            .'C'.$this->at(13.5, 48.5).' '.$this->at(16.8, 54.6).' '.$this->at(20.0, 57.2)
            .'L'.$this->at(20.3, $pocketBottom).'L'.$this->at(-20.3, $pocketBottom).'L'.$this->at(-20.0, 57.2)
            .'C'.$this->at(-16.8, 54.6).' '.$this->at(-13.5, 48.5).' '.$this->at(-12.0, $pocketTop).'z';
        $pocketSeams = 'M'.$this->at(-11.6, $pocketTop + 0.9).'L'.$this->at(11.6, $pocketTop + 0.9);
        $pocketShade = '';

        foreach ([1.0, -1.0] as $sign) {
            $pocketSeams .= 'M'.$this->at($sign * 12.9, $pocketTop + 0.6).'C'.$this->at($sign * 14.4, 48.6).' '.$this->at($sign * 17.4, 54.1).' '.$this->at($sign * 20.5, 56.5);
            $pocketShade .= 'M'.$this->at($sign * 12.3, $pocketTop + 0.2).'C'.$this->at($sign * 13.8, 48.6).' '.$this->at($sign * 17.0, 54.7).' '.$this->at($sign * 20.2, 57.3);
        }

        // The ribbed cuffs, along their middle from the outer edge to the inner
        // one, and where they are sewn on; the hem band likewise.
        $cuffs = '';
        $ribSeams = 'M'.$this->at(-$chest / 2 + 2.0, $seamY).'L'.$this->at($chest / 2 - 2.0, $seamY);
        $armholes = '';

        foreach ($sides as [$side, $sign]) {
            $from = self::between($side['wrist'], $side['cuffOut'], 0.5);
            $to = self::between($side['wristIn'], $side['cuffIn'], 0.5);
            $cuffs .= 'M'.$this->at($sign * ($from[0] - 0.3), $from[1]).'L'.$this->at($sign * ($to[0] + 0.3), $to[1]);
            $ribSeams .= 'M'.$this->at($sign * $side['wrist'][0], $side['wrist'][1]).'L'.$this->at($sign * $side['wristIn'][0], $side['wristIn'][1]);

            [$sx, $sy] = $side['shoulder'];
            [$ax, $ay] = $side['armpit'];
            $armholes .= 'M'.$this->at($sign * ($sx - 0.4), $sy).'C'.$this->at($sign * ($sx - 1.2), $sy + 9.0).' '.$this->at($sign * ($ax - 0.6), $ay - 8.0).' '.$this->at($sign * $ax, $ay);
        }

        // Between the drawstrings' ends and the pocket, clear of both: as large as the chest allows there.
        $print = 23.0;

        return [
            'chest' => $chest,
            'length' => $length,
            'outline' => $outline,
            'parts' => [
                'hood' => $hood,
                'opening' => $opening,
                'edges' => array_map(fn (array $edge): string => 'M'.$this->chain($edge), $edges),
                'eyelets' => $eyelets,
                'strings' => array_map(fn (array $string): string => 'M'.$this->chain($string), $strings),
                'tips' => array_map(fn (array $tip): string => 'M'.$this->at(...$tip[0]).'L'.$this->at(...$tip[1]), $tips),
                'pocket' => $pocket,
                'pocketSeams' => $pocketSeams,
                'pocketShade' => $pocketShade,
                'cuffs' => ['d' => $cuffs, 'width' => $this->cm(6.6)],
                'band' => ['d' => 'M'.$this->at(-$chest / 2 + 2.3, $seamY + $band / 2).'L'.$this->at($chest / 2 - 2.3, $seamY + $band / 2), 'width' => $this->cm($band)],
                'ribSeams' => $ribSeams,
                'armholes' => $armholes,
            ],
            // Creases where the sleeves bend at the elbow and run down the forearm,
            // drag lines from the armpits, a broad wave over the chest and a
            // shallow one across the pocket.
            'folds' => [
                [40.0, 25.0, 9.0, 2.0, -58.0],
                [-39.4, 25.5, 9.0, 2.0, 58.0],
                [42.0, 42.0, 16.0, 2.6, 84.0],
                [-41.4, 43.0, 16.0, 2.6, -86.0],
                [-16.0, 30.0, 20.0, 2.2, 40.0],
                [16.5, 31.0, 19.0, 2.0, -38.0],
                [3.0, 34.0, 38.0, 4.0, 6.0],
                [-3.0, 52.0, 34.0, 3.0, -4.0],
            ],
            'print' => [-$print / 2, 17.8, $print, $print],
            'pocket' => [-20.3, $pocketTop, 40.6, $pocketBottom - $pocketTop],
        ];
    }

    /**
     * A fold given by its middle, length and breadth in centimetres and its
     * angle, in the drawing's units.
     *
     * @return array{x: float, y: float, rx: float, ry: float, angle: float}
     */
    private function fold(float $x, float $y, float $length, float $breadth, float $angle): array
    {
        [$px, $py] = $this->point($x, $y);

        return ['x' => $px, 'y' => $py, 'rx' => $this->cm($length / 2), 'ry' => $this->cm($breadth / 2), 'angle' => $angle];
    }

    /**
     * A point of the garment, given in centimetres from the middle of the
     * line through the top of the shoulders, in the drawing's units.
     *
     * @return array{0: float, 1: float}
     */
    private function point(float $x, float $y): array
    {
        $cut = self::CUTS[$this->cut];

        return [round(60 + $x * $cut['scale'], 2), round($cut['top'] + $y * $cut['scale'], 2)];
    }

    /** The same point as path data. */
    private function at(float $x, float $y): string
    {
        return implode(' ', $this->point($x, $y));
    }

    /**
     * A box given in centimetres, in the drawing's units.
     *
     * @return array{x: float, y: float, width: float, height: float}
     */
    private function box(float $x, float $y, float $width, float $height): array
    {
        [$px, $py] = $this->point($x, $y);

        return Svg::box($px, $py, $this->cm($width), $this->cm($height));
    }

    /**
     * A chain of cubic curves as path data: its first point, then for each
     * curve two control points and its end. The first point is left out
     * where the chain continues a path.
     *
     * @param  array<int, mixed>  $chain
     */
    private function chain(array $chain, bool $start = true): string
    {
        $path = $start ? $this->at(...$chain[0]) : '';

        foreach (array_slice($chain, 1) as [$one, $two, $end]) {
            $path .= 'C'.$this->at(...$one).' '.$this->at(...$two).' '.$this->at(...$end);
        }

        return $path;
    }

    /**
     * A chain mirrored to the other side of the garment and run backwards,
     * so it goes on where the first half ended.
     *
     * @param  array<int, mixed>  $chain
     * @return array<int, mixed>
     */
    private static function mirrored(array $chain): array
    {
        $flip = static fn (array $point): array => [-$point[0], $point[1]];
        $curves = array_slice($chain, 1);
        $mirrored = [$flip(end($curves)[2])];

        for ($index = count($curves) - 1; $index >= 0; $index--) {
            $from = $index === 0 ? $chain[0] : $curves[$index - 1][2];
            $mirrored[] = [$flip($curves[$index][1]), $flip($curves[$index][0]), $flip($from)];
        }

        return $mirrored;
    }

    /**
     * A point part of the way from one point to another, pushed sideways by
     * $bulge centimetres – to the right of the way, seen along it – to round
     * the line between them.
     *
     * @param  array{0: float, 1: float}  $from
     * @param  array{0: float, 1: float}  $to
     * @return array{0: float, 1: float}
     */
    private static function between(array $from, array $to, float $share, float $bulge = 0.0): array
    {
        $dx = $to[0] - $from[0];
        $dy = $to[1] - $from[1];
        $length = sqrt($dx * $dx + $dy * $dy) ?: 1.0;

        return [$from[0] + $dx * $share - $dy / $length * $bulge, $from[1] + $dy * $share + $dx / $length * $bulge];
    }
}
