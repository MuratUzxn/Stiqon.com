<?php

namespace Tests\Feature\Storefront;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_loads(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Stiqon');
    }

    public function test_health_endpoint_responds(): void
    {
        $this->get('/up')->assertOk();
    }
}
