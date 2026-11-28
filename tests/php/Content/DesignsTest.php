<?php

namespace Stiqon\Shop\Tests\Content;

use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Content\Designs;

/**
 * Which designs the shop shows, and which filter values of the list it accepts.
 */
final class DesignsTest extends TestCase
{
    public function test_a_published_design_with_its_image_and_that_image_released_is_shown(): void
    {
        $this->assertTrue(Designs::shown('publish', 42, '42'));
        $this->assertNull(Designs::reason('publish', 42, '42'));
        $this->assertTrue(Designs::shown('publish', 42, 42));
    }

    public function test_each_condition_alone_keeps_a_design_back(): void
    {
        $this->assertFalse(Designs::shown('draft', 42, '42'));
        $this->assertFalse(Designs::shown('publish', 0, '42'));
        $this->assertFalse(Designs::shown('publish', 42, ''));
    }

    public function test_only_a_published_design_counts(): void
    {
        foreach (['draft', 'pending', 'private', 'future', 'trash', 'auto-draft', ''] as $status) {
            $this->assertFalse(Designs::shown($status, 42, '42'), $status);
        }
    }

    public function test_a_release_holds_for_the_image_that_was_checked_only(): void
    {
        // A new picture – exchanged, or the first one of a design released without – was not checked.
        foreach (['41', '1', '0', '042', '42 ', ' 42', 'yes', null, false, true, [], ['42'], 42.0] as $value) {
            $this->assertFalse(Designs::shown('publish', 42, $value), var_export($value, true));
        }

        $this->assertFalse(Designs::shown('publish', 0, '0'), 'released without a picture');
    }

    public function test_the_administration_is_told_the_first_reason(): void
    {
        $this->assertSame('nicht veröffentlicht', Designs::reason('draft', 0, ''));
        $this->assertSame('das Vorschaubild fehlt', Designs::reason('publish', 0, '0'));
        $this->assertSame('nicht freigegeben', Designs::reason('publish', 42, ''));
        $this->assertSame('neues Vorschaubild, nicht freigegeben', Designs::reason('publish', 43, '42'));
    }

    public function test_a_filter_is_one_the_list_offers_or_none(): void
    {
        $offered = ['sticker', 'flyer'];

        $this->assertSame('flyer', Designs::pick('flyer', $offered));
        $this->assertNull(Designs::pick('plakat', $offered));
        $this->assertNull(Designs::pick('Flyer', $offered));
        $this->assertNull(Designs::pick('', $offered));
        $this->assertNull(Designs::pick(null, $offered));
        $this->assertNull(Designs::pick(['flyer'], $offered));
        $this->assertNull(Designs::pick('flyer', []));
    }
}
