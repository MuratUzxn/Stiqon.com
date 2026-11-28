<?php
/**
 * Example designs for development, tests and previews: six designs across both
 * media (Werbemittel) and three occasions, and two that the shop must not show –
 * one published without its release, one released without its preview image.
 *
 *   tools/dev/wp.sh eval-file tools/dev/example-designs.php
 *
 * Real designs are made with an image generator and released by a person after
 * checking them (README "Designs"). These stand in for them, so that the list,
 * its filters and the design pages can be built and tested. Their pictures are
 * drawn here with GD from shapes alone – no lettering, nothing copied – so every
 * run makes the same files. Every design is marked as an example (meta
 * "_stiqon_example") and its address starts with "beispiel-".
 *
 * Running it again brings the examples back to what is written here. Designs
 * are matched by their address, their pictures by a key that changes with the
 * drawing. Nothing else is touched.
 */

use Stiqon\Shop\Content\Designs;

if (! class_exists(Designs::class) || ! post_type_exists(Designs::TYPE)) {
    WP_CLI::error('The plugin "Stiqon Shop-Funktionen" is not active.');
}

if (! function_exists('imagecreatetruecolor')) {
    WP_CLI::error('PHP has no GD; the example designs are drawn with it.');
}

require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/image.php';
require_once ABSPATH.'wp-admin/includes/media.php';

/** Changes whenever a drawing below changes, so the pictures are made anew. */
const STIQON_EXAMPLE_DRAWINGS = 'v1';

// --- Terms -------------------------------------------------------------------------

$terms = [
    Designs::MEDIUM => ['sticker' => 'Sticker', 'flyer' => 'Flyer'],
    Designs::OCCASION => ['eroeffnung' => 'Eröffnung', 'feier' => 'Feier', 'rabattaktion' => 'Rabattaktion'],
];

$termIds = [];

foreach ($terms as $taxonomy => $list) {
    foreach ($list as $slug => $name) {
        $term = get_term_by('slug', $slug, $taxonomy);
        $result = $term instanceof WP_Term ? ['term_id' => $term->term_id] : wp_insert_term($name, $taxonomy, ['slug' => $slug]);

        if (is_wp_error($result)) {
            WP_CLI::error("Term {$name}: ".$result->get_error_message());
        }

        $termIds[$taxonomy][$slug] = (int) $result['term_id'];
    }
}

// --- Drawing -----------------------------------------------------------------------
// Drawn at twice the size and scaled down: GD fills shapes without smoothing
// their edges, and the scaling does that.

$colour = static function (GdImage $image, string $hex, int $alpha = 0): int {
    [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');

    return imagecolorallocatealpha($image, $r, $g, $b, $alpha);
};

/** A regular star or polygon: $points corners on the outer radius, as many on the inner. */
$star = static function (float $cx, float $cy, float $outer, float $inner, int $points, float $turn = -90): array {
    $corners = [];

    for ($i = 0; $i < $points * 2; $i++) {
        $radius = $i % 2 === 0 ? $outer : $inner;
        $angle = deg2rad($turn + $i * 180 / $points);
        $corners[] = (int) round($cx + $radius * cos($angle));
        $corners[] = (int) round($cy + $radius * sin($angle));
    }

    return $corners;
};

/** A rectangle turned about its centre. */
$turned = static function (float $cx, float $cy, float $width, float $height, float $degrees): array {
    $corners = [];
    $angle = deg2rad($degrees);

    foreach ([[-1, -1], [1, -1], [1, 1], [-1, 1]] as [$x, $y]) {
        $dx = $x * $width / 2;
        $dy = $y * $height / 2;
        $corners[] = (int) round($cx + $dx * cos($angle) - $dy * sin($angle));
        $corners[] = (int) round($cy + $dx * sin($angle) + $dy * cos($angle));
    }

    return $corners;
};

/** The same numbers on every run, without depending on PHP's random generator. */
$sequence = static function (int $seed): Closure {
    return static function (int $max) use (&$seed): int {
        $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;

        return $seed % $max;
    };
};

$drawings = [
    // A starburst with a star in its middle.
    'neu-eroeffnet' => [1000, 1000, null, static function (GdImage $image) use ($colour, $star): void {
        imagefilledpolygon($image, $star(1000, 1000, 940, 800, 24), $colour($image, '#ff6b4a'));
        imagefilledellipse($image, 1000, 1000, 1320, 1320, $colour($image, '#fff4e0'));
        imagefilledellipse($image, 1000, 1000, 1040, 1040, $colour($image, '#141414'));
        imagefilledpolygon($image, $star(1000, 1030, 380, 160, 5), $colour($image, '#ffd84d'));
    }],
    // A round sticker full of confetti.
    'konfetti' => [1000, 1000, null, static function (GdImage $image) use ($colour, $turned, $sequence): void {
        imagefilledellipse($image, 1000, 1000, 1880, 1880, $colour($image, '#ffd84d'));
        $next = $sequence(7);
        $colours = ['#ff6b4a', '#1f6feb', '#141414', '#2fb36d', '#ffffff'];

        for ($i = 0; $i < 70; $i++) {
            $angle = deg2rad($next(360));
            $distance = 60 + $next(660);
            $x = 1000 + $distance * cos($angle);
            $y = 1000 + $distance * sin($angle);
            $fill = $colour($image, $colours[$i % count($colours)]);

            if ($i % 3 === 0) {
                imagefilledellipse($image, (int) $x, (int) $y, 56, 56, $fill);
            } else {
                imagefilledpolygon($image, $turned($x, $y, 110, 40, $next(180)), $fill);
            }
        }
    }],
    // A percent sign on a square with soft corners.
    'prozente' => [1000, 1000, null, static function (GdImage $image) use ($colour, $turned): void {
        $blue = $colour($image, '#1f6feb');
        $white = $colour($image, '#ffffff');

        imagefilledrectangle($image, 300, 100, 1700, 1900, $blue);
        imagefilledrectangle($image, 100, 300, 1900, 1700, $blue);

        foreach ([[300, 300], [1700, 300], [300, 1700], [1700, 1700]] as [$x, $y]) {
            imagefilledellipse($image, $x, $y, 400, 400, $blue);
        }

        imagefilledpolygon($image, $turned(1000, 1000, 1500, 170, -55), $white);

        foreach ([[680, 640], [1320, 1360]] as [$x, $y]) {
            imagefilledellipse($image, $x, $y, 480, 480, $white);
            imagefilledellipse($image, $x, $y, 240, 240, $blue);
        }
    }],
    // A sun rising under stripes.
    'grosse-eroeffnung' => [1000, 1414, '#fff4e0', static function (GdImage $image) use ($colour, $turned): void {
        $ink = $colour($image, '#141414');
        $coral = $colour($image, '#ff6b4a');

        for ($i = 0; $i < 5; $i++) {
            imagefilledrectangle($image, 0, 160 + $i * 120, 2000, 210 + $i * 120, $ink);
        }

        for ($i = 0; $i < 12; $i++) {
            $angle = 180 + $i * 180 / 11;
            $x = 1000 + 900 * cos(deg2rad($angle));
            $y = 2400 + 900 * sin(deg2rad($angle));
            imagefilledpolygon($image, $turned($x, $y, 360, 70, $angle), $coral);
        }

        imagefilledellipse($image, 1000, 2500, 1300, 1300, $coral);
        imagefilledrectangle($image, 0, 2620, 2000, 2828, $ink);
    }],
    // Balloons under a garland.
    'gartenfest' => [1000, 1414, '#e6f4ea', static function (GdImage $image) use ($colour): void {
        $ink = $colour($image, '#141414');
        $flags = ['#ff6b4a', '#ffd84d', '#1f6feb', '#2fb36d'];

        imagesetthickness($image, 8);
        imageline($image, 0, 240, 2000, 240, $ink);

        for ($i = 0; $i < 10; $i++) {
            $x = 40 + $i * 200;
            imagefilledpolygon($image, [$x, 240, $x + 160, 240, $x + 80, 420], $colour($image, $flags[$i % 4]));
        }

        foreach ([[620, 1300, '#ff6b4a'], [1000, 1080, '#1f6feb'], [1380, 1340, '#ffd84d']] as [$x, $y, $fill]) {
            imageline($image, $x, $y + 300, $x + ($x < 1000 ? 180 : ($x > 1000 ? -180 : 0)), 2400, $ink);
            imagefilledellipse($image, $x, $y, 480, 600, $colour($image, $fill));
            imagefilledpolygon($image, [$x - 40, $y + 330, $x + 40, $y + 330, $x, $y + 290], $colour($image, $fill));
        }
    }],
    // A dark sheet with a yellow band and a percent sign.
    'wochenangebot' => [1000, 1414, '#141414', static function (GdImage $image) use ($colour, $turned): void {
        $yellow = $colour($image, '#ffd84d');
        $paper = $colour($image, '#ffffff');
        $blue = $colour($image, '#1f6feb');

        imagefilledpolygon($image, $turned(1000, 1900, 3200, 520, -18), $yellow);
        imagefilledellipse($image, 1000, 1000, 1100, 1100, $paper);
        imagefilledpolygon($image, $turned(1000, 1000, 820, 110, -55), $blue);

        foreach ([[800, 770], [1200, 1230]] as [$x, $y]) {
            imagefilledellipse($image, $x, $y, 300, 300, $blue);
            imagefilledellipse($image, $x, $y, 140, 140, $paper);
        }
    }],
    // Leaves – for the design that is published but not released.
    'herbstmarkt' => [1000, 1414, '#fbeee0', static function (GdImage $image) use ($colour, $turned, $sequence): void {
        $next = $sequence(11);
        $colours = ['#c8562a', '#e09a2f', '#8a3b1d'];

        for ($i = 0; $i < 24; $i++) {
            imagefilledpolygon($image, $turned(200 + $next(1600), 200 + $next(2400), 320, 160, $next(180)), $colour($image, $colours[$i % 3]));
        }
    }],
];

/** Draws one picture and saves it as PNG in a temporary file. */
$picture = static function (string $key) use ($drawings, $colour): string {
    [$width, $height, $background, $draw] = $drawings[$key];

    $large = imagecreatetruecolor($width * 2, $height * 2);
    imagealphablending($large, false);
    imagefilledrectangle($large, 0, 0, $width * 2, $height * 2, $background !== null ? $colour($large, $background) : $colour($large, '#ffffff', 127));
    imagealphablending($large, true);
    $draw($large);

    $image = imagecreatetruecolor($width, $height);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagecopyresampled($image, $large, 0, 0, 0, 0, $width, $height, $width * 2, $height * 2);

    $file = wp_tempnam('beispiel-'.$key.'.png');
    imagepng($image, $file, 9);

    return $file;
};

// --- Designs -----------------------------------------------------------------------

// [slug, title, medium, occasion, description, picture or null, released]
$designs = [
    ['neu-eroeffnet', 'Neu eröffnet', 'sticker', 'eroeffnung', 'Ein Strahlenkranz mit Stern – für Tüten, Schaufenster und alles, was neu ist.', 'neu-eroeffnet', true],
    ['grosse-eroeffnung', 'Große Eröffnung', 'flyer', 'eroeffnung', 'Eine Sonne geht unter Streifen auf. Platz für Datum und Adresse bleibt in der Mitte.', 'grosse-eroeffnung', true],
    ['konfetti', 'Konfetti', 'sticker', 'feier', 'Ein runder Sticker voller Konfetti, für Einladungen und Geschenke.', 'konfetti', true],
    ['gartenfest', 'Gartenfest', 'flyer', 'feier', 'Luftballons unter einer Wimpelkette – für das Fest im Hof oder im Verein.', 'gartenfest', true],
    ['prozente', 'Prozente', 'sticker', 'rabattaktion', 'Ein Prozentzeichen auf Blau: auf Ware, Kasse und Tür.', 'prozente', true],
    ['wochenangebot', 'Wochenangebot', 'flyer', 'rabattaktion', 'Ein gelbes Band über dunklem Grund, darüber das Prozentzeichen.', 'wochenangebot', true],
    // Not shown: published, with its picture, but nobody has released it.
    ['herbstmarkt', 'Herbstmarkt', 'flyer', 'feier', 'Bunte Blätter – noch nicht geprüft.', 'herbstmarkt', false],
    // Not shown: released, but its picture is missing.
    ['kaffeepause', 'Kaffeepause', 'sticker', 'feier', 'Wartet noch auf sein Vorschaubild.', null, true],
];

$made = ['designs' => 0, 'pictures' => 0];

foreach ($designs as $order => [$slug, $title, $medium, $occasion, $text, $key, $released]) {
    $name = 'beispiel-'.$slug;
    $existing = get_posts(['post_type' => Designs::TYPE, 'name' => $name, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids']);

    // Newest first in the list: the first design here is the newest.
    $date = gmdate('Y-m-d H:i:s', strtotime('2026-11-01 12:00:00') - $order * 3600);

    $id = wp_insert_post([
        'ID' => $existing[0] ?? 0,
        'post_type' => Designs::TYPE,
        'post_name' => $name,
        'post_title' => $title,
        'post_content' => $text,
        'post_excerpt' => 'Beispieldesign für lokale Seiten und Tests.',
        'post_status' => 'publish',
        'post_date' => $date,
        'post_date_gmt' => $date,
    ], true);

    if (is_wp_error($id)) {
        WP_CLI::error("Design {$title}: ".$id->get_error_message());
    }

    wp_set_object_terms($id, [$termIds[Designs::MEDIUM][$medium]], Designs::MEDIUM);
    wp_set_object_terms($id, [$termIds[Designs::OCCASION][$occasion]], Designs::OCCASION);

    update_post_meta($id, '_stiqon_example', '1');
    update_post_meta($id, Designs::JOB, '');
    update_post_meta($id, Designs::MODELS, '');
    update_post_meta($id, Designs::REVIEW, 'Beispiel für lokale Seiten und Tests, mit PHP aus Formen gezeichnet – kein echtes Design.');

    // The picture: kept while its drawing is unchanged, made anew otherwise.
    $current = (int) get_post_thumbnail_id($id);
    $wanted = $key !== null ? $key.'-'.STIQON_EXAMPLE_DRAWINGS : null;

    if ($current > 0 && get_post_meta($current, '_stiqon_example_image', true) !== $wanted) {
        delete_post_thumbnail($id);
        wp_delete_attachment($current, true);
        $current = 0;
    }

    if ($wanted !== null && $current === 0) {
        $file = $picture($key);
        $attachment = media_handle_sideload(['name' => $name.'.png', 'tmp_name' => $file], $id, $title);

        if (is_wp_error($attachment)) {
            @unlink($file);
            WP_CLI::error("Picture of {$title}: ".$attachment->get_error_message());
        }

        update_post_meta($attachment, '_stiqon_example_image', $wanted);
        update_post_meta($attachment, '_wp_attachment_image_alt', '');
        set_post_thumbnail($id, $attachment);
        $made['pictures']++;
    }

    // Released is the picture that was checked – here the one just drawn, or none for the
    // design that waits for its picture.
    if ($released) {
        update_post_meta($id, Designs::RELEASED, (string) (int) get_post_thumbnail_id($id));
    } else {
        delete_post_meta($id, Designs::RELEASED);
    }

    $made['designs']++;
}

WP_CLI::success("Example designs: {$made['designs']} designs, {$made['pictures']} new pictures.");
