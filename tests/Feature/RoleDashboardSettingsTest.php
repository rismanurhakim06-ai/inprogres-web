<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\RoleDashboardSetting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_save_visibility_settings_for_each_role(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $user = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $settingsPage = $this->actingAs($superadmin)
            ->get(route('settings.roles.edit'))
            ->assertOk()
            ->assertSee('Pengaturan tampilan role');

        $this->assertSame(91, substr_count($settingsPage->getContent(), 'type="checkbox"'));
        $settingsPage->assertSee('Penerima Notifikasi Telegram')
            ->assertSee('Ticket baru dibuat')
            ->assertSee('Akun baru registrasi');
        $this->assertSame(1, substr_count($settingsPage->getContent(), 'Simpan pengaturan'));

        $this->put(route('settings.roles.update'), [
            'settings' => [
                'superadmin' => ['_present' => '1', 'summary_total' => '1', 'summary_pending' => '1', 'summary_in_progress' => '1', 'summary_completed' => '1', 'summary_comments' => '1', 'ticket_number' => '1', 'requester_name' => '1', 'description' => '1', 'whatsapp_number' => '1', 'target' => '1', 'status' => '1', 'comment_tools' => '1', 'edit_submission' => '1', 'actions' => '1', 'completed_at' => '1', 'created_at' => '1'],
                'admin' => ['_present' => '1', 'summary_total' => '1', 'summary_pending' => '1', 'summary_in_progress' => '1', 'summary_completed' => '1', 'summary_comments' => '1', 'ticket_number' => '1', 'requester_name' => '1', 'description' => '1', 'whatsapp_number' => '1', 'target' => '1', 'status' => '1', 'comment_tools' => '1', 'edit_submission' => '1', 'actions' => '1', 'completed_at' => '1', 'created_at' => '1'],
                'user' => ['_present' => '1', 'summary_total' => '0', 'summary_pending' => '1', 'summary_in_progress' => '1', 'summary_completed' => '1', 'summary_comments' => '1', 'ticket_number' => '0', 'requester_name' => '0', 'description' => '0', 'whatsapp_number' => '0', 'target' => '1', 'status' => '1', 'comment_tools' => '0', 'edit_submission' => '0', 'actions' => '0', 'completed_at' => '1', 'created_at' => '0'],
                'supervisor' => ['_present' => '1', 'summary_total' => '1', 'summary_pending' => '1', 'summary_in_progress' => '1', 'summary_completed' => '1', 'summary_comments' => '1', 'ticket_number' => '1', 'requester_name' => '1', 'description' => '1', 'whatsapp_number' => '1', 'target' => '1', 'status' => '1', 'comment_tools' => '1', 'edit_submission' => '1', 'actions' => '1', 'completed_at' => '1', 'created_at' => '1'],
                'owner' => ['_present' => '1', 'summary_total' => '1', 'summary_pending' => '1', 'summary_in_progress' => '1', 'summary_completed' => '1', 'summary_comments' => '1', 'ticket_number' => '1', 'requester_name' => '1', 'description' => '1', 'whatsapp_number' => '1', 'target' => '1', 'status' => '1', 'comment_tools' => '1', 'edit_submission' => '1', 'actions' => '1', 'completed_at' => '1', 'created_at' => '1'],
            ],
        ])->assertRedirect(route('settings.roles.edit'));

        $this->assertDatabaseHas('role_dashboard_settings', ['role' => 'user']);
        $userSettings = RoleDashboardSetting::query()->where('role', 'user')->firstOrFail();
        $this->assertFalse($userSettings->features['ticket_number']);
        $this->assertFalse($userSettings->features['requester_name']);
        $this->assertFalse($userSettings->features['description']);
        $this->assertFalse($userSettings->features['whatsapp_number']);
        $this->assertFalse($userSettings->features['comment_tools']);
        $this->assertFalse($userSettings->features['edit_submission']);
        $this->assertTrue($userSettings->features['status']);
        $this->assertFalse($userSettings->features['actions']);
        $this->assertFalse($userSettings->features['created_at']);
        $this->assertFalse($userSettings->features['summary_total']);
        $this->assertTrue($userSettings->features['summary_pending']);
        $this->assertTrue($userSettings->features['summary_comments']);

        $adminSettings = RoleDashboardSetting::query()->where('role', 'admin')->firstOrFail();
        $this->assertTrue($adminSettings->features['requester_name']);
        $this->assertTrue($adminSettings->features['actions']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Total tiket')
            ->assertSee('Menunggu')
            ->assertSee('Komentar staf')
            ->assertDontSee('Chat (')
            ->assertDontSee('<th class="px-5 py-3">Nomor tiket</th>', false)
            ->assertDontSee('<th class="px-5 py-3">Nama pengaju</th>', false)
            ->assertDontSee('<th class="px-5 py-3">No. WhatsApp</th>', false)
            ->assertDontSee('EDIT')
            ->assertDontSee('<th class="px-5 py-3">Tanggal ajuan</th>', false)
            ->assertDontSee('<th class="px-5 py-3">Komentar</th>', false)
            ->assertSee('Target');
        $this->assertStringNotContainsString($ticket->ticket_number, $this->get(route('dashboard'))->getContent());
    }

    public function test_non_superadmin_roles_cannot_open_or_update_role_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('settings.roles.edit'))
            ->assertOk()
            ->assertSee('Buat akun User')
            ->assertSee('Kelola akun')
            ->assertDontSee('Pengaturan tampilan role');

        $this->actingAs($admin)
            ->put(route('settings.roles.update'), [])
            ->assertForbidden();

        foreach (['user', 'supervisor', 'owner'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('settings.roles.edit'))
                ->assertForbidden();

            $this->actingAs($user)
                ->put(route('settings.roles.update'), [])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('role_dashboard_settings', 5);
    }

    public function test_superadmin_can_toggle_dashboard_features_independently_for_each_role(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $user = User::factory()->create(['role' => 'user']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        Ticket::factory()->create([
            'user_id' => $user->id,
            'unread_by_user' => true,
            'unread_by_supervisor' => true,
        ]);

        $settings = [];

        foreach (RoleDashboardSetting::ROLE_LABELS as $role => $label) {
            $settings[$role] = ['_present' => '1'];

            foreach (array_keys(RoleDashboardSetting::FEATURES) as $feature) {
                $settings[$role][$feature] = '0';
            }
        }

        $settings['user']['ticket_number'] = '1';
        $settings['user']['new_comment'] = '1';
        $settings['supervisor']['ticket_number'] = '1';

        $this->actingAs($superadmin)
            ->put(route('settings.roles.update'), ['settings' => $settings])
            ->assertRedirect(route('settings.roles.edit'));

        $this->assertTrue(RoleDashboardSetting::query()->where('role', 'user')->firstOrFail()->features['new_comment']);
        $this->assertFalse(RoleDashboardSetting::query()->where('role', 'supervisor')->firstOrFail()->features['new_comment']);
        $this->assertFalse(RoleDashboardSetting::query()->where('role', 'supervisor')->firstOrFail()->features['comment_tools']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('<th class="px-5 py-3">New Comment</th>', false)
            ->assertSee('New Comment');

        $this->actingAs($supervisor)
            ->get(route('dashboard'))
            ->assertDontSee('New Comment')
            ->assertDontSee('Chat (');
    }

    public function test_superadmin_can_comment_update_and_delete_tickets(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $ticket = Ticket::factory()->create();

        $this->actingAs($superadmin)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Catatan dari superadmin.'])
            ->assertRedirect(route('tickets.comments', $ticket));

        $this->get(route('tickets.comments', $ticket))
            ->assertOk()
            ->assertSee('Catatan dari superadmin.');

        $this->patch(route('tickets.update', $ticket), ['status' => 'completed'])
            ->assertRedirect();

        $this->assertSame(TicketStatus::Completed, $ticket->fresh()->status);

        $this->delete(route('tickets.destroy', $ticket))
            ->assertRedirect();

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }
}
