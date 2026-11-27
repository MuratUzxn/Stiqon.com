<?php
/**
 * A sheet of every drawing the plugin can make, as one HTML page – to look at
 * the drawings side by side without a WordPress installation.
 *
 *   php tools/dev/mockup-sheet.php > sheet.html                 every kind
 *   php tools/dev/mockup-sheet.php card mug > sheet.html        only these kinds
 *   php tools/dev/mockup-sheet.php --own card > sheet.html      with a visitor's motif instead of the samples
 *
 * Per kind it draws each form, each sample motif, each value of its other
 * options and each switch turned on – one change at a time, not every
 * combination. The page uses the theme's built stylesheet (npm run build), so
 * the drawings cast the shadow they cast in the shop. "--own" puts
 * tests/fixtures/motif.png where the visitor's motif goes, as the shop does
 * once a motif is chosen.
 *
 * Unknown values stop the page with the drawing's own error message, as they
 * do in development.
 */

use Stiqon\Shop\Mockup\Kinds;
use Stiqon\Shop\Mockup\Stage;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$repo = dirname(__DIR__, 2);

define('ABSPATH', $repo.'/');

if (! function_exists('esc_attr')) {
    function esc_attr($text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('esc_html')) {
    function esc_html($text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('wp_get_environment_type')) {
    function wp_get_environment_type(): string
    {
        return 'local';
    }
}

require $repo.'/wp-content/plugins/stiqon-shop/src/autoload.php';
require $repo.'/wp-content/plugins/stiqon-shop/src/helpers.php';

$arguments = array_slice($argv, 1);
$own = in_array('--own', $arguments, true);
$wanted = array_values(array_diff($arguments, ['--own']));
$types = $wanted === [] ? Kinds::types() : $wanted;

foreach ($types as $type) {
    if (! Kinds::has($type)) {
        fwrite(STDERR, "Unknown kind [{$type}]. Known: ".implode(', ', Kinds::types())."\n");
        exit(1);
    }
}

$manifest = json_decode((string) @file_get_contents($repo.'/wp-content/themes/stiqon/assets/build/manifest.json'), true);
$stylesheet = is_array($manifest) && isset($manifest['assets/src/css/app.css']['file'])
    ? 'file://'.$repo.'/wp-content/themes/stiqon/assets/build/'.$manifest['assets/src/css/app.css']['file']
    : null;

if ($stylesheet === null) {
    fwrite(STDERR, "The theme is not built; the drawings appear without their shadow. Run: npm run build\n");
}

$image = 'data:image/png;base64,'.base64_encode((string) file_get_contents($repo.'/tests/fixtures/motif.png'));

/** The drawing as the shop shows it once a visitor has chosen a motif. */
$withOwnMotif = static function (string $svg) use ($image): string {
    return str_replace(
        ['x-show="idle" data-motif-sample', '<g x-show="active" style="display:none">', 'x-bind:href="url"'],
        ['x-show="idle" style="display:none" data-motif-sample', '<g x-show="active">', 'href="'.$image.'"'],
        $svg,
    );
};

/** One change at a time: each form, each motif, each value of other options, each switch. */
$variants = static function (string $type): array {
    $kind = Kinds::of($type);
    $form = $kind::form();
    $first = array_key_first($form['values']);
    $variants = [];

    foreach (array_keys($form['values']) as $value) {
        $variants[] = [$form['key'] => (string) $value];
    }

    foreach ($kind::motifs() as $motif) {
        $variants[] = [$form['key'] => (string) $first, 'motif' => $motif];
    }

    foreach ($kind::keys() as $key => $values) {
        if ($key === $form['key'] || $key === 'motif') {
            continue;
        }

        if ($values === null) {
            $variants[] = [$form['key'] => (string) $first, $key => true];

            continue;
        }

        foreach ($values as $value) {
            $variants[] = [$form['key'] => (string) $first, $key => $value];
        }
    }

    return array_values(array_unique($variants, SORT_REGULAR));
};

ob_start();
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Zeichnungen<?php echo $own ? ' mit eigenem Motiv' : ''; ?></title>
    <?php if ($stylesheet !== null) : ?>
        <link rel="stylesheet" href="<?php echo esc_attr($stylesheet); ?>">
    <?php endif; ?>
    <style>
        body { margin: 0; padding: 32px; background: #fff; }
        h2 { margin: 40px 0 16px; font: 650 28px/1.2 var(--font-sans, sans-serif); }
        .sheet { display: grid; grid-template-columns: repeat(auto-fill, 220px); gap: 24px 16px; }
        .sheet figure { margin: 0; }
        .sheet figcaption { margin-top: 8px; font: 500 12px/1.4 var(--font-sans, sans-serif); color: #6b6b6b; word-break: break-word; }
    </style>
</head>
<body>
<?php foreach ($types as $type) : ?>
    <h2><?php echo esc_html(Kinds::of($type)::label()); ?> <small style="font-size:16px;color:#6b6b6b">(<?php echo esc_html($type); ?>)</small></h2>
    <div class="sheet">
        <?php foreach ($variants($type) as $spec) : ?>
            <?php $svg = stiqon_mockup($type, $spec); ?>
            <figure>
                <?php echo Stage::render($own ? $withOwnMotif($svg) : $svg); ?>
                <figcaption><?php echo esc_html(json_encode($spec, JSON_UNESCAPED_UNICODE)); ?><br><?php echo esc_html(Kinds::of($type)::fromArray($spec)->describe()); ?></figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
</body>
</html>
<?php
echo ob_get_clean();
