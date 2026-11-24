<?php

namespace App\Providers\Filament;

use App\Filament\AvatarProviders\LocalInitialsAvatarProvider;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The shop's back office at /admin.
 *
 * Runs on its own guard and user table ("admin"/admins) with a mandatory
 * authenticator app. Stock Filament theme: no ->font() (it would fall back to
 * a font CDN) and a local avatar provider, so the panel makes no third-party
 * requests.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Stiqon Verwaltung')
            ->authGuard('admin')
            ->authPasswordBroker('admins')
            ->login()
            ->passwordReset()
            ->profile()
            // Mandatory for every admin, without an off switch: the panel controls bank
            // details, payment keys and customer data.
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()],
                isRequired: true,
            )
            ->defaultAvatarProvider(LocalInitialsAvatarProvider::class)
            ->colors([
                'primary' => Color::Zinc,
            ])
            ->databaseTransactions()
            ->unsavedChangesAlerts()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
