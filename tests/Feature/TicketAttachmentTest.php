<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_ticket_with_a_private_attachment_and_view_it(): void
    {
        Storage::fake('local');
        $this->mock(TelegramService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendTicketCreatedNotification')->once();
        });
        $user = User::factory()->create(['role' => 'user']);
        $file = UploadedFile::fake()->create('diagnostic.pdf', 20, 'application/pdf');

        $response = $this->actingAs($user)->post(route('tickets.store'), [
            ...$this->ticketDetails(),
            'attachments' => [$file],
        ]);

        $response->assertRedirect(route('dashboard'));
        $ticket = Ticket::query()->firstOrFail();
        $attachment = $ticket->attachments()->firstOrFail();
        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'original_name' => 'diagnostic.pdf',
            'mime_type' => 'application/pdf',
        ]);
        Storage::disk('local')->assertExists($attachment->path);

        $this->actingAs($user)
            ->get(route('tickets.attachments.show', [$ticket, $attachment]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('diagnostic.pdf')
            ->assertSee(route('tickets.attachments.show', [$ticket, $attachment]));
    }

    public function test_ticket_submission_rejects_more_than_five_attachments(): void
    {
        Storage::fake('local');
        $this->mock(TelegramService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendTicketCreatedNotification');
        });
        $user = User::factory()->create(['role' => 'user']);
        $files = array_map(
            fn (int $index): UploadedFile => UploadedFile::fake()->create("document-{$index}.pdf", 1, 'application/pdf'),
            range(1, 6),
        );

        $response = $this->actingAs($user)->from(route('dashboard'))->post(route('tickets.store'), [
            ...$this->ticketDetails(),
            'attachments' => $files,
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHasErrors('attachments');
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    public function test_ticket_submission_rejects_an_attachment_over_ten_megabytes(): void
    {
        Storage::fake('local');
        $this->mock(TelegramService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendTicketCreatedNotification');
        });
        $user = User::factory()->create(['role' => 'user']);
        $file = UploadedFile::fake()->create('oversized.pdf', 10241, 'application/pdf');

        $response = $this->followingRedirects()
            ->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('tickets.store'), [
                ...$this->ticketDetails(),
                'attachments' => [$file],
            ]);

        $response->assertOk();
        $response->assertSee('Ukuran setiap lampiran maksimal 10 MB.');
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    public function test_ticket_submission_rejects_an_unsupported_attachment_format(): void
    {
        Storage::fake('local');
        $this->mock(TelegramService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendTicketCreatedNotification');
        });
        $user = User::factory()->create(['role' => 'user']);
        $file = UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml');

        $response = $this->actingAs($user)->from(route('dashboard'))->post(route('tickets.store'), [
            ...$this->ticketDetails(),
            'attachments' => [$file],
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHasErrors('attachments.0');
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_a_different_user_cannot_access_a_ticket_attachment(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->for($owner, 'requester')->create();
        $path = UploadedFile::fake()->create('diagnostic.pdf', 1, 'application/pdf')
            ->store('ticket-attachments', 'local');
        $attachment = $ticket->attachments()->create([
            'original_name' => 'diagnostic.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 1,
        ]);

        $this->actingAs($otherUser)
            ->get(route('tickets.attachments.show', [$ticket, $attachment]))
            ->assertNotFound();
    }

    public function test_guest_is_redirected_to_login_when_opening_a_ticket_attachment(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->for($owner, 'requester')->create();
        $path = UploadedFile::fake()->create('diagnostic.pdf', 1, 'application/pdf')
            ->store('ticket-attachments', 'local');
        $attachment = $ticket->attachments()->create([
            'original_name' => 'diagnostic.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 1,
        ]);

        $this->get(route('tickets.attachments.show', [$ticket, $attachment]))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_attach_supported_image_and_office_document_formats(): void
    {
        Storage::fake('local');
        $this->mock(TelegramService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendTicketCreatedNotification')->twice();
        });
        $user = User::factory()->create(['role' => 'user']);
        $files = [
            UploadedFile::fake()->create('photo.jpg', 1, 'image/jpeg'),
            UploadedFile::fake()->create('photo.png', 1, 'image/png'),
            UploadedFile::fake()->create('photo.webp', 1, 'image/webp'),
            UploadedFile::fake()->create('request.pdf', 1, 'application/pdf'),
            UploadedFile::fake()->create('request.doc', 1, 'application/msword'),
            UploadedFile::fake()->create('request.docx', 1, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            UploadedFile::fake()->create('budget.xls', 1, 'application/vnd.ms-excel'),
            UploadedFile::fake()->create('budget.xlsx', 1, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];

        foreach (array_chunk($files, 5) as $fileBatch) {
            $this->actingAs($user)->post(route('tickets.store'), [
                ...$this->ticketDetails(),
                'attachments' => $fileBatch,
            ])->assertRedirect(route('dashboard'));
        }

        $this->assertDatabaseCount('ticket_attachments', count($files));
        $imageAttachment = TicketAttachment::query()->where('original_name', 'photo.jpg')->firstOrFail();

        $this->actingAs($user)
            ->get(route('tickets.attachments.show', [$imageAttachment->ticket, $imageAttachment]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('Content-Disposition', 'inline; filename=photo.jpg');
    }

    public function test_deleting_a_ticket_removes_its_attachment_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->for($user, 'requester')->create();
        $path = UploadedFile::fake()->create('diagnostic.pdf', 1, 'application/pdf')
            ->store('ticket-attachments', 'local');
        $ticket->attachments()->create([
            'original_name' => 'diagnostic.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 1,
        ]);

        $ticket->delete();

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
        $this->assertDatabaseMissing('ticket_attachments', ['ticket_id' => $ticket->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_user_can_replace_a_ticket_attachment_while_editing_the_submission(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->for($user, 'requester')->create();
        $oldPath = UploadedFile::fake()->create('old-document.pdf', 1, 'application/pdf')
            ->store('ticket-attachments', 'local');
        $oldAttachment = $ticket->attachments()->create([
            'original_name' => 'old-document.pdf',
            'path' => $oldPath,
            'mime_type' => 'application/pdf',
            'size' => 1,
        ]);
        $newFile = UploadedFile::fake()->create('updated-document.pdf', 5, 'application/pdf');

        $this->actingAs($user)->patch(route('tickets.update-own', $ticket), [
            ...$this->ticketDetails(),
            'remove_attachments' => [$oldAttachment->id],
            'attachments' => [$newFile],
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('ticket_attachments', ['id' => $oldAttachment->id]);
        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'original_name' => 'updated-document.pdf',
        ]);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($ticket->attachments()->firstOrFail()->path);
    }

    public function test_edit_form_shows_current_attachments_and_limits_replacement_to_five_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->for($user, 'requester')->create();

        foreach (range(1, 5) as $index) {
            $path = UploadedFile::fake()->create("document-{$index}.pdf", 1, 'application/pdf')
                ->store('ticket-attachments', 'local');
            $ticket->attachments()->create([
                'original_name' => "document-{$index}.pdf",
                'path' => $path,
                'mime_type' => 'application/pdf',
                'size' => 1,
            ]);
        }

        $file = UploadedFile::fake()->create('sixth-document.pdf', 1, 'application/pdf');

        $this->actingAs($user)
            ->get(route('tickets.edit', $ticket))
            ->assertOk()
            ->assertSee('document-1.pdf')
            ->assertSee('Ganti / hapus')
            ->assertSee('Unggah file pengganti atau tambahan');

        $response = $this->followingRedirects()
            ->actingAs($user)
            ->from(route('tickets.edit', $ticket))
            ->patch(route('tickets.update-own', $ticket), [
                ...$this->ticketDetails(),
                'attachments' => [$file],
            ]);

        $response->assertOk()
            ->assertSee('Jumlah lampiran setelah perubahan maksimal 5 file.');
        $this->assertDatabaseCount('ticket_attachments', 5);
        $this->assertDatabaseMissing('ticket_attachments', ['original_name' => 'sixth-document.pdf']);
    }

    public function test_user_cannot_replace_an_attachment_from_another_ticket(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'user']);
        $ticket = Ticket::factory()->for($user, 'requester')->create();
        $otherTicket = Ticket::factory()->for($user, 'requester')->create();
        $otherTicketPath = UploadedFile::fake()->create('other-document.pdf', 1, 'application/pdf')
            ->store('ticket-attachments', 'local');
        $otherAttachment = $otherTicket->attachments()->create([
            'original_name' => 'other-document.pdf',
            'path' => $otherTicketPath,
            'mime_type' => 'application/pdf',
            'size' => 1,
        ]);

        $response = $this->followingRedirects()
            ->actingAs($user)
            ->from(route('tickets.edit', $ticket))
            ->patch(route('tickets.update-own', $ticket), [
                ...$this->ticketDetails(),
                'remove_attachments' => [$otherAttachment->id],
            ]);

        $response->assertOk()
            ->assertSee('Lampiran yang dipilih tidak ditemukan pada tiket ini.');
        $this->assertDatabaseHas('ticket_attachments', ['id' => $otherAttachment->id]);
        Storage::disk('local')->assertExists($otherTicketPath);
    }

    /**
     * @return array<string, string>
     */
    private function ticketDetails(): array
    {
        return [
            'requester_name' => 'Test User',
            'whatsapp_number' => '081234567890',
            'description' => 'Mohon bantu periksa kendala pada sistem.',
            'priority' => 'urgent',
            'target' => 'Web TRPL',
        ];
    }
}
