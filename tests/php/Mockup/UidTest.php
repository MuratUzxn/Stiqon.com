<?php

namespace Stiqon\Shop\Tests\Mockup;

use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Mockup\Uid;

final class UidTest extends TestCase
{
    protected function setUp(): void
    {
        Uid::reset();
    }

    public function test_ids_count_up_and_carry_their_prefix(): void
    {
        $this->assertSame('st1', Uid::next('st'));
        $this->assertSame('fl2', Uid::next('fl'));
        $this->assertSame('u3', Uid::next());
    }

    public function test_no_id_repeats_within_a_page(): void
    {
        $ids = array_map(static fn (): string => Uid::next('st'), range(1, 200));

        $this->assertCount(200, array_unique($ids));
    }
}
