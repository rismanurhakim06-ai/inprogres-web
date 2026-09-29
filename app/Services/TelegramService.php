<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramService
{
    protected ?string $botToken;

    public function __construct(?string $botToken = null)
    {
        $this->botToken = $botToken ?? config('services.telegram.bot_token');
    }

    /**
     * Check if Telegram Bot Token is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->botToken);
    }

    /**
     * Send a message to a specific Telegram chat ID.
     */
    public function sendMessage(string|int $chatId, string $text, string $parseMode = 'HTML'): bool
    {
        if (! $this->isConfigured()) {
            Log::info('Telegram notification skipped: TELEGRAM_BOT_TOKEN is not configured.');

            return false;
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => (string) $chatId,
                'text' => $text,
                'parse_mode' => $parseMode,
            ]);

            if (! $response->successful()) {
                Log::warning('Telegram API returned non-successful response', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send Telegram message due to an exception', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Build the summary message for a new ticket.
     */
    public function formatTicketMessage(Ticket $ticket): string
    {
        $ticketNumber = htmlspecialchars((string) $ticket->ticket_number, ENT_QUOTES, 'UTF-8');
        $requesterName = htmlspecialchars((string) $ticket->requester_name, ENT_QUOTES, 'UTF-8');
        $whatsappNumber = htmlspecialchars((string) ($ticket->whatsapp_number ?? '-'), ENT_QUOTES, 'UTF-8');
        $targetLabel = htmlspecialchars($ticket->target?->label() ?? (string) $ticket->target?->value ?? '-', ENT_QUOTES, 'UTF-8');
        $priorityLabel = htmlspecialchars($ticket->priority?->label() ?? (string) $ticket->priority?->value ?? '-', ENT_QUOTES, 'UTF-8');
        $createdAt = $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i');
        $description = htmlspecialchars(Str::limit((string) $ticket->description, 350), ENT_QUOTES, 'UTF-8');
        $ticketUrl = route('home', ['ticket' => $ticket->ticket_number]);

        return "🔔 <b>NOTIFIKASI TIKET BARU MASUK</b>\n\n"
            ."📌 <b>No. Tiket:</b> <code>{$ticketNumber}</code>\n"
            ."👤 <b>Pengaju:</b> {$requesterName}\n"
            ."📱 <b>No. WhatsApp:</b> {$whatsappNumber}\n"
            ."🎯 <b>Target/Unit:</b> {$targetLabel}\n"
            ."⚡ <b>Prioritas:</b> {$priorityLabel}\n"
            ."🕒 <b>Waktu:</b> {$createdAt} WIB\n\n"
            ."📝 <b>Deskripsi Pengajuan:</b>\n"
            ."<i>{$description}</i>\n\n"
            ."🔗 <a href=\"{$ticketUrl}\">Buka & Periksa Tiket</a>";
    }

    /**
     * Send notification for a newly created ticket to all Admin and Supervisor users.
     *
     * @return array<string, bool>
     */
    public function sendTicketCreatedNotification(Ticket $ticket): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $users = User::query()
            ->whereIn('role', ['admin', 'supervisor', 'superadmin'])
            ->get();

        /** @var array<int, string> $chatIds */
        $chatIds = $users
            ->map(fn (User $user): ?string => $user->getTelegramChatId())
            ->filter(fn (?string $chatId): bool => ! empty($chatId))
            ->unique()
            ->values()
            ->all();

        $defaultChatId = config('services.telegram.default_chat_id');
        if (! empty($defaultChatId) && ! in_array((string) $defaultChatId, $chatIds, true)) {
            $chatIds[] = (string) $defaultChatId;
        }

        if (empty($chatIds)) {
            Log::info('No eligible Telegram Chat IDs found for Admin and Supervisor.', [
                'ticket_number' => $ticket->ticket_number,
            ]);

            return [];
        }

        $message = $this->formatTicketMessage($ticket);
        $results = [];

        foreach ($chatIds as $chatId) {
            $results[$chatId] = $this->sendMessage($chatId, $message);
        }

        return $results;
    }

    /**
     * Normalize a phone number to Indonesian standard digits (628...).
     */
    public static function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    /**
     * Find a user in the database matching the given phone number.
     */
    public function findUserByPhoneNumber(string $phone): ?User
    {
        $target = self::normalizePhoneNumber($phone);

        if (empty($target)) {
            return null;
        }

        return User::all()->first(function (User $user) use ($target): bool {
            if (empty($user->phone_number)) {
                return false;
            }

            return self::normalizePhoneNumber($user->phone_number) === $target;
        });
    }

    /**
     * Send a prompt asking the user to share their contact (phone number).
     */
    public function sendContactPrompt(string|int $chatId): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => (string) $chatId,
                'text' => "👋 <b>Selamat Datang di Bot Notifikasi Tiket!</b>\n\nUntuk menghubungkan akun Anda, silakan tekan tombol di bawah ini untuk membagikan nomor telepon Telegram Anda.",
                'parse_mode' => 'HTML',
                'reply_markup' => [
                    'keyboard' => [
                        [
                            [
                                'text' => '📱 Bagikan Nomor Telepon Telegram',
                                'request_contact' => true,
                            ],
                        ],
                    ],
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true,
                ],
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Telegram sendContactPrompt exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Handle incoming webhook updates from Telegram.
     *
     * @param  array<string, mixed>  $update
     */
    public function handleWebhook(array $update): bool
    {
        $message = $update['message'] ?? null;
        if (! $message) {
            return false;
        }

        $chatId = $message['chat']['id'] ?? null;
        if (! $chatId) {
            return false;
        }

        if (! empty($message['contact']['phone_number'])) {
            $phone = (string) $message['contact']['phone_number'];
            $user = $this->findUserByPhoneNumber($phone);

            if ($user) {
                $user->update(['telegram_chat_id' => (string) $chatId]);

                $this->sendMessage(
                    $chatId,
                    "✅ <b>Nomor Telepon Berhasil Terhubung!</b>\n\n"
                    ."Halo <b>{$user->name}</b>, nomor Anda (<code>{$phone}</code>) telah terverifikasi di Sistem Tiket dengan role <b>{$user->role}</b>.\n\n"
                    .'Chat ID Telegram Anda telah otomatis disimpan. Anda akan menerima notifikasi setiap ada tiket baru masuk.'
                );

                return true;
            }

            $this->sendMessage(
                $chatId,
                "⚠️ <b>Nomor Belum Terdaftar di Sistem Tiket</b>\n\n"
                ."Nomor telepon Anda (<code>{$phone}</code>) belum terdaftar di Sistem Tiket.\n\n"
                .'Silakan hubungi Administrator atau pastikan nomor telepon ini sudah dimasukkan ke akun Anda di sistem tiket.'
            );

            return true;
        }

        $text = trim((string) ($message['text'] ?? ''));
        if (str_starts_with($text, '/start')) {
            $this->sendContactPrompt($chatId);

            return true;
        }

        return true;
    }
}
