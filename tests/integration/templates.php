<?php
/**
 * The theme replaces some of WooCommerce's templates with its own (the files
 * under woocommerce/ in the theme). Each says in its head which version of
 * WooCommerce's template it was written from. When WooCommerce changes one of
 * those templates, the theme's copy has to be looked at again – this check
 * says which.
 *
 *   tools/dev/wp.sh eval-file tests/integration/templates.php
 */

$version = static function (string $file): ?string {
    return preg_match('/^\s*\*\s*@version\s+([\d.]+)/m', (string) file_get_contents($file, false, null, 0, 4096), $found) ? $found[1] : null;
};

$theme = get_theme_file_path('woocommerce');
$core = WC()->plugin_path().'/templates';
$problems = [];
$checked = 0;

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($theme, FilesystemIterator::SKIP_DOTS));

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $relative = ltrim(str_replace($theme, '', $file->getPathname()), '/');
    $original = $core.'/'.$relative;

    if (! is_file($original)) {
        $problems[] = "{$relative}: WooCommerce has no template of that name (any more).";

        continue;
    }

    $ours = $version($file->getPathname());
    $theirs = $version($original);

    if ($ours === null || $theirs === null) {
        $problems[] = "{$relative}: no @version in ".($ours === null ? 'the theme\'s file' : 'WooCommerce\'s file').'.';
    } elseif (version_compare($theirs, $ours, '>')) {
        $problems[] = "{$relative}: written from version {$ours}, WooCommerce's is now {$theirs}. Compare the two, bring over what changed, then raise @version in the theme's file.";
    } elseif (version_compare($theirs, $ours, '<')) {
        $problems[] = "{$relative}: claims version {$ours}, but WooCommerce's template is {$theirs}.";
    }

    $checked++;
}

if ($checked === 0) {
    $problems[] = 'No template of the theme was found under '.$theme.'.';
}

if ($problems !== []) {
    WP_CLI::error("Templates the theme replaces:\n- ".implode("\n- ", $problems));
}

WP_CLI::success("The theme's {$checked} templates of WooCommerce are written from the current versions.");
