<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the Telegram Bot API.
 *
 * @see https://core.telegram.org/bots/api
 */
class TelegramClient
{
    /**
     * Send a message to the given chat.
     *
     * @param  array<int, array<int, array{text: string, callback_data: string}>>|null  $buttons
     */
    public function sendMessage(int $chatId, string $text, ?array $buttons = null): Response
    {
        return $this->request()->post('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            ...$this->replyMarkup($buttons),
        ]);
    }

    /**
     * Replace the text and buttons of a message the bot already sent.
     *
     * @param  array<int, array<int, array{text: string, callback_data: string}>>|null  $buttons
     */
    public function editMessageText(int $chatId, int $messageId, string $text, ?array $buttons = null): Response
    {
        return $this->request()->post('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
            ...$this->replyMarkup($buttons),
        ]);
    }

    /**
     * Acknowledge a button press, optionally showing a short toast to the user.
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): Response
    {
        return $this->request()->post('answerCallbackQuery', array_filter([
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
        ]));
    }

    /**
     * Long-poll Telegram for new updates.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUpdates(int $offset, int $timeoutInSeconds = 30): array
    {
        return $this->request()
            ->timeout($timeoutInSeconds + 10)
            ->post('getUpdates', [
                'offset' => $offset,
                'timeout' => $timeoutInSeconds,
                'allowed_updates' => ['message', 'callback_query'],
            ])
            ->throw()
            ->json('result', []);
    }

    /**
     * Point Telegram at the given webhook URL.
     */
    public function setWebhook(string $url, string $secretToken): Response
    {
        return $this->request()->post('setWebhook', [
            'url' => $url,
            'secret_token' => $secretToken,
            'allowed_updates' => ['message', 'callback_query'],
        ]);
    }

    /**
     * Remove the webhook so updates can be fetched with long polling.
     */
    public function deleteWebhook(): Response
    {
        return $this->request()->post('deleteWebhook');
    }

    /**
     * Register the command list shown in the Telegram "/" menu.
     *
     * @param  array<string, string>  $commands
     */
    public function setMyCommands(array $commands): Response
    {
        return $this->request()->post('setMyCommands', [
            'commands' => collect($commands)
                ->map(fn (string $description, string $command): array => [
                    'command' => $command,
                    'description' => $description,
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * @param  array<int, array<int, array{text: string, callback_data: string}>>|null  $buttons
     * @return array{reply_markup?: array{inline_keyboard: array<int, array<int, array{text: string, callback_data: string}>>}}
     */
    private function replyMarkup(?array $buttons): array
    {
        if ($buttons === null) {
            return [];
        }

        return ['reply_markup' => ['inline_keyboard' => $buttons]];
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl('https://api.telegram.org/bot'.config('services.telegram.bot_token'))
            ->acceptJson()
            ->asJson();
    }
}
