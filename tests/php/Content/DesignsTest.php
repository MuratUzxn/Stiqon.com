<?php

namespace Stiqon\Shop\Tests\Content;

use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Content\Designs;

/**
 * Which designs the shop shows, and which filter values of the list it accepts.
 */
final class DesignsTest extends TestCase
{
    public function test_a_published_design_with_its_image_and_its_release_is_shown(): void
    {
        $this->assertTrue(Designs::shown('publish', true, '1'));
        $this->assertNull(Designs::reason('publish', true, '1'));
    }

    public function test_each_condition_alone_keeps_a_design_back(): void
    {
        $this->assertFalse(Designs::shown('draft', true, '1'));
        $this->assertFalse(Designs::shown('publish', false, '1'));
        $this->assertFalse(Designs::shown('publish', true, ''));
    }

    public function test_only_a_published_design_counts(): void
    {
        foreach (['draft', 'pending', 'private', 'future', 'trash', 'auto-draft', ''] as $status) {
            $this->assertFalse(Designs::shown($status, true, '1'), $status);
        }
    }

    public function test_only_the_release_written_by_the_form_counts(): void
    {
        // The box stores "1" or nothing; anything else is not a release someone gave.
        foreach (['', '0', 'yes', 'ja', '1 ', ' 1', '01', null, false, [], ['1']] as $value) {
            $this->assertFalse(Designs::shown('publish', true, $value), var_export($value, true));
        }

        $this->assertTrue(Designs::shown('publish', true, 1));
    }

    public function test_the_administration_is_told_the_first_reason(): void
    {
        $this->assertSame('nicht veröffentlicht', Designs::reason('draft', false, ''));
        $this->assertSame('das Vorschaubild fehlt', Designs::reason('publish', false, ''));
        $this->assertSame('nicht freigegeben', Designs::reason('publish', true, ''));
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
