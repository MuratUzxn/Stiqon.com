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
    }

    /**
     * Absolute path of a file inside the plugin directory.
     */
    public static function path(string $relative = ''): string
    {
        return dirname((string) self::$file).($relative !== '' ? '/'.ltrim($relative, '/') : '');
    }
}
