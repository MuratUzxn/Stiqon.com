<?php

namespace Tests\Feature\Auth;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Customers get registration, login, password reset, e-mail verification and
 * profile updates – nothing else. Fortify enables two-factor authentication
 * and passkeys by default, so their absence is asserted explicitly.
 */
class FortifyFeatureSetTest extends TestCase
{
    public function test_two_factor_and_passkey_routes_are_not_registered(): void
    {
        $offending = collect(Route::getRoutes()->getRoutes())
            ->filter(function (RoutingRoute $route): bool {
                $name = (string) $route->getName();

                return Str::startsWith($name, ['two-factor.', 'passkey.', 'passkeys.'])
                    || Str::contains($route->uri(), ['two-factor', 'passkey']);
            })
            // The admin panel has its own, intended multi-factor routes.
            ->reject(fn (RoutingRoute $route): bool => Str::startsWith($route->uri(), 'admin/'))
            ->map(fn (RoutingRoute $route): string => $route->uri())
            ->values()
            ->all();

        $this->assertSame([], $offending);
    }

    public function test_customer_auth_uses_german_paths(): void
    {
        $this->assertSame('anmelden', Route::getRoutes()->getByName('login.store')->uri());
        $this->assertSame('registrieren', Route::getRoutes()->getByName('register.store')->uri());
        $this->assertSame('passwort-vergessen', Route::getRoutes()->getByName('password.email')->uri());
        $this->assertSame('e-mail-bestaetigen/{id}/{hash}', Route::getRoutes()->getByName('verification.verify')->uri());
    }
}
