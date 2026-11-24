<?php
/**
 * The theme and the plugin must survive being switched on in either order: a
 * shop owner may well activate the theme first and the plugin afterwards.
 *
 *   tools/dev/wp.sh eval-file tests/integration/activation-order.php
 */

$plugin = 'stiqon-shop/stiqon-shop.php';

$run = static function (string $command): array {
    return (array) WP_CLI::runcommand($command, ['return' => 'all', 'exit_error' => false, 'launch' => true]);
};

$problems = [];

// Theme active, plugin off, then the plugin is switched on.
$run('plugin deactivate stiqon-shop');

$alone = $run('eval "echo \'loaded\';"');

if (($alone['return_code'] ?? 1) !== 0 || trim((string) ($alone['stdout'] ?? '')) !== 'loaded') {
    $problems[] = 'WordPress does not load with the theme active and the plugin switched off: '.trim((string) ($alone['stderr'] ?? ''));
}

$activate = $run('plugin activate stiqon-shop');

if (($activate['return_code'] ?? 1) !== 0) {
    $problems[] = 'Activating the plugin while the theme is active fails: '.trim((string) ($activate['stderr'] ?? ''));
}

// Plugin active, theme switched away and back.
$away = $run('theme activate twentytwentyfive');
$back = $run('theme activate stiqon');

if (($away['return_code'] ?? 1) !== 0 || ($back['return_code'] ?? 1) !== 0) {
    $problems[] = 'Switching the theme away and back fails: '.trim((string) ($away['stderr'] ?? '').' '.(string) ($back['stderr'] ?? ''));
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
