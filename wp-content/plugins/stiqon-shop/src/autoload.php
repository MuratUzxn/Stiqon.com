<?php
/**
 * Loads the plugin's classes: Stiqon\Shop\Mockup\Uid lives in src/Mockup/Uid.php.
 *
 * The plugin ships without Composer, so it brings this small loader itself.
 * The unit tests use it as well.
 */

spl_autoload_register(static function (string $class): void {
    $prefix = 'Stiqon\\Shop\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $file = __DIR__.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';

    if (is_file($file)) {
        require $file;
    }
});
