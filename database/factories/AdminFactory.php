<?php

namespace Database\Factories;

use App\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    protected static ?string $password = null;

    /**
     * Factories fill models unguarded, so the (not mass assignable) role can be set here.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => AdminRole::Staff,
            'is_active' => true,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (): array => ['role' => AdminRole::Owner]);
    }

    /**
     * An admin who has already set up the authenticator app (the panel requires it).
     * The secret is a fixed test value; the model stores it encrypted.
     */
    public function withMfa(string $secret = 'JBSWY3DPEHPK3PXP'): static
    {
        return $this->state(fn (): array => ['app_authentication_secret' => $secret]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
