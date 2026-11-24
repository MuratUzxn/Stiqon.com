<?php

namespace Tests\Feature\Storefront;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_shows_the_main_action_and_both_areas(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Werbemittel, die')
            ->assertSee('Motiv wählen')
            ->assertSee('href="'.route('area.show', 'sticker').'"', false)
            ->assertSee('href="'.route('area.show', 'flyer').'"', false);
    }

    public function test_home_page_offers_a_skip_link_to_the_content(): void
    {
        $this->get('/')
            ->assertSee('href="#inhalt"', false)
            ->assertSee('id="inhalt"', false);
    }

    public function test_pages_are_not_indexable_before_launch(): void
    {
        $this->get('/')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_health_endpoint_responds(): void
    {
        $this->get('/up')->assertOk();
    }
}
