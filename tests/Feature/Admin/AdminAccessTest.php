<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin panel runs on its own guard and table. These tests run in the
 * "testing" environment, where Filament enforces canAccessPanel() exactly as
 * in production.
 */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_the_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_a_customer_session_does_not_grant_admin_access(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer, 'web')
            ->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_an_admin_must_set_up_the_authenticator_app_first(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertRedirect(route('filament.admin.auth.multi-factor-authentication.set-up-required'));
    }

    public function test_an_admin_with_mfa_reaches_the_dashboard(): void
    {
        $admin = Admin::factory()->withMfa()->create();

        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertOk();
    }

    public function test_an_inactive_admin_is_rejected(): void
    {
        $admin = Admin::factory()->withMfa()->inactive()->create();

        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_the_authenticator_secret_is_stored_encrypted(): void
    {
        $admin = Admin::factory()->withMfa('JBSWY3DPEHPK3PXP')->create();

        $stored = $admin->getConnection()->table('admins')->where('id', $admin->id)->value('app_authentication_secret');

        $this->assertNotSame('JBSWY3DPEHPK3PXP', $stored);
        $this->assertSame('JBSWY3DPEHPK3PXP', $admin->fresh()->getAppAuthenticationSecret());
        $this->assertArrayNotHasKey('app_authentication_secret', $admin->fresh()->toArray());
    }

    public function test_the_role_cannot_be_mass_assigned(): void
    {
        $admin = new Admin(['name' => 'Test', 'email' => 'test@example.test', 'password' => 'secret', 'role' => 'owner']);

        $this->assertNull($admin->role);
    }
}
