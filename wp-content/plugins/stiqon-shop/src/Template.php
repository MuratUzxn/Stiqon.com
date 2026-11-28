<?php

namespace Stiqon\Shop;

use InvalidArgumentException;

/**
 * Renders the plugin's own templates (the files under templates/).
 */
final class Template
{
    /**
     * Markup of templates/<name>.php, with $variables as local variables.
     *
     * @param  array<string, mixed>  $variables
     */
    public static function render(string $name, array $variables = []): string
    {
        $level = ob_get_level();
        ob_start();

        try {
            self::output($name, $variables);

            return (string) ob_get_clean();
        } finally {
            // An exception inside a template must not leave half a page in the buffer.
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
    }

    /**
     * Prints templates/<name>.php in place.
     *
     * @param  array<string, mixed>  $variables
     */
    public static function output(string $name, array $variables = []): void
    {
        if (! self::exists($name)) {
            throw new InvalidArgumentException("Unknown template [{$name}].");
        }

        (static function (string $__file, array $__variables): void {
            extract($__variables, EXTR_SKIP);

            include $__file;
        })(self::file($name), $variables);
    }

    /**
     * Names come from data as well (a product's motif), so only plain
     * lowercase path segments are accepted – nothing that could leave templates/.
     * The pattern ends in \z: a plain $ would also accept a trailing line break.
     */
    public static function exists(string $name): bool
    {
        return preg_match('~\A[a-z0-9-]+(/[a-z0-9-]+)*\z~', $name) === 1 && is_file(self::file($name));
    }

    /**
     * Names of the templates directly inside a directory, sorted.
     *
     * @return list<string>
     */
    public static function names(string $directory): array
    {
        $names = array_map(
            static fn (string $file): string => basename($file, '.php'),
            glob(dirname(__DIR__).'/templates/'.$directory.'/*.php') ?: [],
        );

        sort($names);

        return $names;
    }

    private static function file(string $name): string
    {
        return dirname(__DIR__).'/templates/'.$name.'.php';
    }
}
