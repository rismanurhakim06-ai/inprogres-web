<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketTarget;
use App\Models\RoleDashboardSetting;
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
            'target' => 'lppm',
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

    public function test_telegram_service_escapes_a_custom_target(): void
    {
        $ticket = Ticket::factory()->create([
            'target' => 'Portal <script>alert(1)</script>',
        ]);

        $message = (new TelegramService('dummy-token'))->formatTicketMessage($ticket);

        $this->assertStringContainsString('Portal &lt;script&gt;alert(1)&lt;/script&gt;', $message);
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

        // 3. Superadmin with Telegram chat ID
        User::factory()->create([
            'role' => 'superadmin',
            'telegram_chat_id' => '300003',
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
            'phone_number' => '081234567890',
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

    public function test_superadmin_can_select_telegram_recipients_for_new_tickets(): void
    {
        Config::set('services.telegram.bot_token', 'test-bot-token');
        Config::set('services.telegram.default_chat_id', null);
        Http::fake();

        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'telegram_chat_id' => '300003',
        ]);
        User::factory()->create(['role' => 'admin', 'telegram_chat_id' => '100001']);
        User::factory()->create(['role' => 'supervisor', 'telegram_chat_id' => '200002']);

        $settings = [];
        foreach (RoleDashboardSetting::ROLE_LABELS as $role => $label) {
            $settings[$role] = ['_present' => '1'];
        }
        $settings['supervisor']['telegram_ticket_notifications'] = '1';

        $this->actingAs($superadmin)
            ->put(route('settings.roles.update'), ['settings' => $settings])
            ->assertRedirect(route('settings.roles.edit'));

        $applicant = User::factory()->create(['role' => 'user']);

        $this->actingAs($applicant)->post(route('tickets.store'), [
            'requester_name' => 'Rina Rahma',
            'whatsapp_number' => '081234567890',
            'description' => 'Permintaan bantuan teknis.',
            'priority' => 'urgent',
            'target' => 'lppm',
        ])->assertRedirect(route('dashboard'));

        $ticket = Ticket::where('requester_name', 'Rina Rahma')->firstOrFail();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => $request['chat_id'] === '200002'
            && str_contains($request['text'], $ticket->ticket_number));
    }

    public function test_registration_notification_only_reaches_selected_roles(): void
    {
        Config::set('services.telegram.bot_token', 'test-bot-token');
        Config::set('services.telegram.default_chat_id', null);
        Http::fake();

        User::factory()->create(['role' => 'admin', 'telegram_chat_id' => '100001']);
        User::factory()->create(['role' => 'supervisor', 'telegram_chat_id' => '200002']);
        User::factory()->create(['role' => 'superadmin', 'telegram_chat_id' => '300003']);

        foreach (RoleDashboardSetting::TELEGRAM_NOTIFICATION_ROLES as $role => $label) {
            $features = RoleDashboardSetting::defaultFeaturesFor($role);
            $features['telegram_registration_notifications'] = $role === 'admin';

            RoleDashboardSetting::query()->updateOrCreate(
                ['role' => $role],
                ['features' => $features],
            );
        }

        $this->post(route('register'), [
            'name' => '<script>Rina</script>',
            'email' => 'rina@example.com',
            'phone_number' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['email' => 'rina@example.com']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => $request['chat_id'] === '100001'
            && str_contains($request['text'], '&lt;script&gt;Rina&lt;/script&gt;')
            && str_contains($request['text'], 'rina@example.com')
            && str_contains($request['text'], 'Menunggu persetujuan'));
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
