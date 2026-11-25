<?php
/**
 * The theme and the plugin must survive being switched on in either order: a
 * shop owner may well activate the theme first and the plugin afterwards.
 *
 *   tools/dev/wp.sh eval-file tests/integration/activation-order.php
 */

$run = static fn (string $command): array => (array) WP_CLI::runcommand($command, ['return' => 'all', 'exit_error' => false, 'launch' => true]);
$failed = static fn (array $result): bool => ($result['return_code'] ?? 1) !== 0;
$said = static fn (array $result): string => trim((string) ($result['stderr'] ?? ''));

// Any other theme will do to switch away to; WordPress ships with a few.
$others = array_values(array_diff(array_keys(wp_get_themes()), ['stiqon']));

if ($others === []) {
    WP_CLI::error('There is no second theme installed to switch to.');
}

$problems = [];

try {
    // Theme active, plugin off, then the plugin is switched on.
    $run('plugin deactivate stiqon-shop');

    $alone = $run('eval "echo \'loaded\';"');

    if ($failed($alone) || trim((string) ($alone['stdout'] ?? '')) !== 'loaded') {
        $problems[] = 'WordPress does not load with the theme active and the plugin switched off: '.$said($alone);
    }

    $activate = $run('plugin activate stiqon-shop');

    if ($failed($activate)) {
        $problems[] = 'Activating the plugin while the theme is active fails: '.$said($activate);
    }

    // Plugin active, theme switched away and back.
    $away = $run('theme activate '.$others[0]);
    $back = $run('theme activate stiqon');

    if ($failed($away) || $failed($back)) {
        $problems[] = 'Switching the theme away and back fails: '.trim($said($away).' '.$said($back));
    }
} finally {
    // Whatever happened above, the site is left with both switched on.
    $run('plugin activate stiqon-shop');
    $run('theme activate stiqon');
}

$state = $run('eval "echo wp_get_theme()->get_stylesheet().\'|\'.(function_exists(\'stiqon_sticker\') ? \'plugin\' : \'no plugin\');"');

if (trim((string) ($state['stdout'] ?? '')) !== 'stiqon|plugin') {
    $problems[] = 'After the round trip the site is not back to theme and plugin active: '.trim((string) ($state['stdout'] ?? ''));
}

if ($problems !== []) {
    WP_CLI::error_multi_line($problems);
    WP_CLI::halt(1);
}

WP_CLI::success('Theme and plugin can be activated in either order.');
