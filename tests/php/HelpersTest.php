<?php

namespace Stiqon\Shop\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * The theme draws its views through three functions. A name they do not know
 * stops the page while developing, and costs a live shop one view – not the page.
 */
final class HelpersTest extends TestCase
{
    private string $log;

    private string|false $previousLog;

    protected function setUp(): void
    {
        $this->log = (string) tempnam(sys_get_temp_dir(), 'stiqon-log-');
        $this->previousLog = ini_set('error_log', $this->log);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['stiqon_test_environment']);
        ini_set('error_log', (string) $this->previousLog);
        @unlink($this->log);
    }

    public function test_while_developing_an_unknown_name_stops_the_page(): void
    {
        $GLOBALS['stiqon_test_environment'] = 'local';

        $this->expectException(InvalidArgumentException::class);

        stiqon_sticker(['motif' => 'gibt-es-nicht']);
    }

    public function test_on_a_live_shop_the_one_view_is_left_out(): void
    {
        $GLOBALS['stiqon_test_environment'] = 'production';

        $this->assertSame('', stiqon_sticker(['shape' => 'dreieck']));
        $this->assertSame('', stiqon_flyer(['format' => 'a3']));
        $this->assertStringContainsString('<svg', stiqon_sticker(['shape' => 'oval']), 'Known names still render.');
    }

    public function test_on_a_live_shop_a_card_with_an_unknown_look_keeps_its_content(): void
    {
        $GLOBALS['stiqon_test_environment'] = 'production';

        $card = stiqon_stage('<svg></svg>', 'sand', 'shrink-0', 'panorama');

        $this->assertStringContainsString('<svg></svg>', $card);
        $this->assertStringContainsString('bg-mist', $card);
        $this->assertStringContainsString('aspect-square', $card);
        $this->assertStringContainsString('shrink-0', $card);
    }

    public function test_the_reason_is_logged_once_however_often_it_repeats(): void
    {
        $GLOBALS['stiqon_test_environment'] = 'production';

        stiqon_sticker(['motif' => 'steht-nur-im-test']);
        stiqon_sticker(['motif' => 'steht-nur-im-test']);
        stiqon_sticker(['motif' => 'steht-nur-im-test']);

        $this->assertSame(1, substr_count((string) file_get_contents($this->log), 'Unknown sticker motif [steht-nur-im-test]'));
    }
}
