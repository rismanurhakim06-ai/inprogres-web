<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_view_and_update_account_details_and_reset_password(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $account = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'old@example.test',
            'role' => 'user',
            'password' => 'existing-password',
        ]);
        $storedHash = $account->password;

        $this->actingAs($superadmin)
            ->get(route('settings.accounts.index'))
            ->assertOk()
            ->assertSee('Nama Lama')
            ->assertSee('old@example.test')
            ->assertSee('user')
            ->assertDontSee($storedHash)
            ->assertDontSee('existing-password');

        $this->put(route('settings.accounts.update', $account), [
            'name' => 'Nama Baru',
            'email' => 'new@example.test',
            'role' => 'supervisor',
            'password' => 'new-account-password',
            'password_confirmation' => 'new-account-password',
        ])->assertRedirect(route('settings.accounts.index'));

        $account->refresh();
        $this->assertSame('Nama Baru', $account->name);
        $this->assertSame('new@example.test', $account->email);
        $this->assertSame('supervisor', $account->role);
        $this->assertTrue(Hash::check('new-account-password', $account->password));
        $this->assertNotSame($storedHash, $account->password);
    }

    public function test_superadmin_can_update_account_without_changing_existing_password(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $account = User::factory()->create(['password' => 'unchanged-password']);
        $storedHash = $account->password;

        $this->actingAs($superadmin)
            ->put(route('settings.accounts.update', $account), [
                'name' => 'Nama Diperbarui',
                'email' => $account->email,
                'role' => 'admin',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('settings.accounts.index'));

        $account->refresh();
        $this->assertSame('Nama Diperbarui', $account->name);
        $this->assertSame('admin', $account->role);
        $this->assertSame($storedHash, $account->password);
    }

    public function test_admin_and_regular_users_cannot_manage_all_accounts(): void
    {
        foreach (['admin', 'user', 'supervisor', 'owner'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('settings.accounts.index'))
                ->assertForbidden();

            $this->actingAs($user)
                ->delete(route('settings.accounts.destroy', User::factory()->create()))
                ->assertForbidden();
        }
    }

    public function test_superadmin_deletion_removes_account_tickets_and_authored_comments(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $account = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create();
        $ownedTicket = Ticket::factory()->create(['user_id' => $account->id]);
        $otherTicket = Ticket::factory()->create([
            'user_id' => $otherUser->id,
            'assigned_to' => $account->id,
            'updated_by' => $account->id,
        ]);
        $ownedTicketComment = TicketComment::create([
            'ticket_id' => $ownedTicket->id,
            'user_id' => $otherUser->id,
            'body' => 'Komentar pada tiket akun.',
        ]);
        $authoredComment = TicketComment::create([
            'ticket_id' => $otherTicket->id,
            'user_id' => $account->id,
            'body' => 'Komentar yang ditulis akun.',
        ]);

        $this->actingAs($superadmin)
            ->delete(route('settings.accounts.destroy', $account))
            ->assertRedirect(route('settings.accounts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $account->id]);
        $this->assertDatabaseMissing('tickets', ['id' => $ownedTicket->id]);
        $this->assertDatabaseMissing('ticket_comments', ['id' => $ownedTicketComment->id]);
        $this->assertDatabaseMissing('ticket_comments', ['id' => $authoredComment->id]);
        $this->assertDatabaseHas('tickets', [
            'id' => $otherTicket->id,
            'user_id' => $otherUser->id,
            'assigned_to' => null,
            'updated_by' => null,
        ]);
    }

    public function test_superadmin_cannot_delete_the_account_used_for_the_current_session(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($superadmin)
            ->delete(route('settings.accounts.destroy', $superadmin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $superadmin->id, 'role' => 'superadmin']);
    }
}
