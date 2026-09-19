<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_submit_and_track_a_ticket(): void
    {
        $response = $this->post(route('tickets.store'), [
            'requester_name' => 'Alya Pratama',
            'whatsapp_number' => '081234567890',
            'description' => 'Mohon bantuan memperbarui halaman layanan.',
            'priority' => 'urgent',
            'target' => 'lppm',
        ]);

        $ticket = Ticket::first();

        $response->assertRedirect(route('home'))
            ->assertSessionHas('created_ticket', $ticket->ticket_number);
        $this->get(route('home'))
            ->assertOk()
            ->assertSee($ticket->ticket_number)
            ->assertDontSee('Belum disetujui');
        $this->get(route('home', ['ticket' => $ticket->ticket_number]))
            ->assertOk()
            ->assertSee($ticket->ticket_number)
            ->assertSee('Belum disetujui');
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
}
