<?php

namespace App\Support;

/**
 * Short, page-unique ids for inline SVG.
 *
 * Many mockups share one page, and each needs its own clip paths and
 * gradients. A counter keeps the markup deterministic (no random ids), which
 * keeps responses cacheable and diffs readable.
 */
final class Uid
{
    private static int $counter = 0;

    public static function next(string $prefix = 'u'): string
    {
        return $prefix.(++self::$counter);
    }

    public static function reset(): void
    {
        self::$counter = 0;
    }
}
