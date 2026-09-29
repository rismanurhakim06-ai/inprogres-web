<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketTarget;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_phone_number_and_telegram_chat_id(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone_number' => '081234567890',
            'telegram_chat_id' => '123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'phone_number' => '081234567890',
            'telegram_chat_id' => '123456789',
        ]);
    }

    public function test_user_can_update_profile_phone_number_and_telegram_chat_id(): void
    {
        $user = User::factory()->create([
            'phone_number' => '0811111111',
            'telegram_chat_id' => '111111',
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Budi Baru',
            'email' => $user->email,
            'phone_number' => '0899999999',
            'telegram_chat_id' => '999999',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('0899999999', $user->phone_number);
        $this->assertSame('999999', $user->telegram_chat_id);
    }

    public function test_telegram_service_message_formatting_includes_all_summary_details(): void
    {
        $ticket = Ticket::factory()->create([
            'ticket_number' => 'TCK-20260929-TEST1',
            'requester_name' => 'Jane Doe',
            'whatsapp_number' => '08123456789',
            'target' => TicketTarget::Lppm,
            'priority' => TicketPriority::Urgent,
            'description' => 'Tolong cek & perbaiki error <script>alert(1)</script> pada portal.',
        ]);

        $service = new TelegramService('dummy-token');
        $message = $service->formatTicketMessage($ticket);

        $this->assertStringContainsString('TCK-20260929-TEST1', $message);
        $this->assertStringContainsString('Jane Doe', $message);
        $this->assertStringContainsString('08123456789', $message);
        $this->assertStringContainsString(TicketTarget::Lppm->label(), $message);
        $this->assertStringContainsString(TicketPriority::Urgent->label(), $message);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $message);
        $this->assertStringNotContainsString('<script>', $message);
    }

    public function test_telegram_notification_is_sent_to_admin_and_supervisor_when_ticket_is_created(): void
    {
        Config::set('services.telegram.bot_token', 'test-bot-token');
        Http::fake();

        // 1. Admin with telegram_chat_id
        User::factory()->create([
            'role' => 'admin',
            'telegram_chat_id' => '100001',
        ]);

        // 2. Supervisor with telegram_chat_id
        User::factory()->create([
            'role' => 'supervisor',
            'telegram_chat_id' => '200002',
        ]);

        // 3. Superadmin with chat id in phone_number (fallback test)
        User::factory()->create([
            'role' => 'superadmin',
            'phone_number' => '300003',
            'telegram_chat_id' => null,
        ]);

        // 4. Regular user with telegram_chat_id (should NOT receive admin notification)
        User::factory()->create([
            'role' => 'user',
            'telegram_chat_id' => '400004',
        ]);

        // 5. Admin without any telegram ID
        User::factory()->create([
            'role' => 'admin',
            'telegram_chat_id' => null,
            'phone_number' => null,
        ]);

        // Creating user who submits ticket
        $applicant = User::factory()->create([
            'role' => 'user',
            'target' => 'lppm',
        ]);

        $response = $this->actingAs($applicant)->post(route('tickets.store'), [
            'requester_name' => 'Ahmad Kasim',
            'whatsapp_number' => '081234567890',
            'description' => 'Permintaan update data dan konfigurasi website baru.',
            'priority' => 'urgent',
            'target' => 'lppm',
        ]);

        $response->assertRedirect(route('dashboard'));

        $ticket = Ticket::where('requester_name', 'Ahmad Kasim')->firstOrFail();

        // Should be sent to 3 chat IDs: 100001 (Admin), 200002 (Supervisor), 300003 (Superadmin)
        Http::assertSentCount(3);

        Http::assertSent(function ($request) use ($ticket): bool {
            return $request->url() === 'https://api.telegram.org/bottest-bot-token/sendMessage'
                && in_array($request['chat_id'], ['100001', '200002', '300003'], true)
                && str_contains($request['text'], $ticket->ticket_number)
                && str_contains($request['text'], 'Ahmad Kasim');
        });
    }

    public function test_telegram_notification_is_skipped_silently_if_token_not_set(): void
    {
        Config::set('services.telegram.bot_token', null);
        Http::fake();

        User::factory()->create([
            'role' => 'admin',
            'telegram_chat_id' => '100001',
        ]);

        $applicant = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($applicant)->post(route('tickets.store'), [
            'requester_name' => 'Siti Nurhaliza',
            'whatsapp_number' => '081234567890',
            'description' => 'Mohon bantuan penyesuaian halaman profil lembaga.',
            'priority' => 'relaxed',
            'target' => 'lpm',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('tickets', [
            'requester_name' => 'Siti Nurhaliza',
        ]);

        Http::assertNothingSent();
    }

    public function test_telegram_webhook_links_user_chat_id_when_contact_is_shared(): void
    {
        Config::set('services.telegram.bot_token', 'test-bot-token');
        Http::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'phone_number' => '081234567890',
            'telegram_chat_id' => null,
        ]);

        $response = $this->postJson(route('telegram.webhook'), [
            'update_id' => 123456,
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => 987654321],
                'contact' => [
                    'phone_number' => '+6281234567890',
                    'first_name' => 'Admin',
                    'user_id' => 987654321,
                ],
            ],
        ]);

        $response->assertOk();

        $admin->refresh();
        $this->assertSame('987654321', $admin->telegram_chat_id);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.telegram.org/bottest-bot-token/sendMessage'
                && $request['chat_id'] === '987654321'
                && str_contains($request['text'], 'Berhasil Terhubung');
        });
    }

    public function test_telegram_webhook_prompts_for_contact_on_start_command(): void
    {
        Config::set('services.telegram.bot_token', 'test-bot-token');
        Http::fake();

        $response = $this->postJson(route('telegram.webhook'), [
            'update_id' => 123456,
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => 987654321],
                'text' => '/start',
            ],
        ]);

        $response->assertOk();

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.telegram.org/bottest-bot-token/sendMessage'
                && $request['chat_id'] === '987654321'
                && isset($request['reply_markup']['keyboard']);
        });
    }
}
