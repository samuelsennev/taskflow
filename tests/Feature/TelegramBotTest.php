<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Telegram\TelegramUpdateHandler;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config([
        'services.telegram.bot_token' => 'test-token',
        'services.telegram.webhook_secret' => 'webhook-secret',
    ]);
});

/**
 * Fake every Bot API call and block anything else from leaving the process.
 */
function fakeTelegramApi(): void
{
    Http::preventStrayRequests();
    Http::fake(['https://api.telegram.org/bottest-token/*' => Http::response(['ok' => true, 'result' => true])]);
}

/**
 * @param  array<string, mixed>  $update
 */
function postTelegramUpdate(array $update, string $secret = 'webhook-secret'): TestResponse
{
    return test()->postJson(route('telegram.webhook'), $update, ['X-Telegram-Bot-Api-Secret-Token' => $secret]);
}

function telegramMessage(int $chatId, string $text): array
{
    return ['update_id' => 1, 'message' => ['message_id' => 10, 'chat' => ['id' => $chatId], 'text' => $text]];
}

function telegramButtonPress(int $chatId, string $data): array
{
    return ['update_id' => 1, 'callback_query' => ['id' => 'cb-1', 'data' => $data, 'message' => ['message_id' => 10, 'chat' => ['id' => $chatId]]]];
}

/**
 * The text of the single Bot API call made to the given method.
 */
function sentTelegramText(string $method): string
{
    $requests = Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), "/{$method}"));

    expect($requests)->toHaveCount(1);

    return $requests->first()[0]['text'];
}

test('the webhook rejects requests without the secret token', function () {
    fakeTelegramApi();

    postTelegramUpdate(telegramMessage(12345, '/hoje'), 'wrong-secret')->assertForbidden();

    Http::assertNothingSent();
});

test('starting the bot with a valid link token connects the chat to the user', function () {
    fakeTelegramApi();
    $user = User::factory()->create();
    Cache::put(TelegramUpdateHandler::linkCacheKey('link-token'), $user->id);

    postTelegramUpdate(telegramMessage(12345, '/start link-token'))->assertNoContent();

    expect($user->fresh()->telegram_chat_id)->toBe(12345)
        ->and(Cache::has(TelegramUpdateHandler::linkCacheKey('link-token')))->toBeFalse()
        ->and(sentTelegramText('sendMessage'))->toContain('Sua conta está conectada');
});

test('connecting a chat moves it away from the account that had it before', function () {
    fakeTelegramApi();
    $previousOwner = User::factory()->create(['telegram_chat_id' => 12345]);
    $user = User::factory()->create();
    Cache::put(TelegramUpdateHandler::linkCacheKey('link-token'), $user->id);

    postTelegramUpdate(telegramMessage(12345, '/start link-token'));

    expect($previousOwner->fresh()->telegram_chat_id)->toBeNull()
        ->and($user->fresh()->telegram_chat_id)->toBe(12345);
});

test('starting the bot with an unknown link token does not connect anything', function () {
    fakeTelegramApi();
    $user = User::factory()->create();

    postTelegramUpdate(telegramMessage(12345, '/start expired-token'));

    expect($user->fresh()->telegram_chat_id)->toBeNull()
        ->and(sentTelegramText('sendMessage'))->toContain('Este link expirou');
});

test('chats that are not connected cannot create tasks', function () {
    fakeTelegramApi();

    postTelegramUpdate(telegramMessage(12345, '/nova Comprar leite amanhã'));

    expect(Task::count())->toBe(0)
        ->and(sentTelegramText('sendMessage'))->toContain('Conectar Telegram');
});

test('the today command lists only the user\'s tasks due today', function () {
    fakeTelegramApi();
    $user = User::factory()->create(['telegram_chat_id' => 12345]);
    Task::factory()->for($user)->create(['title' => 'Revisar PR']);
    Task::factory()->for($user)->completed()->create(['title' => 'Responder e-mails']);
    Task::factory()->for($user)->dueInDays(1)->create(['title' => 'Tarefa de amanhã']);
    Task::factory()->create(['title' => 'Tarefa de outra pessoa']);

    postTelegramUpdate(telegramMessage(12345, '/hoje'));

    expect(sentTelegramText('sendMessage'))
        ->toContain('Revisar PR')
        ->toContain('<s>Responder e-mails</s>')
        ->not->toContain('Tarefa de amanhã')
        ->not->toContain('Tarefa de outra pessoa');
});

test('task titles are escaped before being sent as HTML', function () {
    fakeTelegramApi();
    $user = User::factory()->create(['telegram_chat_id' => 12345]);
    Task::factory()->for($user)->create(['title' => '<b>urgente</b> & cia']);

    postTelegramUpdate(telegramMessage(12345, '/hoje'));

    expect(sentTelegramText('sendMessage'))->toContain('&lt;b&gt;urgente&lt;/b&gt; &amp; cia');
});

test('pressing the complete button completes the task and refreshes the list', function () {
    $this->freezeTime();
    fakeTelegramApi();
    $user = User::factory()->create(['telegram_chat_id' => 12345]);
    $task = Task::factory()->for($user)->create(['title' => 'Revisar PR']);

    postTelegramUpdate(telegramButtonPress(12345, "done:{$task->id}:today"));

    expect($task->fresh()->completed_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and(sentTelegramText('editMessageText'))->toContain('<s>Revisar PR</s>');
});

test('users cannot complete another user\'s task from Telegram', function () {
    fakeTelegramApi();
    User::factory()->create(['telegram_chat_id' => 12345]);
    $task = Task::factory()->create();

    postTelegramUpdate(telegramButtonPress(12345, "done:{$task->id}:today"));

    expect($task->fresh()->completed_at)->toBeNull();
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/answerCallbackQuery')
        && $request['text'] === 'Tarefa não encontrada.');
});

test('the new command creates a task with the due date at the end of the text', function (string $text, string $expectedDueDate) {
    $this->travelTo('2026-10-29 09:00');
    fakeTelegramApi();
    $user = User::factory()->create(['telegram_chat_id' => 12345]);

    postTelegramUpdate(telegramMessage(12345, $text));

    expect($user->tasks()->sole())
        ->title->toBe('Comprar leite')
        ->due_date->toDateString()->toBe($expectedDueDate);
})->with([
    'today' => ['/nova Comprar leite hoje', '2026-10-29'],
    'tomorrow' => ['/nova Comprar leite amanhã', '2026-10-30'],
    'day and month' => ['/nova Comprar leite 15/11', '2026-11-15'],
    'day and month already past this year' => ['/nova Comprar leite 10/01', '2027-01-10'],
    'full date' => ['/nova Comprar leite 05/12/2026', '2026-12-05'],
    'plain message without a command' => ['Comprar leite amanhã', '2026-10-30'],
]);

test('the new command rejects a due date in the past', function () {
    $this->travelTo('2026-10-29 09:00');
    fakeTelegramApi();
    $user = User::factory()->create(['telegram_chat_id' => 12345]);

    postTelegramUpdate(telegramMessage(12345, '/nova Comprar leite 01/01/2026'));

    expect($user->tasks()->count())->toBe(0)
        ->and(sentTelegramText('sendMessage'))->toContain('não pode estar no passado');
});

test('a task without a due date asks for one and is created when a date is picked', function () {
    $this->travelTo('2026-10-29 09:00');
    fakeTelegramApi();
    $user = User::factory()->create(['telegram_chat_id' => 12345]);

    postTelegramUpdate(telegramMessage(12345, '/nova Ligar para o banco'));

    expect($user->tasks()->count())->toBe(0)
        ->and(sentTelegramText('sendMessage'))->toContain('Para quando é <b>Ligar para o banco</b>?');

    postTelegramUpdate(telegramButtonPress(12345, 'due:1'));

    expect($user->tasks()->sole())
        ->title->toBe('Ligar para o banco')
        ->due_date->toDateString()->toBe('2026-10-30');
});

test('picking a project lists only its pending tasks', function () {
    fakeTelegramApi();
    $user = User::factory()->create(['telegram_chat_id' => 12345]);
    $project = Project::factory()->for($user)->create(['name' => 'Taskflow']);
    Task::factory()->forProject($project)->create(['title' => 'Bot do Telegram']);
    Task::factory()->forProject($project)->completed()->create(['title' => 'Login com GitHub']);
    Task::factory()->for($user)->create(['title' => 'Fora do projeto']);

    postTelegramUpdate(telegramButtonPress(12345, "project:{$project->id}"));

    expect(sentTelegramText('sendMessage'))
        ->toContain('Taskflow')
        ->toContain('Bot do Telegram')
        ->not->toContain('Login com GitHub')
        ->not->toContain('Fora do projeto');
});

test('users cannot list another user\'s project from Telegram', function () {
    fakeTelegramApi();
    User::factory()->create(['telegram_chat_id' => 12345]);
    $project = Project::factory()->create();
    Task::factory()->forProject($project)->create(['title' => 'Segredo']);

    postTelegramUpdate(telegramButtonPress(12345, "project:{$project->id}"));

    expect(sentTelegramText('sendMessage'))
        ->toBe('Projeto não encontrado.');
});
