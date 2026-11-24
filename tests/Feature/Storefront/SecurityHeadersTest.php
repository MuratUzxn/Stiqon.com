<?php

namespace Tests\Feature\Storefront;

use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The shop promises a strict Content Security Policy without 'unsafe-eval'.
 * That depends on both Livewire builds being published as static files; the
 * tests arrange that state themselves instead of relying on what the
 * checkout happens to contain. The browser tests check the real files.
 */
class SecurityHeadersTest extends TestCase
{
    private string $publicPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicPath = sys_get_temp_dir().'/stiqon-public-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($this->publicPath);
        $this->app->usePublicPath($this->publicPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicPath);

        parent::tearDown();
    }

    private function publishLivewireBuilds(): void
    {
        File::ensureDirectoryExists($this->publicPath.'/vendor/livewire');
        File::copy(
            base_path('vendor/livewire/livewire/dist/manifest.json'),
            $this->publicPath.'/vendor/livewire/manifest.json',
        );
    }

    private function policy(TestResponse $response): string
    {
        $policy = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($policy, 'The response carries no Content-Security-Policy header.');

        return $policy;
    }

    private function directive(string $policy, string $name): string
    {
        $this->assertSame(1, preg_match('/(?:^|; )'.preg_quote($name, '/').' ([^;]+)/', $policy, $match), "No {$name} in: {$policy}");

        return $match[1];
    }

    public function test_shop_pages_allow_scripts_only_from_this_site_with_the_request_nonce(): void
    {
        $this->publishLivewireBuilds();

        $policy = $this->policy($this->get('/')->assertOk());
        $scripts = $this->directive($policy, 'script-src');

        $this->assertMatchesRegularExpression("/^'self' 'nonce-[A-Za-z0-9+\/=_-]{20,}'$/", $scripts);
        $this->assertSame("'self'", $this->directive($policy, 'default-src'));
        $this->assertSame("'none'", $this->directive($policy, 'object-src'));
        $this->assertSame("'none'", $this->directive($policy, 'base-uri'));
        $this->assertSame("'self'", $this->directive($policy, 'form-action'));
        $this->assertSame("'none'", $this->directive($policy, 'frame-ancestors'));
        // Nothing may come from another origin.
        $this->assertStringNotContainsString('http', $policy);
        $this->assertStringNotContainsString('*', $policy);
    }

    public function test_every_script_tag_carries_the_nonce_of_the_policy(): void
    {
        $this->publishLivewireBuilds();

        $response = $this->get('/');

        preg_match("/'nonce-([^']+)'/", $this->policy($response), $nonce);
        preg_match_all('/<script\b[^>]*>/', $response->getContent(), $tags);

        $this->assertNotEmpty($tags[0], 'The page has no script tags at all.');

        foreach ($tags[0] as $tag) {
            $this->assertStringContainsString('nonce="'.$nonce[1].'"', $tag);
        }
    }

    public function test_each_response_gets_its_own_nonce(): void
    {
        $this->publishLivewireBuilds();

        preg_match("/'nonce-([^']+)'/", $this->policy($this->get('/')), $first);
        preg_match("/'nonce-([^']+)'/", $this->policy($this->get('/')), $second);

        $this->assertNotSame($first[1], $second[1]);
    }

    public function test_shop_pages_load_the_csp_safe_livewire_build(): void
    {
        $this->publishLivewireBuilds();

        $this->get('/')
            ->assertSee('/vendor/livewire/livewire.csp.', false)
            ->assertDontSee('/vendor/livewire/livewire.min.js', false)
            ->assertDontSee('/vendor/livewire/livewire.js', false);
    }

    public function test_without_published_builds_the_shop_falls_back_to_the_regular_build(): void
    {
        $response = $this->get('/')->assertOk();

        // The regular Alpine build evaluates expressions, so the policy has to allow that.
        $this->assertStringContainsString("'unsafe-eval'", $this->directive($this->policy($response), 'script-src'));
        $response->assertDontSee('livewire.csp', false);
    }

    public function test_the_admin_panel_keeps_the_regular_build_and_a_policy_without_script_rules(): void
    {
        $this->publishLivewireBuilds();

        $response = $this->get('/admin/login')->assertOk();
        $policy = $this->policy($response);

        $response->assertDontSee('livewire.csp', false);
        $this->assertStringNotContainsString('script-src', $policy);
        $this->assertStringNotContainsString('default-src', $policy);
        $this->assertSame("'none'", $this->directive($policy, 'frame-ancestors'));
        $this->assertSame("'none'", $this->directive($policy, 'object-src'));
    }

    public function test_general_headers_are_sent_everywhere(): void
    {
        foreach (['/', '/admin/login', '/gibt-es-nicht', '/up'] as $path) {
            $response = $this->get($path);

            $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'), $path);
            $this->assertSame('DENY', $response->headers->get('X-Frame-Options'), $path);
            $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'), $path);
            $this->assertNotNull($response->headers->get('Permissions-Policy'), $path);
            $this->assertNotNull($response->headers->get('Content-Security-Policy'), $path);
        }
    }

    public function test_a_response_with_its_own_policy_keeps_it(): void
    {
        \Illuminate\Support\Facades\Route::get('/_test/eigene-richtlinie', fn () => response('ok')
            ->header('Content-Security-Policy', "default-src 'none'"));

        $this->assertSame("default-src 'none'", $this->policy($this->get('/_test/eigene-richtlinie')));
    }
}
