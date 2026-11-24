<?php

namespace Tests\Feature\Storefront;

use Tests\TestCase;

/**
 * The sample sheet is a working document for agreeing on the look. It must
 * never be reachable on a production installation.
 */
class SampleSheetTest extends TestCase
{
    public function test_it_renders_while_developing(): void
    {
        $this->get('/musterbogen')
            ->assertOk()
            ->assertSee('Musterbogen');
    }

    public function test_it_does_not_exist_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->get('/musterbogen')->assertNotFound();
    }
}
