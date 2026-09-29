<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramService $telegramService): JsonResponse
    {
        $update = $request->all();

        $telegramService->handleWebhook($update);

        return response()->json(['status' => 'ok']);
    }
}
