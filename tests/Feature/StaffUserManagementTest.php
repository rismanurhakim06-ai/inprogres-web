<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_accounts_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $email = 'user-created@example.test';

        $this->actingAs($admin)
            ->get(route('settings.roles.edit'))
            ->assertOk()
            ->assertSee('<option value="user"', false)
            ->assertDontSee('<option value="admin"', false)
            ->assertDontSee('<option value="supervisor"', false)
            ->assertDontSee('<option value="owner"', false);

        $this->post(route('settings.roles.users.store'), [
            'name' => 'User Baru',
            'email' => $email,
            'role' => 'user',
            'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123',
        ])->assertRedirect(route('settings.roles.edit'));

        $this->assertDatabaseHas('users', ['name' => 'User Baru', 'email' => $email, 'role' => 'user']);
        $this->assertTrue(Hash::check('Strong-password-123', User::query()->where('email', $email)->firstOrFail()->password));
    }

    public function test_admin_cannot_create_admin_supervisor_or_owner_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['admin', 'supervisor', 'owner'] as $role) {
            $email = $role.'-created@example.test';

            $this->actingAs($admin)
                ->from(route('settings.roles.edit'))
                ->post(route('settings.roles.users.store'), [
                    'name' => ucfirst($role).' Baru',
                    'email' => $email,
                    'role' => $role,
                    'password' => 'Strong-password-123',
                    'password_confirmation' => 'Strong-password-123',
                ])
                ->assertSessionHasErrors('role');

            $this->assertDatabaseMissing('users', ['email' => $email]);
        }
    }

    public function test_superadmin_can_create_admin_supervisor_and_owner_accounts(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($superadmin)
            ->get(route('settings.roles.edit'))
            ->assertOk()
            ->assertSee('<option value="owner"', false);

        foreach (['admin', 'supervisor', 'owner'] as $role) {
            $email = $role.'-created@example.test';

            $this->actingAs($superadmin)
                ->post(route('settings.roles.users.store'), [
                    'name' => ucfirst($role).' Baru',
                    'email' => $email,
                    'role' => $role,
                    'password' => 'Strong-password-123',
                    'password_confirmation' => 'Strong-password-123',
                ])
                ->assertRedirect(route('settings.roles.edit'));

            $this->assertDatabaseHas('users', ['email' => $email, 'role' => $role]);
        }
    }

    public function test_admin_cannot_create_a_superadmin_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('settings.roles.edit'))
            ->post(route('settings.roles.users.store'), [
                'name' => 'Tidak boleh',
                'email' => 'escalation@example.test',
                'role' => 'superadmin',
                'password' => 'Strong-password-123',
                'password_confirmation' => 'Strong-password-123',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'escalation@example.test']);
    }

    public function test_regular_user_cannot_create_staff_accounts(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->post(route('settings.roles.users.store'), [
                'name' => 'Tidak boleh',
                'email' => 'unauthorized@example.test',
                'role' => 'supervisor',
                'password' => 'Strong-password-123',
                'password_confirmation' => 'Strong-password-123',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'unauthorized@example.test']);
    }
}
