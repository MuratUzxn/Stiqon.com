<?php

namespace Tests\Feature\Storefront;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // What visitors of a production installation see.
        config(['app.debug' => false]);
    }

    public function test_an_unknown_address_shows_the_shop_frame_with_ways_onward(): void
    {
        $this->get('/gibt-es-nicht')
            ->assertNotFound()
            ->assertSee('Diese Seite gibt es nicht.')
            ->assertSee('Zum Inhalt springen')
            ->assertSee('Zur Startseite')
            ->assertSee('href="'.route('area.show', 'sticker').'"', false);
    }

    public function test_an_expired_session_is_explained(): void
    {
        Route::get('/_test/abgelaufen', fn () => abort(419));

        $this->get('/_test/abgelaufen')
            ->assertStatus(419)
            ->assertSee('Die Sitzung ist abgelaufen.');
    }

    public function test_a_server_error_page_stands_on_its_own(): void
    {
        Route::get('/_test/fehler', fn () => throw new RuntimeException('Interna, die niemand sehen soll'));

        $response = $this->get('/_test/fehler')->assertStatus(500);

        $response->assertSee('Da ist etwas schiefgelaufen.');
        $response->assertDontSee('Interna');
        // No scripts and no build assets: the page must work when everything else is broken.
        $response->assertDontSee('<script', false);
        $response->assertDontSee('/build/', false);
    }

    public function test_maintenance_is_announced_without_the_shop_frame(): void
    {
        Route::get('/_test/wartung', fn () => abort(503));

        $this->get('/_test/wartung')
            ->assertStatus(503)
            ->assertSee('Wir sind gleich wieder da.')
            ->assertDontSee('<script', false);
    }
}
