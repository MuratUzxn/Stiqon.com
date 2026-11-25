<?php

namespace Stiqon\Shop;

/**
 * Entry point of the plugin: wires its parts into WordPress.
 */
final class Plugin
{
    public const VERSION = '0.1.0';

    private static ?string $file = null;

    public static function boot(string $file): void
    {
        self::$file = $file;

        register_activation_hook($file, [Setup\Defaults::class, 'apply']);

        Catalog\AddToCart::register();
        Catalog\SupplierCataloguePage::register();
    }

    /**
     * Whether a mistake in a template or in stored data should stop the page.
     * It should while developing, so it is noticed; a live shop has to stay up.
     */
    public static function strict(): bool
    {
        return function_exists('wp_get_environment_type')
            && in_array(wp_get_environment_type(), ['local', 'development'], true);
    }

    /**
     * Absolute path of a file inside the plugin directory.
     */
    public static function path(string $relative = ''): string
    {
        return dirname((string) self::$file).($relative !== '' ? '/'.ltrim($relative, '/') : '');
    }
}
