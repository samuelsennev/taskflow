<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramUpdateHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramWebhookController extends Controller
{
    /**
     * Handle an update pushed by Telegram.
     */
    public function __invoke(Request $request, TelegramUpdateHandler $handler): Response
    {
        $secret = config('services.telegram.webhook_secret');

        abort_unless(
            filled($secret) && hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')),
            403,
        );

        $handler->handle($request->all());

        return response()->noContent();
    }
}
