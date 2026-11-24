<?php

namespace Stiqon\Shop;

/**
 * Small helpers for writing markup.
 */
final class Html
{
    /**
     * A class attribute value from a list: plain entries are always used,
     * "class" => condition entries only when the condition holds.
     *
     * @param  array<int|string, string|bool|null>  $classes
     */
    public static function classes(array $classes): string
    {
        $list = [];

        foreach ($classes as $key => $value) {
            if (is_int($key)) {
                if (is_string($value) && trim($value) !== '') {
                    $list[] = trim($value);
                }
            } elseif ($value) {
                $list[] = $key;
            }
        }

        return implode(' ', $list);
    }
}
