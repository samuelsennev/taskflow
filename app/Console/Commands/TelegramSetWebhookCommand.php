<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramUpdateHandler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('telegram:set-webhook {url? : Public webhook URL (defaults to the telegram.webhook route)}')]
#[Description('Register the Telegram webhook and the bot command menu')]
class TelegramSetWebhookCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TelegramClient $telegram): int
    {
        $secret = config('services.telegram.webhook_secret');

        if (blank($secret)) {
            $this->components->error('Set TELEGRAM_WEBHOOK_SECRET in your .env first.');

            return self::FAILURE;
        }

        $url = $this->argument('url') ?? route('telegram.webhook');
        $response = $telegram->setWebhook($url, $secret);

        if ($response->failed()) {
            $this->components->error('Telegram rejected the webhook: '.$response->json('description'));

            return self::FAILURE;
        }

        $telegram->setMyCommands(TelegramUpdateHandler::COMMANDS);

        $this->components->info("Webhook set to {$url}.");

        return self::SUCCESS;
    }
}
