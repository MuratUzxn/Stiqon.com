<?php
/**
 * Recomputes, for the entries in search-cases.json, the fields search compares
 * ("t", "k", "w", "m") from the fields before them. Run it after a change to
 * how the plugin computes those fields (Search\Entry, Search\Text):
 *
 *   php tests/fixtures/update-search-cases.php
 *
 * Everything else in the file – the texts, the queries and what they are
 * expected to find – is written by hand and left as it is. Afterwards both
 * sides are due: composer test, and the browser test search-logic.spec.js.
 */

require dirname(__DIR__, 2).'/wp-content/plugins/stiqon-shop/src/autoload.php';

use Stiqon\Shop\Search\Entry;

$file = __DIR__.'/search-cases.json';
$cases = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

$cases['entries'] = array_map(
    static fn (array $entry): array => array_intersect_key($entry, array_flip(['kind', 'n', 's', 'keywords']))
        + Entry::searchable($entry['kind'], $entry['n'], $entry['s'], $entry['keywords']),
    $cases['entries'],
);

file_put_contents($file, json_encode($cases, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");

echo 'Updated '.count($cases['entries'])." entries in tests/fixtures/search-cases.json.\n";
