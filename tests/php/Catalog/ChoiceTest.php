<?php

namespace Stiqon\Shop\Tests\Catalog;

use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Catalog\Choice;

/**
 * Which choice a product settles on when not every combination of its options
 * exists. The product page's script follows the same rule; both are held to the
 * same cases (tests/fixtures/choice-cases.json, on the script's side
 * tests/e2e/choice-logic.spec.js).
 */
final class ChoiceTest extends TestCase
{
    public function test_a_choice_settles_on_the_nearest_combination_that_exists(): void
    {
        $cases = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/fixtures/choice-cases.json'), true, 512, JSON_THROW_ON_ERROR)['cases'];

        $this->assertNotEmpty($cases);

        foreach ($cases as $case) {
            $this->assertSame($case['expect'], Choice::closest($case['combinations'], $case['wanted'], $case['fixed']), $case['why']);
        }
    }

    public function test_the_wanted_choice_keeps_its_order_and_every_option(): void
    {
        $settled = Choice::closest([['b' => '2', 'a' => '1']], ['a' => '9', 'b' => '9', 'c' => '9'], ['a']);

        // "c" is no option of the variation: nothing restricts it.
        $this->assertSame(['a' => '1', 'b' => '2', 'c' => '9'], $settled);
        $this->assertSame(['a', 'b', 'c'], array_keys($settled));
    }
}
