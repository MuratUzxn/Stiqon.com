<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends the security headers with every response.
 *
 * The shop gets a strict Content Security Policy: scripts run only when they
 * come from this site and carry the nonce of the current request, and nothing
 * is loaded from other origins. To get by without 'unsafe-eval', shop pages
 * use Livewire's CSP-safe build (which brings Alpine's CSP-safe build), so
 * Alpine expressions in shop templates have to stay simple – see
 * resources/js/app.js.
 *
 * The admin panel cannot run under that policy: Filament relies on the regular
 * Alpine build and on inline scripts. It keeps the regular Livewire build and
 * gets the part of the policy that does not concern scripts.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $shop = ! $this->isAdminPanel($request);
        $strict = $shop && self::cspSafeBuildAvailable();

        // Decided per request, before any view renders its script tags.
        config(['livewire.csp_safe' => $strict]);

        if ($shop) {
            Vite::useCspNonce();
        }

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // A response that brings its own policy keeps it.
        if ($response->headers->has('Content-Security-Policy')) {
            return $response;
        }

        // The framework's debug page for exceptions needs inline scripts. It only
        // exists with APP_DEBUG=true, which is never the case in production.
        if (config('app.debug') && $response->getStatusCode() >= 500) {
            return $response;
        }

        // While the Vite dev server runs (local development), assets come from
        // its origin; the policy is then only reported in the browser console.
        $header = app()->isLocal() && Vite::isRunningHot()
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $response->headers->set($header, $shop ? $this->shopPolicy($strict) : $this->adminPolicy());

        return $response;
    }

    /**
     * Whether the shop can do without 'unsafe-eval'.
     *
     * Both Livewire builds are published as static files by
     * "composer install" (see the post-autoload-dump script). Only then can
     * shop pages and the admin panel load different builds. Without the
     * published files everything falls back to the regular build and the
     * shop policy has to allow 'unsafe-eval'.
     */
    public static function cspSafeBuildAvailable(): bool
    {
        return is_file(public_path('vendor/livewire/manifest.json'));
    }

    private function isAdminPanel(Request $request): bool
    {
        $path = trim(Filament::getPanel('admin')->getPath(), '/');

        return $request->is($path, $path.'/*');
    }

    private function shopPolicy(bool $strict): string
    {
        $scripts = ["'self'", "'nonce-".Vite::cspNonce()."'"];

        if (! $strict) {
            $scripts[] = "'unsafe-eval'";
        }

        return $this->compile([
            'default-src' => ["'self'"],
            'script-src' => $scripts,
            // Alpine and Livewire set style attributes; styles cannot run code.
            'style-src' => ["'self'", "'unsafe-inline'"],
            // blob: is the visitor's own motif, shown from their browser.
            'img-src' => ["'self'", 'data:', 'blob:'],
            'font-src' => ["'self'"],
            'connect-src' => ["'self'"],
            'worker-src' => ["'self'", 'blob:'],
            'object-src' => ["'none'"],
            'base-uri' => ["'none'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ]);
    }

    private function adminPolicy(): string
    {
        return $this->compile([
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ]);
    }

    /**
     * @param  array<string, list<string>>  $directives
     */
    private function compile(array $directives): string
    {
        $parts = [];

        foreach ($directives as $name => $sources) {
            $parts[] = $name.' '.implode(' ', $sources);
        }

        return implode('; ', $parts);
    }
}
