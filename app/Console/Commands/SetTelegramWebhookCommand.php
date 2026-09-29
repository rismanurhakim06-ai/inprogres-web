<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook {url? : URL publik endpoint webhook (misal: https://domain.com/telegram/webhook)} {--info : Tampilkan informasi webhook saat ini} {--delete : Hapus webhook saat ini}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kelola webhook Telegram Bot API untuk menghubungkan bot secara otomatis';

    public function handle(TelegramService $telegramService): int
    {
        if (! $telegramService->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN belum dikonfigurasi di .env.');

            return self::FAILURE;
        }

        $token = config('services.telegram.bot_token');

        if ($this->option('delete')) {
            $response = Http::get("https://api.telegram.org/bot{$token}/deleteWebhook");
            $this->info('Webhook Telegram berhasil dihapus.');

            return self::SUCCESS;
        }

        if ($this->option('info')) {
            $response = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");
            $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $url = $this->argument('url');
        if (empty($url)) {
            $url = url('/telegram/webhook');
        }

        if (! str_starts_with($url, 'https://')) {
            $this->warn('Perhatian: Telegram Webhook mewajibkan URL HTTPS.');
        }

        $this->info("Mendaftarkan webhook ke Telegram: {$url}...");

        $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $url,
        ]);

        if ($response->successful() && ($response->json('ok') ?? false)) {
            $this->info('Webhook berhasil didaftarkan ke Telegram!');

            return self::SUCCESS;
        }

        $this->error('Gagal mendaftarkan webhook: '.($response->json('description') ?? $response->body()));

        return self::FAILURE;
    }
}
