<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramUpdateHandler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('telegram:poll {--once : Process a single batch of updates and exit}')]
#[Description('Fetch Telegram updates with long polling (for local development, where the webhook is unreachable)')]
class TelegramPollCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TelegramClient $telegram, TelegramUpdateHandler $handler): int
    {
        $telegram->deleteWebhook();
        $telegram->setMyCommands(TelegramUpdateHandler::COMMANDS);

        $this->components->info('Listening for Telegram updates. Press Ctrl+C to stop.');

        $offset = 0;

        do {
            foreach ($telegram->getUpdates($offset, $this->option('once') ? 0 : 30) as $update) {
                $offset = $update['update_id'] + 1;

                try {
                    $handler->handle($update);
                } catch (Throwable $exception) {
                    report($exception);
                    $this->components->error($exception->getMessage());
                }
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }
}
