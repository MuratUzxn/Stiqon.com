<?php
/**
 * The plugin's privacy defaults are a starting point. A setting the owner
 * changes afterwards has to survive switching the plugin off and on again.
 *
 *   tools/dev/wp.sh eval-file tests/integration/defaults.php
 */

use Stiqon\Shop\Setup\Defaults;

$run = static fn (string $command): array => (array) WP_CLI::runcommand($command, ['return' => 'all', 'exit_error' => false, 'launch' => true]);

$problems = [];
$applied = (array) get_option(Defaults::APPLIED, []);

if (array_diff(array_keys(Defaults::PRIVACY), $applied) !== []) {
    $problems[] = 'Not every default is recorded as applied: '.implode(', ', array_diff(array_keys(Defaults::PRIVACY), $applied));
}

$option = 'show_avatars';
$default = Defaults::PRIVACY[$option];

try {
    // The owner decides otherwise …
    $run("option update {$option} 1");
    // … and later the plugin is switched off and on, as happens during an update.
    $run('plugin deactivate stiqon-shop');
    $run('plugin activate stiqon-shop');

    $after = trim((string) ($run("option get {$option}")['stdout'] ?? ''));

    if ($after !== '1') {
        $problems[] = "Reactivating the plugin put {$option} back to \"{$after}\" although the owner had changed it.";
    }
} finally {
    $run('plugin activate stiqon-shop');
    $run("option update {$option} {$default}");
}

if ($problems !== []) {
    WP_CLI::error_multi_line($problems);
    WP_CLI::halt(1);
}

WP_CLI::success('The defaults are applied once; later changes by the owner stay.');
