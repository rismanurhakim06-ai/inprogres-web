<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class TestTelegramCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test {chat_id? : Telegram Chat ID tujuan pengujian}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim pesan uji coba menggunakan Telegram Bot API';

    public function handle(TelegramService $telegramService): int
    {
        if (! $telegramService->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN belum dikonfigurasi di file .env.');
            $this->line('Silakan isi TELEGRAM_BOT_TOKEN=... terlebih dahulu.');

            return self::FAILURE;
        }

        $targetChatId = $this->argument('chat_id');

        if (empty($targetChatId)) {
            $targetChatId = config('services.telegram.default_chat_id');
        }

        if (empty($targetChatId)) {
            $adminOrSupervisor = User::query()
                ->whereIn('role', ['admin', 'supervisor', 'superadmin'])
                ->get()
                ->first(fn (User $user): bool => ! empty($user->getTelegramChatId()));

            $targetChatId = $adminOrSupervisor?->getTelegramChatId();
        }

        if (empty($targetChatId)) {
            $this->warn('Tidak ada Chat ID yang ditemukan.');
            $this->line('Gunakan: php artisan telegram:test <chat_id>');

            return self::FAILURE;
        }

        $this->info("Mengirim pesan uji coba ke Chat ID: {$targetChatId}...");

        $message = "🤖 <b>TES KONEKSI TELEGRAM BOT</b>\n\n"
            ."Halo! Integrasi Telegram Bot API dengan Sistem Tiket telah berhasil terhubung.\n"
            .'Waktu kirim: '.now()->format('d/m/Y H:i:s').' WIB';

        $success = $telegramService->sendMessage($targetChatId, $message);

        if ($success) {
            $this->info('Pesan uji coba berhasil terkirim!');

            return self::SUCCESS;
        }

        $this->error('Gagal mengirim pesan. Periksa token bot dan pastikan user telah memulai chat (/start) dengan bot.');

        return self::FAILURE;
    }
}
