<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramUpdateHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class TelegramConnectionController extends Controller
{
    /**
     * Send the user to the bot with a one-time token that links their chat to this account.
     */
    public function store(Request $request): Response
    {
        abort_if(blank(config('services.telegram.bot_username')), 503, 'O bot do Telegram não está configurado.');

        $token = Str::random(32);

        Cache::put(TelegramUpdateHandler::linkCacheKey($token), $request->user()->id, now()->addMinutes(15));

        return Inertia::location('https://t.me/'.config('services.telegram.bot_username').'?start='.$token);
    }

    /**
     * Unlink the user's Telegram chat.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['telegram_chat_id' => null])->save();

        return back();
    }
}
