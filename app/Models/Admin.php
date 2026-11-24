<?php

namespace App\Models;

use App\Enums\AdminRole;
use Database\Factories\AdminFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Shop staff with access to the admin panel.
 *
 * Deliberately a separate model and table from customers (User): there is no
 * path from customer registration to an admin session. The role is never mass
 * assignable; it is set explicitly by owner-only code.
 *
 * @property AdminRole $role
 * @property bool $is_active
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'])]
class Admin extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory;

    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;
    use Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => AdminRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->is_active;
    }

    public function isOwner(): bool
    {
        return $this->role === AdminRole::Owner;
    }
}
