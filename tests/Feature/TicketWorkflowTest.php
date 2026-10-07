<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\RoleDashboardSetting;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_must_sign_in_before_submitting_a_ticket(): void
    {
        $this->post(route('tickets.store'), [
            'requester_name' => 'Alya Pratama',
            'whatsapp_number' => '081234567890',
            'description' => 'Mohon bantuan memperbarui halaman layanan.',
            'priority' => 'urgent',
            'target' => 'lppm',
        ])->assertRedirect(route('login'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Masuk untuk membuat tiket')
            ->assertDontSee('new-ticket-panel', false);
    }

    public function test_authenticated_user_can_submit_and_track_a_ticket(): void
    {
        $user = User::factory()->create([
            'email' => 'user.lppm@example.com',
            'role' => 'user',
            'target' => 'lppm',
        ]);

        $response = $this->actingAs($user)->post(route('tickets.store'), [
            'requester_name' => 'Alya Pratama',
            'whatsapp_number' => '081234567890',
            'description' => 'Mohon bantuan memperbarui halaman layanan.',
            'priority' => 'urgent',
            'target' => 'Portal Akademik Internal',
        ]);

        $ticket = Ticket::firstOrFail();

        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('created_ticket', $ticket->ticket_number);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Buat Ticket Baru')
            ->assertSee('Cari Tiket')
            ->assertSee('x-show="showTicketSearch"', false)
            ->assertSee('role="status"', false)
            ->assertSee('fixed inset-0 z-[100] flex items-center justify-center', false)
            ->assertSee('&#10003;', false)
            ->assertSee('action="'.route('tickets.store').'"', false)
            ->assertSee('name="description"', false)
            ->assertSee('list="new-ticket-target-options"', false)
            ->assertSee('value="Web LPPM"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('status-pending', false)
            ->assertSee($ticket->ticket_number)
            ->assertSee('Portal Akademik Internal');
        $this->get(route('home', ['ticket' => $ticket->ticket_number]))
            ->assertOk()
            ->assertSee($ticket->ticket_number)
            ->assertSee('Portal Akademik Internal')
            ->assertSee('Belum disetujui');

        $this->assertSame(
            $user->id,
            $ticket->user_id,
        );
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'target' => 'Portal Akademik Internal',
        ]);
    }

    public function test_ticket_is_saved_to_the_authenticated_user_not_the_selected_target_account(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'target' => 'lppm',
        ]);
        $targetUser = User::factory()->create([
            'email' => 'user.ma@example.com',
            'role' => 'user',
            'target' => 'ma',
        ]);

        $this->actingAs($user)->post(route('tickets.store'), [
            'requester_name' => 'Alya Pratama',
            'whatsapp_number' => '081234567890',
            'description' => 'Mohon bantuan memperbarui halaman layanan.',
            'priority' => 'urgent',
            'target' => 'ma',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('tickets', [
            'target' => 'ma',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('tickets', ['user_id' => $targetUser->id]);
    }

    public function test_staff_cannot_create_a_ticket_from_the_user_submission_form(): void
    {
        foreach (['admin', 'superadmin'] as $role) {
            $staff = User::factory()->create(['role' => $role]);

            $this->actingAs($staff)
                ->post(route('tickets.store'), [
                    'requester_name' => 'Alya Pratama',
                    'whatsapp_number' => '081234567890',
                    'description' => 'Mohon bantuan memperbarui halaman layanan.',
                    'priority' => 'urgent',
                    'target' => 'lppm',
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_only_admin_or_supervisor_can_update_tickets(): void
    {
        $ticket = Ticket::factory()->create();
        $regularUser = User::factory()->create(['role' => 'user']);

        $this->actingAs($regularUser)
            ->patch(route('tickets.update', $ticket), ['status' => 'completed'])
            ->assertForbidden();

        $this->assertSame('pending', $ticket->fresh()->status->value);
    }

    public function test_public_lookup_shows_the_completion_date(): void
    {
        $completedAt = now()->setDate(2026, 9, 19)->setTime(14, 30);
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Completed,
            'completed_at' => $completedAt,
        ]);

        $this->get(route('home', ['ticket' => $ticket->ticket_number]))
            ->assertOk()
            ->assertSee('Tanggal selesai: '.$completedAt->translatedFormat('d M Y, H:i'));
    }

    public function test_admin_dashboard_colors_tickets_by_priority(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Ticket::factory()->create(['priority' => TicketPriority::Relaxed]);
        Ticket::factory()->create(['priority' => TicketPriority::Urgent]);
        Ticket::factory()->create(['priority' => TicketPriority::Critical]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('priority-row priority-relaxed', false)
            ->assertSee('priority-row priority-urgent', false)
            ->assertSee('priority-row priority-critical', false);
    }

    public function test_supervisor_can_delete_a_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)
            ->delete(route('tickets.destroy', $ticket))
            ->assertRedirect();

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_user_dashboard_only_shows_owned_tickets_and_can_edit_submission(): void
    {
        $user = User::factory()->create(['role' => 'user', 'target' => 'lppm']);
        $ownedTicket = Ticket::factory()->create(['user_id' => $user->id]);
        $otherTicket = Ticket::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($ownedTicket->ticket_number)
            ->assertDontSee($otherTicket->ticket_number)
            ->assertSee('EDIT')
            ->assertDontSee('Simpan');

        $this->actingAs($user)
            ->patch(route('tickets.update-own', $ownedTicket), [
                'requester_name' => 'Nama Baru',
                'whatsapp_number' => '081234567890',
                'description' => 'Deskripsi pengajuan yang sudah diperbarui.',
                'priority' => 'urgent',
                'target' => 'Sistem Operasional Baru',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('tickets', [
            'id' => $ownedTicket->id,
            'user_id' => $user->id,
            'requester_name' => 'Nama Baru',
            'description' => 'Deskripsi pengajuan yang sudah diperbarui.',
            'target' => 'Sistem Operasional Baru',
        ]);
    }

    public function test_user_cannot_edit_another_users_ticket(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->create(['user_id' => User::factory()->create(['role' => 'user'])->id]);

        $this->actingAs($user)
            ->patch(route('tickets.update-own', $ticket), [
                'requester_name' => 'Tidak boleh',
                'whatsapp_number' => '081234567890',
                'description' => 'Perubahan yang tidak boleh diterapkan.',
                'priority' => 'urgent',
                'target' => 'lppm',
            ])
            ->assertForbidden();
    }

    public function test_admin_and_ticket_owner_can_exchange_comments(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $user = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $this->actingAs($owner)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Mohon lengkapi detail pengajuan.'])
            ->assertRedirect(route('tickets.comments', $ticket));

        $this->actingAs($user)
            ->get(route('tickets.comments', $ticket))
            ->assertOk()
            ->assertSee('Mohon lengkapi detail pengajuan.');

        $this->actingAs($user)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Baik, detailnya sudah saya lengkapi.'])
            ->assertRedirect(route('tickets.comments', $ticket));

        $this->assertDatabaseCount('ticket_comments', 2);
        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'Baik, detailnya sudah saya lengkapi.',
        ]);
    }

    public function test_user_cannot_comment_on_another_users_ticket(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->create(['user_id' => User::factory()->create(['role' => 'user'])->id]);

        $this->actingAs($user)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Pesan yang tidak boleh dikirim.'])
            ->assertForbidden();

        $this->assertDatabaseCount('ticket_comments', 0);
    }

    public function test_supervisor_comment_marks_the_ticket_unread_for_the_user(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        RoleDashboardSetting::query()->create([
            'role' => 'supervisor',
            'features' => array_replace(RoleDashboardSetting::defaultFeaturesFor('supervisor'), ['comment_tools' => false]),
        ]);

        $this->actingAs($supervisor)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Mohon lengkapi detail pengajuan.'])
            ->assertRedirect(route('tickets.comments', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'unread_by_user' => true,
            'unread_by_supervisor' => false,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('New Comment');

        $this->actingAs($supervisor)
            ->get(route('dashboard'))
            ->assertDontSee('Chat (1)')
            ->assertSee('<th class="px-5 py-3">New Comment</th>', false);
    }

    public function test_user_comment_marks_ticket_unread_for_supervisor(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($user)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Saya sudah melengkapi detailnya.'])
            ->assertRedirect(route('tickets.comments', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'unread_by_user' => false,
            'unread_by_supervisor' => true,
        ]);

        $this->actingAs($supervisor)
            ->get(route('dashboard'))
            ->assertSee('New Comment');
    }

    public function test_reply_clears_flag_for_the_replying_party(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Mohon lengkapi detail pengajuan.'])
            ->assertRedirect(route('tickets.comments', $ticket));

        $this->actingAs($user)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Detail sudah dilengkapi.'])
            ->assertRedirect(route('tickets.comments', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'unread_by_user' => false,
            'unread_by_supervisor' => true,
        ]);
    }

    public function test_completing_ticket_clears_both_unread_flags(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::factory()->create([
            'unread_by_user' => true,
            'unread_by_supervisor' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('tickets.update', $ticket), ['status' => 'completed'])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'completed',
            'unread_by_user' => false,
            'unread_by_supervisor' => false,
        ]);
    }

    public function test_dashboard_status_cards_filter_tickets_and_show_comment_total(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pendingTicket = Ticket::factory()->create(['status' => TicketStatus::Pending]);
        $completedTicket = Ticket::factory()->create(['status' => TicketStatus::Completed]);
        TicketComment::create([
            'ticket_id' => $pendingTicket->id,
            'user_id' => $admin->id,
            'body' => 'Komentar untuk pengajuan.',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard', ['status' => 'pending']))
            ->assertOk()
            ->assertSee($pendingTicket->ticket_number)
            ->assertDontSee($completedTicket->ticket_number)
            ->assertSee('Komentar')
            ->assertSee('1');

        $this->actingAs($admin)
            ->get(route('dashboard', ['filter' => 'comments']))
            ->assertOk()
            ->assertSee($pendingTicket->ticket_number)
            ->assertDontSee($completedTicket->ticket_number)
            ->assertSee('Pengajuan yang dikomentari staf');
    }

    public function test_admin_can_search_tickets_and_sort_matching_results(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $laterNameTicket = Ticket::factory()->create([
            'requester_name' => 'Zahra Hidayat',
            'description' => 'Mohon pembaruan portal akademik.',
        ]);
        $earlierNameTicket = Ticket::factory()->create([
            'requester_name' => 'Alya Pratama',
            'description' => 'Perlu pembaruan portal layanan.',
        ]);
        $unmatchedTicket = Ticket::factory()->create([
            'requester_name' => 'Bima Saputra',
            'description' => 'Perbaikan jaringan kantor.',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard', [
            'search' => 'pembaruan portal',
            'sort' => 'requester_name',
            'direction' => 'asc',
        ]));

        $response->assertOk()
            ->assertSee($earlierNameTicket->ticket_number)
            ->assertSee($laterNameTicket->ticket_number)
            ->assertDontSee($unmatchedTicket->ticket_number);
        $this->assertTrue(
            strpos($response->getContent(), 'Alya Pratama') < strpos($response->getContent(), 'Zahra Hidayat'),
        );
    }

    public function test_user_ticket_search_does_not_show_another_users_ticket(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $ownedTicket = Ticket::factory()->create([
            'user_id' => $user->id,
            'description' => 'Pencarian khusus untuk pembaruan portal.',
        ]);
        $otherTicket = Ticket::factory()->create([
            'description' => 'Pencarian khusus untuk portal lainnya.',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', ['search' => 'pencarian khusus']))
            ->assertOk()
            ->assertSee($ownedTicket->ticket_number)
            ->assertDontSee($otherTicket->ticket_number);
    }
}
