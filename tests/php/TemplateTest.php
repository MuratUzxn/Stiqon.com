<?php

namespace Stiqon\Shop\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Stiqon\Shop\Template;

/**
 * Template names also come from data (a product's motif), so a name must never
 * reach a file outside the plugin's templates directory.
 */
final class TemplateTest extends TestCase
{
    public function test_the_plugins_own_templates_are_found(): void
    {
        $this->assertTrue(Template::exists('mockup/sticker'));
        $this->assertTrue(Template::exists('motif/siegel'));
        $this->assertTrue(Template::exists('motif/flyer/fest'));
        $this->assertFalse(Template::exists('motif/gibt-es-nicht'));
    }

    /**
     * @dataProvider namesThatLeaveTheDirectory
     */
    public function test_names_that_are_not_plain_path_segments_are_refused(string $name): void
    {
        $this->assertFalse(Template::exists($name));

        $this->expectException(InvalidArgumentException::class);

        Template::render($name);
    }

    /**
     * Every one of these would name an existing PHP file if it were accepted.
     *
     * @return array<string, array{string}>
     */
    public static function namesThatLeaveTheDirectory(): array
    {
        $templates = dirname(__DIR__, 2).'/wp-content/plugins/stiqon-shop/templates';

        return [
            'parent directory' => ['../src/Plugin'],
            'parent directory inside the path' => ['mockup/../../src/Plugin'],
            'absolute path' => [$templates.'/mockup/sticker'],
            'trailing line break' => ["mockup/sticker\n"],
            'upper case' => ['Mockup/Sticker'],
            'file extension' => ['mockup/sticker.php'],
            'empty segment' => ['mockup//sticker'],
            'null byte' => ["mockup/sticker\0"],
            'empty' => [''],
        ];
    }

    public function test_an_exception_inside_a_template_leaves_no_output_behind(): void
    {
        $level = ob_get_level();

        try {
            Template::render('motif/gibt-es-nicht');
            $this->fail('An unknown template was rendered.');
        } catch (InvalidArgumentException) {
            $this->assertSame($level, ob_get_level());
        }
    }
}
