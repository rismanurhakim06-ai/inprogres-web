<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_superadmin_can_view_and_approve_pending_users(): void
    {
        $pendingUser = User::factory()->create([
            'name' => 'Pending User',
            'email' => 'pending@example.com',
            'is_approved' => false,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('account-approvals.index'))
            ->assertOk()
            ->assertSee('Persetujuan akun')
            ->assertSee('pending@example.com')
            ->assertSee('>1</span>', false);

        $this->post(route('account-approvals.store', $pendingUser))
            ->assertRedirect(route('account-approvals.index'))
            ->assertSessionHas('success', 'Akun Pending User berhasil disetujui.');

        $this->assertDatabaseHas('users', ['id' => $pendingUser->id, 'is_approved' => true]);

        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', [
            'email' => $pendingUser->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($pendingUser);

        $secondPendingUser = User::factory()->create(['is_approved' => false]);
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($superadmin)
            ->post(route('account-approvals.store', $secondPendingUser))
            ->assertRedirect(route('account-approvals.index'));

        $this->assertDatabaseHas('users', ['id' => $secondPendingUser->id, 'is_approved' => true]);
    }

    public function test_users_without_admin_roles_cannot_access_account_approvals(): void
    {
        $pendingUser = User::factory()->create(['is_approved' => false]);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('account-approvals.index'))
            ->assertForbidden();

        $this->post(route('account-approvals.store', $pendingUser))
            ->assertForbidden();
    }

    public function test_admin_cannot_approve_an_account_that_is_not_a_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor', 'is_approved' => false]);

        $this->actingAs($admin)
            ->post(route('account-approvals.store', $supervisor))
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $supervisor->id, 'is_approved' => false]);
    }
}
