<?php
/**
 * A T-shirt or a hoodie as inline SVG. See Stiqon\Shop\Mockup\Shirt for the
 * cuts and how the garment is laid out.
 *
 * @var \Stiqon\Shop\Mockup\Shirt $mockup
 * @var string                      $class  extra classes from the caller
 */

use Stiqon\Shop\Mockup\Shirt;
use Stiqon\Shop\Mockup\Svg;
use Stiqon\Shop\Template;

defined('ABSPATH') || exit;

$uid = $mockup->uid;
$parts = $mockup->parts;
$hoodie = $mockup->cut === 'hoodie';
$grey = $mockup->color === 'grey';
$dark = in_array($mockup->color, Shirt::DARK_COLORS, true);
$x = $mockup->body['x'];
$y = $mockup->body['y'];
$w = $mockup->body['width'];
$h = $mockup->body['height'];

// cloth: the fabric; edge: where it turns under, at the outline; line: seams
// and the edges of the parts lying on the cloth; inside: the inside of the
// garment, seen through the neck or in the hood; thread: the stitching; rib:
// the ribs of the knitted bands; knit: the texture of the jersey, and how much
// of it shows; fold: shade in the folds, with how dark their valleys and how
// light their ridges are – white cloth can only show the shade, dark cloth
// mostly the light; metal: the hoodie's eyelets and the tips of its strings.
$tone = [
    'white' => ['cloth' => '#fbfbf9', 'edge' => '#d3d2cd', 'line' => '#cfcdc7', 'inside' => '#e4e3df', 'thread' => '#cdcbc4', 'rib' => '#d2d0ca', 'knit' => '#8a8780', 'texture' => 0.018, 'fold' => '#3b3a37', 'valley' => 0.065, 'ridge' => 0.0, 'metal' => '#b7b5ae'],
    'black' => ['cloth' => '#1f1f1f', 'edge' => '#171717', 'line' => '#0d0d0d', 'inside' => '#131313', 'thread' => '#383835', 'rib' => '#111111', 'knit' => '#ffffff', 'texture' => 0.009, 'fold' => '#000000', 'valley' => 0.22, 'ridge' => 0.065, 'metal' => '#7a7973'],
    'navy' => ['cloth' => '#1f2a44', 'edge' => '#18213a', 'line' => '#0f1626', 'inside' => '#141b2e', 'thread' => '#384462', 'rib' => '#131a2d', 'knit' => '#ffffff', 'texture' => 0.009, 'fold' => '#02050c', 'valley' => 0.22, 'ridge' => 0.065, 'metal' => '#878c99'],
    'grey' => ['cloth' => '#b8babc', 'edge' => '#9d9fa2', 'line' => '#939598', 'inside' => '#96989b', 'thread' => '#9b9da0', 'rib' => '#9c9ea1', 'knit' => '#3d3f42', 'texture' => 0.016, 'fold' => '#25272a', 'valley' => 0.13, 'ridge' => 0.1, 'metal' => '#dcdddd'],
][$mockup->color];
?>
<svg
    viewBox="0 0 120 120"
    role="img"
    aria-label="<?php echo esc_attr($mockup->describe()); ?>"
    data-mockup="shirt"
    class="<?php echo esc_attr(Svg::classes($class)); ?>"
>
    <defs>
        <clipPath id="<?php echo esc_attr($uid); ?>-cloth">
            <path d="<?php echo esc_attr($mockup->outline); ?>" />
            <?php if ($hoodie) : ?>
                <path d="<?php echo esc_attr($parts['hood']); ?>" />
            <?php endif; ?>
        </clipPath>

        <?php // Single jersey: fine wales running down the cloth ?>
        <pattern id="<?php echo esc_attr($uid); ?>-knit" width="0.6" height="0.6" patternUnits="userSpaceOnUse">
            <path d="M0.3 0v0.6" stroke="<?php echo esc_attr($tone['knit']); ?>" stroke-opacity="<?php echo esc_attr($tone['texture']); ?>" stroke-width="0.24" />
        </pattern>
        <?php if ($grey) : ?>
            <?php // Melange: light and dark fibres spun together, a fine fleck all over, scattered so no rows show ?>
            <pattern id="<?php echo esc_attr($uid); ?>-fleck" width="6" height="5" patternUnits="userSpaceOnUse" patternTransform="rotate(-2)">
                <path d="M2.01 4.9l0.51 -0.01M0.97 3.93l0.27 -0.04M3.67 0.24l0.18 -0.02M4.82 2.19l0.24 -0.03M2.73 0.38l0.53 0.06M0.36 0.27l0.52 -0.1M5.8 3.3l0.31 -0.05M0.63 3.49l0.33 0.02M5.04 1.94l0.52 -0.1M2.94 0.25l0.35 -0.01M0.37 0.74l0.37 -0.07M2.45 4.75l0.35 0.07M4.85 1.05l0.21 0.03M0.17 3.85l0.48 0.04M0.08 2.69l0.48 -0.04M2.23 0.35l0.27 -0.02M4.94 4.73l0.45 0.02M5.12 1.38l0.33 0.03M5.39 3.13l0.47 0.04M1.92 4.57l0.48 0.09M0.31 3.54l0.29 -0.04M2.1 0l0.34 0.06M5.56 4.18l0.19 -0.03M3.68 0.37l0.19 0.02M5.58 3.75l0.36 -0.06M5.07 4.77l0.22 -0.03M1.78 2.34l0.27 -0.01M5.77 1.16l0.44 -0.05M0.46 1.47l0.49 -0.04M3.1 2.45l0.18 0.02" stroke="#ebeced" stroke-opacity="0.45" stroke-width="0.12" stroke-linecap="round" />
                <path d="M3.79 2.27l0.55 0.04M3.08 0.43l0.49 0.03M1.92 0.83l0.38 0.01M2.04 4.43l0.39 -0.01M3.96 1.7l0.39 -0.07M4.24 1.88l0.51 -0.09M2.79 3.76l0.29 0.06M1.99 4.52l0.33 0.06M1.06 3.66l0.32 -0.06M2.68 1.23l0.5 0.08M2.06 4.54l0.45 0.02M5.67 2.7l0.43 -0.09M4.22 4.7l0.25 -0.05M0.37 2.27l0.49 -0.04M2.94 1.26l0.5 0.11M5.33 4.79l0.35 0.01M2.88 4.07l0.38 -0.01M3.2 0.54l0.44 -0.02M2.74 2.73l0.28 0.06M4.72 1.81l0.55 -0.04M4.35 2.12l0.2 0.04M3.15 3.11l0.25 0.04M0.14 1.16l0.2 -0.04M3.92 2.29l0.48 0.1M1.48 1.12l0.44 0.01M1.97 0.24l0.43 -0.02" stroke="#76787b" stroke-opacity="0.35" stroke-width="0.12" stroke-linecap="round" />
            </pattern>
        <?php endif; ?>

        <radialGradient id="<?php echo esc_attr($uid); ?>-valley">
            <stop offset="0" stop-color="<?php echo esc_attr($tone['fold']); ?>" stop-opacity="<?php echo esc_attr($tone['valley']); ?>" />
            <stop offset="1" stop-color="<?php echo esc_attr($tone['fold']); ?>" stop-opacity="0" />
        </radialGradient>
        <?php if ($tone['ridge'] > 0) : ?>
            <radialGradient id="<?php echo esc_attr($uid); ?>-ridge">
                <stop offset="0" stop-color="#fff" stop-opacity="<?php echo esc_attr($tone['ridge']); ?>" />
                <stop offset="1" stop-color="#fff" stop-opacity="0" />
            </radialGradient>
        <?php endif; ?>

        <?php // The cloth rounds over at its edges and lies in shade there ?>
        <radialGradient id="<?php echo esc_attr($uid); ?>-seams" cx="0.5" cy="0.45" r="0.75">
            <stop offset="0.62" stop-color="<?php echo esc_attr($tone['fold']); ?>" stop-opacity="0" />
            <stop offset="1" stop-color="<?php echo esc_attr($tone['fold']); ?>" stop-opacity="0.1" />
        </radialGradient>

        <?php // Inside the neck or the hood it grows darker towards the bottom, under the front ?>
        <linearGradient id="<?php echo esc_attr($uid); ?>-deep" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#000" stop-opacity="0" />
            <stop offset="1" stop-color="#000" stop-opacity="<?php echo esc_attr($dark ? '0.25' : '0.12'); ?>" />
        </linearGradient>
    </defs>

    <g transform="rotate(<?php echo esc_attr(Shirt::TILT); ?> 60 60)">
        <?php if ($hoodie) : ?>
            <?php // The hood, lying behind the neck; the body covers its lower edge ?>
            <path d="<?php echo esc_attr($parts['hood']); ?>" fill="<?php echo esc_attr($tone['cloth']); ?>" stroke="<?php echo esc_attr($tone['edge']); ?>" stroke-width="0.4" />
            <path d="<?php echo esc_attr($parts['hood']); ?>" fill="none" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.3" />
        <?php endif; ?>

        <?php // The garment ?>
        <path d="<?php echo esc_attr($mockup->outline); ?>" fill="<?php echo esc_attr($tone['cloth']); ?>" />

        <?php if ($hoodie) : ?>
            <?php // The hood's opening faces the viewer: its inside shows down to where the front edges cross, in front of the neck ?>
            <path d="<?php echo esc_attr($parts['opening']); ?>" fill="<?php echo esc_attr($tone['inside']); ?>" />
            <path d="<?php echo esc_attr($parts['opening']); ?>" fill="url(#<?php echo esc_attr($uid); ?>-deep)" />

            <?php // The hood's doubled front edges, stitched along their length, the left one on top ?>
            <g fill="none" stroke-linecap="round">
                <?php foreach ($parts['edges'] as $edge) : ?>
                    <path d="<?php echo esc_attr($edge); ?>" stroke="#000" stroke-opacity="0.12" stroke-width="<?php echo esc_attr($mockup->cm(2.9)); ?>" transform="translate(0.3 0.45)" />
                    <path d="<?php echo esc_attr($edge); ?>" stroke="<?php echo esc_attr($tone['line']); ?>" stroke-width="<?php echo esc_attr($mockup->cm(2.7)); ?>" />
                    <path d="<?php echo esc_attr($edge); ?>" stroke="<?php echo esc_attr($tone['cloth']); ?>" stroke-width="<?php echo esc_attr($mockup->cm(2.35)); ?>" />
                    <path d="<?php echo esc_attr($edge); ?>" stroke="<?php echo esc_attr($tone['thread']); ?>" stroke-width="0.25" stroke-dasharray="0.8 0.5" />
                <?php endforeach; ?>
            </g>
            <path d="<?php echo esc_attr($parts['eyelets']); ?>" fill="<?php echo esc_attr($tone['inside']); ?>" stroke="<?php echo esc_attr($tone['metal']); ?>" stroke-width="0.5" />

            <?php // The kangaroo pocket: a second layer of cloth, its hand openings in a little shade ?>
            <path d="<?php echo esc_attr($parts['pocketShade']); ?>" fill="none" stroke="#000" stroke-opacity="0.1" stroke-width="1.1" stroke-linecap="round" transform="translate(0.45 0.2)" />
            <path d="<?php echo esc_attr($parts['pocket']); ?>" fill="<?php echo esc_attr($tone['cloth']); ?>" stroke="<?php echo esc_attr($tone['line']); ?>" stroke-opacity="0.6" stroke-width="0.35" />
            <path d="<?php echo esc_attr($parts['pocketSeams']); ?>" fill="none" stroke="<?php echo esc_attr($tone['thread']); ?>" stroke-width="0.28" stroke-dasharray="0.8 0.5" />

            <?php // Rib knit at the cuffs and the hem band, and where they are sewn on ?>
            <?php foreach (['band', 'cuffs'] as $band) : ?>
                <path d="<?php echo esc_attr($parts[$band]['d']); ?>" stroke="<?php echo esc_attr($tone['rib']); ?>" stroke-opacity="0.55" stroke-width="<?php echo esc_attr($parts[$band]['width']); ?>" stroke-dasharray="0.3 0.55" />
            <?php endforeach; ?>
            <path d="<?php echo esc_attr($parts['ribSeams']); ?>" stroke="<?php echo esc_attr($tone['line']); ?>" stroke-opacity="0.8" stroke-width="0.35" />
        <?php else : ?>
            <?php // Through the neck the inside of the back shows, below the back of the collar ?>
            <path d="<?php echo esc_attr($parts['inside']); ?>" fill="<?php echo esc_attr($tone['inside']); ?>" />
            <path d="<?php echo esc_attr($parts['inside']); ?>" fill="url(#<?php echo esc_attr($uid); ?>-deep)" />
            <path d="<?php echo esc_attr($parts['backCollar']); ?>" fill="<?php echo esc_attr($tone['cloth']); ?>" />
            <path d="<?php echo esc_attr($parts['backCollar']); ?>" fill="#000" fill-opacity="0.06" />

            <?php // The collar: rib knit, stitched down below its seam ?>
            <path d="<?php echo esc_attr($parts['collar']); ?>" fill="<?php echo esc_attr($tone['cloth']); ?>" />
            <path d="<?php echo esc_attr($parts['collarRib']['d']); ?>" fill="none" stroke="<?php echo esc_attr($tone['rib']); ?>" stroke-opacity="0.5" stroke-width="<?php echo esc_attr($parts['collarRib']['width']); ?>" stroke-dasharray="0.25 0.5" />
            <path d="<?php echo esc_attr($parts['collar']); ?>" fill="none" stroke="<?php echo esc_attr($tone['line']); ?>" stroke-opacity="0.7" stroke-width="0.3" />
            <path d="<?php echo esc_attr($parts['collarSeam']); ?>" fill="none" stroke="<?php echo esc_attr($tone['thread']); ?>" stroke-width="0.25" stroke-dasharray="0.8 0.5" />

            <?php // The hems of the sleeves and of the shirt ?>
            <path d="<?php echo esc_attr($parts['hems']); ?>" fill="none" stroke="<?php echo esc_attr($tone['thread']); ?>" stroke-width="0.28" stroke-dasharray="0.8 0.5" />
        <?php endif; ?>

        <?php // Where the sleeves are sewn in ?>
        <path d="<?php echo esc_attr($parts['armholes']); ?>" fill="none" stroke="<?php echo esc_attr($tone['line']); ?>" stroke-opacity="0.55" stroke-width="0.3" />

        <?php if ($grey) : ?>
            <?php // The fleck is in the yarn: the print covers it, all but a trace ?>
            <g clip-path="url(#<?php echo esc_attr($uid); ?>-cloth)">
                <rect x="8" y="8" width="104" height="104" fill="url(#<?php echo esc_attr($uid); ?>-fleck)" />
            </g>
        <?php endif; ?>

        <?php
        // The samples are printed straight onto the cloth, without a ground of
        // their own: in dark ink on white and grey cloth, in light ink on black
        // and navy (Shirt::motifVars()). A visitor's logo gets no backing either,
        // so the cloth shows where it is transparent. The ink lets a little of
        // the cloth through.
        Template::output('mockup/parts/motif', [
            'uid' => $uid,
            'box' => $mockup->printBox,
            'motif' => 'motif/'.$mockup->motif,
            'viewBox' => '0 0 100 100',
            'fit' => 'meet',
            'ownFit' => 'meet',
            'vars' => $mockup->motifVars(),
            'backing' => null,
            'attributes' => 'opacity="0.93"',
        ]);
        ?>

        <?php // Over the print as well: the knit and the folds of the cloth ?>
        <g clip-path="url(#<?php echo esc_attr($uid); ?>-cloth)" pointer-events="none">
            <rect x="8" y="8" width="104" height="104" fill="url(#<?php echo esc_attr($uid); ?>-knit)" />
            <?php foreach ($mockup->folds as $fold) : ?>
                <g transform="translate(<?php echo esc_attr($fold['x']); ?> <?php echo esc_attr($fold['y']); ?>) rotate(<?php echo esc_attr($fold['angle']); ?>)">
                    <?php if ($tone['ridge'] > 0) : ?>
                        <ellipse cy="<?php echo esc_attr(-$fold['ry']); ?>" rx="<?php echo esc_attr($fold['rx']); ?>" ry="<?php echo esc_attr($fold['ry']); ?>" fill="url(#<?php echo esc_attr($uid); ?>-ridge)" />
                    <?php endif; ?>
                    <ellipse rx="<?php echo esc_attr($fold['rx']); ?>" ry="<?php echo esc_attr($fold['ry']); ?>" fill="url(#<?php echo esc_attr($uid); ?>-valley)" />
                </g>
            <?php endforeach; ?>
            <rect x="<?php echo esc_attr($x - 24); ?>" y="<?php echo esc_attr($y - 12); ?>" width="<?php echo esc_attr($w + 48); ?>" height="<?php echo esc_attr($h + 14); ?>" fill="url(#<?php echo esc_attr($uid); ?>-seams)" />
        </g>

        <?php if ($hoodie) : ?>
            <?php // The drawstrings lie on the cloth and cast a little shadow; their ends are tipped ?>
            <g fill="none" stroke-linecap="round">
                <?php foreach ($parts['strings'] as $index => $string) : ?>
                    <path d="<?php echo esc_attr($string); ?>" stroke="#000" stroke-opacity="0.16" stroke-width="1.5" transform="translate(0.35 0.5)" />
                    <path d="<?php echo esc_attr($string); ?>" stroke="<?php echo esc_attr($tone['line']); ?>" stroke-width="1.3" />
                    <path d="<?php echo esc_attr($string); ?>" stroke="<?php echo esc_attr($tone['cloth']); ?>" stroke-width="0.95" />
                    <path d="<?php echo esc_attr($parts['tips'][$index]); ?>" stroke="<?php echo esc_attr($tone['metal']); ?>" stroke-width="1.35" />
                <?php endforeach; ?>
            </g>
        <?php endif; ?>

        <path d="<?php echo esc_attr($mockup->outline); ?>" fill="none" stroke="<?php echo esc_attr($tone['edge']); ?>" stroke-width="0.4" />
        <path d="<?php echo esc_attr($mockup->outline); ?>" fill="none" stroke="#0a0a0a" stroke-opacity="0.1" stroke-width="0.3" />
    </g>
</svg>
