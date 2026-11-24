<?php

namespace Tests\Feature\Storefront;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AreaPagesTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function areas(): array
    {
        return [
            'sticker' => ['sticker', 'Sticker'],
            'flyer' => ['flyer', 'Flyer'],
        ];
    }

    #[DataProvider('areas')]
    public function test_an_area_page_renders_and_marks_itself_in_the_navigation(string $slug, string $name): void
    {
        $response = $this->get('/'.$slug)->assertOk();

        $response->assertSee('<h1 class="text-display mt-5">'.$name.'</h1>', false);

        // Exactly the links to this area carry aria-current (desktop and mobile navigation).
        preg_match_all('/<a\b[^>]*aria-current="page"[^>]*>/', $response->getContent(), $current);

        $this->assertCount(2, $current[0]);

        foreach ($current[0] as $link) {
            $this->assertStringContainsString('href="'.route('area.show', $slug).'"', $link);
        }
    }

    public function test_an_unknown_area_is_not_found(): void
    {
        $this->get('/poster')->assertNotFound();
    }

    public function test_the_home_page_marks_no_area_as_current(): void
    {
        $this->get('/')->assertDontSee('aria-current="page"', false);
    }
}
