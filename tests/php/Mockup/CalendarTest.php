<?php

namespace Stiqon\Shop\Tests\Mockup;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Guess;
use Stiqon\Shop\Catalog\Spec;
use Stiqon\Shop\Mockup\Calendar;
use Stiqon\Shop\Mockup\Sticker;
use Stiqon\Shop\Mockup\Uid;

/**
 * A calendar hangs on the wall, stands on the desk or is a year planner. Its
 * picture is the motif; the days are the calendar's own.
 */
final class CalendarTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_every_motif_renders_in_every_variant(): void
    {
        $this->assertGreaterThanOrEqual(12, count(Calendar::motifs()));

        foreach (Calendar::motifs() as $motif) {
            foreach (array_keys(Calendar::form()['values']) as $variant) {
                $svg = stiqon_mockup('calendar', compact('variant', 'motif'));

                $this->assertWellFormed($svg, "{$motif} on {$variant}");
                $this->assertStringContainsString('data-mockup="calendar"', $svg);
                $this->assertStringContainsString('data-motif-image', $svg);
                $this->assertStringContainsString('data-motif-sample', $svg);
                $this->assertStringContainsString('viewBox="0 0 120 120"', $svg);
            }
        }
    }

    public function test_the_motif_is_the_picture_on_white_paper(): void
    {
        foreach (array_keys(Calendar::VARIANTS) as $variant) {
            $calendar = new Calendar(variant: $variant, motif: 'wellen');
            $svg = $calendar->render();
            $picture = $calendar->picture;
            $box = sprintf('x="%s" y="%s" width="%s" height="%s"', $picture['x'], $picture['y'], $picture['width'], $picture['height']);

            $this->assertStringContainsString('<svg '.$box.' viewBox="0 0 100 100" preserveAspectRatio="xMidYMid slice"', $svg, $variant);
            $this->assertStringContainsString('<rect '.$box.' fill="#fff" />', $svg, "{$variant}: white paper under the visitor's motif");
            $this->assertStringContainsString('<image '.$box.' preserveAspectRatio="xMidYMid slice"', $svg, $variant);
            $this->assertSame(1, substr_count($svg, 'data-motif-image'), "{$variant}: one picture");

            // Picture and days lie on the page.
            foreach (['picture' => $picture, 'grid' => $calendar->grid] as $what => $area) {
                $this->assertGreaterThanOrEqual($calendar->sheet['x'], $area['x'], "{$variant}: {$what}");
                $this->assertGreaterThanOrEqual($calendar->sheet['y'], $area['y'], "{$variant}: {$what}");
                $this->assertLessThanOrEqual($calendar->sheet['x'] + $calendar->sheet['width'], $area['x'] + $area['width'], "{$variant}: {$what}");
                $this->assertLessThanOrEqual($calendar->sheet['y'] + $calendar->sheet['height'], $area['y'] + $area['height'], "{$variant}: {$what}");
            }
        }
    }

    public function test_each_variant_is_drawn_as_what_it_is(): void
    {
        $wall = new Calendar(variant: 'wall');
        $desk = new Calendar(variant: 'desk');
        $planner = new Calendar(variant: 'planner');

        // On a nail, portrait, with a month grid.
        $this->assertCount(1, $wall->fixings);
        $this->assertGreaterThan($wall->sheet['width'], $wall->sheet['height']);
        $this->assertNull($wall->face);
        $this->assertStringNotContainsString('matrix(', $wall->render());

        // A landscape page, slanted onto its stand.
        $this->assertCount(0, $desk->fixings);
        $this->assertGreaterThan($desk->sheet['height'], $desk->sheet['width']);
        $this->assertNotNull($desk->face);
        $this->assertNotNull($desk->side);
        $this->assertStringContainsString('transform="matrix('.implode(' ', $desk->face).')"', $desk->render());
        $this->assertStringContainsString('-stand)', $desk->render());

        // Landscape, pinned at its top corners, a column for each month.
        $this->assertCount(2, $planner->fixings);
        $this->assertGreaterThan($planner->sheet['height'], $planner->sheet['width']);
        $this->assertNull($planner->face);
        $this->assertSame(12, count(array_filter($planner->year(), static fn (array $cell): bool => $cell['kind'] === 'heading')));
    }

    public function test_the_month_has_seven_columns_and_five_rows_of_days(): void
    {
        foreach (['wall', 'desk'] as $variant) {
            $calendar = new Calendar(variant: $variant);
            $cells = $calendar->month();
            $kinds = array_count_values(array_column($cells, 'kind'));

            $this->assertCount(35, $cells, $variant);
            $this->assertCount(7, array_unique(array_column($cells, 'x')), $variant);
            $this->assertCount(5, array_unique(array_column($cells, 'y')), $variant);
            $this->assertSame(4, $kinds['blank'], "{$variant}: 31 days in 35 cells");
            $this->assertSame(1, $kinds['mark'], $variant);
            $this->assertSame(1, $kinds['note'], $variant);
            $this->assertGreaterThanOrEqual(4, $kinds['sunday'], $variant);
            $this->assertCount(7, $calendar->weekdays(), $variant);

            foreach ($cells as $cell) {
                $this->assertGreaterThan(0, $cell['width']);
                $this->assertGreaterThan(0, $cell['height']);
                $this->assertLessThanOrEqual($calendar->grid['x'] + $calendar->grid['width'] + 0.01, $cell['x'] + $cell['width'], $variant);
                $this->assertLessThanOrEqual($calendar->grid['y'] + $calendar->grid['height'] + 0.01, $cell['y'] + $cell['height'], $variant);
            }
        }
    }

    public function test_the_planner_shows_every_day_of_the_year(): void
    {
        $planner = new Calendar(variant: 'planner');
        $days = array_filter($planner->year(), static fn (array $cell): bool => $cell['kind'] !== 'heading');
        $kinds = array_count_values(array_column($days, 'kind'));

        $this->assertCount(365, $days);
        $this->assertGreaterThan(80, $kinds['weekend']);
        $this->assertArrayHasKey('holiday', $kinds);
        $this->assertArrayHasKey('event', $kinds);
        $this->assertArrayHasKey('mark', $kinds);

        foreach ($days as $cell) {
            $this->assertLessThanOrEqual($planner->grid['x'] + $planner->grid['width'] + 0.01, $cell['x'] + $cell['width']);
            $this->assertLessThanOrEqual($planner->grid['y'] + $planner->grid['height'] + 0.01, $cell['y'] + $cell['height']);
        }
    }

    public function test_cells_of_a_kind_are_drawn_as_one_path(): void
    {
        $cells = [
            ['x' => 1.0, 'y' => 2.0, 'width' => 3.0, 'height' => 4.0, 'kind' => 'day'],
            ['x' => 5.0, 'y' => 2.0, 'width' => 3.0, 'height' => 4.0, 'kind' => 'mark'],
            ['x' => 9.5, 'y' => 2.0, 'width' => 3.0, 'height' => 4.0, 'kind' => 'day'],
        ];

        $this->assertSame(['day' => 'M1 2h3v4h-3zM9.5 2h3v4h-3z', 'mark' => 'M5 2h3v4h-3z'], Calendar::paths($cells));
        $this->assertSame(
            'M1.5 2h2a0.5 0.5 0 0 1 0.5 0.5v3a0.5 0.5 0 0 1 -0.5 0.5h-2a0.5 0.5 0 0 1 -0.5 -0.5v-3a0.5 0.5 0 0 1 0.5 -0.5z',
            Calendar::paths([$cells[0]], 0.5)['day'],
        );
    }

    public function test_a_calendar_stays_light_on_a_page_of_many_drawings(): void
    {
        // A gallery of twenty stickers is about 800 elements; a calendar, even the
        // planner with a cell for each day of the year, weighs about as much as a sticker.
        foreach (array_keys(Calendar::VARIANTS) as $variant) {
            foreach (Calendar::motifs() as $motif) {
                $svg = stiqon_mockup('calendar', compact('variant', 'motif'));

                $this->assertLessThanOrEqual(50, preg_match_all('/<[a-zA-Z]/', $svg), "{$variant} with {$motif}");
                $this->assertLessThan(16 * 1024, strlen($svg), "{$variant} with {$motif}");
            }
        }
    }

    public function test_every_variant_fits_the_square_card(): void
    {
        $wall = new Calendar(variant: 'wall');
        $planner = new Calendar(variant: 'planner');
        $desk = new Calendar(variant: 'desk');

        foreach (['wall' => $wall, 'planner' => $planner] as $variant => $calendar) {
            $this->assertGreaterThanOrEqual(10.0, $calendar->sheet['x'], $variant);
            $this->assertGreaterThanOrEqual(10.0, $calendar->sheet['y'], $variant);
            $this->assertLessThanOrEqual(110.0, $calendar->sheet['x'] + $calendar->sheet['width'], $variant);
            $this->assertLessThanOrEqual(108.0, $calendar->sheet['y'] + $calendar->sheet['height'], $variant);
        }

        // The nail sits above the wall calendar and inside the card; the planner's pins sit on its corners.
        [[$nailX, $nailY]] = $wall->fixings;
        $this->assertSame(60.0, $nailX);
        $this->assertGreaterThanOrEqual(10.0, $nailY);
        $this->assertLessThan($wall->sheet['y'] - 5, $nailY);

        foreach ($planner->fixings as [$x, $y]) {
            $this->assertGreaterThan($planner->sheet['x'], $x);
            $this->assertLessThan($planner->sheet['x'] + $planner->sheet['width'], $x);
            $this->assertGreaterThan($planner->sheet['y'], $y);
            $this->assertLessThan($planner->picture['y'], $y);
        }

        // The desk calendar's page, placed by its matrix, and the foot of its stand.
        [$a, $b, $c, $d, $e, $f] = $desk->face;
        $corners = [];

        foreach ([[0, 0], [$desk->sheet['width'], 0], [0, $desk->sheet['height']], [$desk->sheet['width'], $desk->sheet['height']]] as [$x, $y]) {
            $corners[] = [$a * $x + $c * $y + $e, $b * $x + $d * $y + $f];
        }

        foreach (array_merge($corners, $desk->side) as [$x, $y]) {
            $this->assertGreaterThanOrEqual(10.0, $x);
            $this->assertLessThanOrEqual(110.0, $x);
            $this->assertGreaterThanOrEqual(10.0, $y);
            $this->assertLessThanOrEqual(108.0, $y);
        }

        $this->assertSame([$e, $f], $desk->side[0], 'The stand starts at the ridge.');
        $this->assertSame([round($c * $desk->sheet['height'] + $e, 2), round($d * $desk->sheet['height'] + $f, 2)], $desk->side[1], 'The page ends at its foot.');
        $this->assertLessThan($desk->side[1][0], $desk->side[2][0], 'The stand\'s foot lies behind the page\'s, to the left …');
        $this->assertLessThan($desk->side[1][1], $desk->side[2][1], '… and higher.');
    }

    public function test_the_drawing_says_what_it_shows(): void
    {
        $this->assertSame('Kalender', Calendar::label());
        $this->assertStringContainsString('aria-label="Wandkalender"', stiqon_mockup('calendar'));
        $this->assertStringContainsString('aria-label="Tischkalender"', stiqon_mockup('calendar', ['variant' => 'desk']));
        $this->assertStringContainsString('aria-label="Jahresplaner"', stiqon_mockup('calendar', ['variant' => 'planner']));
        $this->assertStringContainsString('aria-label="Fotokalender 2027"', stiqon_mockup('calendar', ['label' => 'Fotokalender 2027']));
    }

    /**
     * @dataProvider unknownValues
     */
    public function test_unknown_values_are_rejected(callable $build, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $build();
    }

    /**
     * @return array<string, array{callable, string}>
     */
    public static function unknownValues(): array
    {
        return [
            'variant' => [static fn () => new Calendar(variant: 'pocket'), 'Unknown calendar variant [pocket].'],
            'variant from a spec' => [static fn () => Calendar::fromArray(['variant' => 'Wandkalender']), 'Unknown calendar variant [Wandkalender].'],
            'motif' => [static fn () => new Calendar(motif: 'gibt-es-nicht'), 'Unknown calendar motif [gibt-es-nicht].'],
            'motif outside the directory' => [static fn () => new Calendar(motif: '../mockup/flyer'), 'Unknown calendar motif'],
            'flyer motif' => [static fn () => Calendar::fromArray(['motif' => 'fest']), 'Unknown calendar motif [fest].'],
        ];
    }

    public function test_without_values_the_first_variant_and_motif_are_drawn(): void
    {
        $calendar = Calendar::fromArray([]);

        $this->assertSame(array_key_first(Calendar::form()['values']), $calendar->variant);
        $this->assertSame('wall', $calendar->variant);
        $this->assertSame(Calendar::motifs()[0], $calendar->motif);
        $this->assertNull($calendar->label);
        $this->assertSame((new Calendar())->variant, $calendar->variant);
        $this->assertSame((new Calendar())->motif, $calendar->motif);
    }

    public function test_values_of_other_kinds_are_ignored(): void
    {
        $calendar = Calendar::fromArray(['format' => 'a0', 'shape' => 'dreieck', 'material' => 'gold', 'gloss' => true, 'stack' => true, 'open' => true, 'variant' => 'planner']);

        $this->assertSame('planner', $calendar->variant);
        $this->assertWellFormed($calendar->render(), 'foreign keys');
        $this->assertSame('wall', Calendar::fromArray(['format' => 'a0', 'gloss' => true])->variant);
    }

    public function test_ids_stay_unique_and_references_resolve_when_calendars_share_a_page(): void
    {
        $drawings = [
            stiqon_mockup('calendar'),
            stiqon_mockup('calendar', ['variant' => 'desk', 'motif' => 'siegel']),
            stiqon_mockup('calendar', ['variant' => 'planner', 'motif' => 'wellen']),
            stiqon_mockup('calendar', ['variant' => 'desk', 'motif' => 'siegel']),
        ];

        foreach ($drawings as $svg) {
            $this->assertReferencesResolve($svg);
        }

        $this->assertIdsAreUnique(implode('', $drawings), 'cl', 4);
    }

    public function test_keys_and_form_agree(): void
    {
        $keys = Calendar::keys();
        $form = Calendar::form();

        $this->assertSame(['variant', 'motif'], array_keys($keys));
        $this->assertSame('variant', $form['key']);
        $this->assertSame('Ausführung', $form['label']);
        $this->assertSame(['wall' => 'Wandkalender', 'desk' => 'Tischkalender', 'planner' => 'Jahresplaner'], $form['values']);
        $this->assertSame(array_keys($form['values']), $keys[$form['key']]);
        $this->assertSame(Calendar::motifs(), $keys['motif']);
        $this->assertSame(Sticker::motifs(), Calendar::motifs());

        $names = Calendar::names();
        $this->assertSame(['desk', 'planner', 'wall'], array_keys($names['form']), 'Desk and planner are asked before the wall.');
        $this->assertSame([], $names['options']);
        $this->assertSame([], $names['switches']);
    }

    public function test_names_say_a_product_is_a_calendar_and_which(): void
    {
        foreach (['Wandkalender', 'Tischkalender', 'Jahresplaner', 'Wandplaner', 'Streifenkalender'] as $name) {
            $this->assertSame('calendar', Guess::type($name), $name);
        }

        $this->assertSame('calendar', Guess::type('DIN A3 hoch', 'Kalender'));

        $this->assertSame(['variant' => 'wall'], Guess::product('calendar', 'Wandkalender'));
        $this->assertSame(['variant' => 'wall'], Guess::product('calendar', 'Monatskalender A3'));
        $this->assertSame(['variant' => 'wall'], Guess::product('calendar', 'Streifenkalender'));
        $this->assertSame(['variant' => 'wall'], Guess::product('calendar', 'Kalender'));
        $this->assertSame(['variant' => 'desk'], Guess::product('calendar', 'Tischkalender'));
        $this->assertSame(['variant' => 'desk'], Guess::product('calendar', 'Aufstellkalender quer'));
        $this->assertSame(['variant' => 'planner'], Guess::product('calendar', 'Jahresplaner'));
        $this->assertSame(['variant' => 'planner'], Guess::product('calendar', 'Wandplaner'));
        $this->assertSame(['variant' => 'planner'], Guess::product('calendar', 'Jahreskalender'));
        $this->assertSame([], Guess::product('calendar', 'Fotodruck'));

        $this->assertSame([], Guess::option('calendar', '13 Monatsblätter'));
        $this->assertSame([], Guess::option('calendar', '300 g Bilderdruck'));
    }

    public function test_whatever_is_resolved_can_be_drawn(): void
    {
        foreach (['Wandkalender' => 'wall', 'Tischkalender' => 'desk', 'Jahresplaner' => 'planner', 'Wandplaner' => 'planner', 'Streifenkalender' => 'wall'] as $name => $variant) {
            $resolved = Spec::resolve(['name' => $name], [['name' => '250 g Bilderdruck'], ['name' => 'Wire-O-Bindung']]);

            $this->assertSame('calendar', $resolved['type'], $name);
            $this->assertSame($variant, $resolved['spec']['variant'], $name);
            $this->assertContains($resolved['spec']['motif'], Calendar::motifs(), $name);
            $this->assertWellFormed(stiqon_mockup($resolved['type'], $resolved['spec']), $name);
        }
    }

    public function test_text_from_outside_cannot_break_out_of_an_attribute(): void
    {
        $hostile = 'A "B" <c> & \'d\'';
        $escaped = 'A &quot;B&quot; &lt;c&gt; &amp; &#039;d&#039;';

        foreach (array_keys(Calendar::VARIANTS) as $variant) {
            foreach (['label' => stiqon_mockup('calendar', ['variant' => $variant, 'label' => $hostile]), 'class' => stiqon_mockup('calendar', ['variant' => $variant], $hostile)] as $what => $markup) {
                $this->assertStringContainsString($escaped, $markup, "{$variant} {$what}");
                $this->assertStringNotContainsString('<c>', $markup, "{$variant} {$what}");
            }
        }
    }

    public function test_a_width_class_from_the_caller_replaces_the_full_width_default(): void
    {
        $this->assertMatchesRegularExpression('/class="[^"]*\bw-full\b[^"]*\blift\b/', stiqon_mockup('calendar'));
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bw-full\b/', stiqon_mockup('calendar', ['variant' => 'desk'], 'md:w-1/2'));
    }

    /**
     * Every url(#…) and href="#…" of one drawing points to an id of that drawing.
     */
    private function assertReferencesResolve(string $svg): void
    {
        preg_match_all('/\sid="([^"]+)"/', $svg, $ids);
        preg_match_all('/url\(#([^)]+)\)|href="#([^"]+)"/', $svg, $references);

        $referenced = array_unique(array_filter(array_merge($references[1], $references[2])));

        $this->assertNotEmpty($referenced);
        $this->assertSame([], array_values(array_diff($referenced, $ids[1])), 'References point nowhere.');
    }

    /**
     * No id repeats on a page, and every id starts with the uid of its drawing.
     */
    private function assertIdsAreUnique(string $page, string $prefix, int $drawings): void
    {
        preg_match_all('/\sid="([^"]+)"/', $page, $ids);

        $this->assertGreaterThanOrEqual($drawings, count($ids[1]));
        $this->assertSame([], array_keys(array_filter(array_count_values($ids[1]), static fn (int $count): bool => $count > 1)), 'Ids repeat.');

        foreach ($ids[1] as $id) {
            $this->assertMatchesRegularExpression('/\A'.$prefix.'\d+-/', $id);
        }
    }

    private function assertWellFormed(string $svg, string $what): void
    {
        $previous = libxml_use_internal_errors(true);
        // The markup is HTML, checked here with an XML parser: Alpine's attributes carry a
        // colon without declaring a namespace, and two attributes come without a value.
        $xml = preg_replace('/\sx-[a-z]+:[a-z-]+=/', ' data-x=', $svg);
        $xml = preg_replace('/\s(x-cloak|data-motif-image|data-motif-sample)(?=[\s\/>])/', ' $1=""', $xml);
        $document = simplexml_load_string($xml);
        $errors = array_map(static fn ($error): string => trim($error->message), libxml_get_errors());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($document, "Not well-formed: {$what} – ".implode('; ', $errors));
        $this->assertSame('svg', $document->getName(), $what);
    }
}
