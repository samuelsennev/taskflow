<?php

namespace App\Services\Telegram;

use App\Models\Task;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Turns incoming Telegram updates (messages and button presses) into task actions.
 */
class TelegramUpdateHandler
{
    /**
     * Commands shown in the Telegram "/" menu.
     *
     * @var array<string, string>
     */
    public const array COMMANDS = [
        'hoje' => 'Tarefas de hoje',
        'tarefas' => 'Todas as tarefas pendentes',
        'projetos' => 'Tarefas por projeto',
        'nova' => 'Criar tarefa: /nova Título amanhã',
        'ajuda' => 'Como usar o bot',
    ];

    private const int LIST_LIMIT = 20;

    public function __construct(private TelegramClient $telegram) {}

    /**
     * Cache key holding the user id for a pending account link token.
     */
    public static function linkCacheKey(string $token): string
    {
        return "telegram:link:{$token}";
    }

    /**
     * Handle a single update from the Telegram Bot API.
     *
     * @param  array<string, mixed>  $update
     */
    public function handle(array $update): void
    {
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);

            return;
        }

        if (isset($update['message']['text'], $update['message']['chat']['id'])) {
            $this->handleMessage((int) $update['message']['chat']['id'], trim($update['message']['text']));
        }
    }

    private function handleMessage(int $chatId, string $text): void
    {
        [$command, $arguments] = $this->parseCommand($text);

        if ($command === 'start' && $arguments !== '') {
            $this->connect($chatId, $arguments);

            return;
        }

        $user = User::firstWhere('telegram_chat_id', $chatId);

        if ($user === null) {
            $this->telegram->sendMessage($chatId, 'Esta conversa ainda não está ligada a uma conta do Taskflow. Abra o Taskflow e clique em <b>Conectar Telegram</b>.');

            return;
        }

        match ($command) {
            'hoje' => $this->sendTaskList($user, 'today'),
            'tarefas' => $this->sendTaskList($user, 'pending'),
            'projetos' => $this->sendProjects($user),
            'nova' => $this->createTask($user, $arguments),
            null => $this->createTask($user, $text),
            default => $this->telegram->sendMessage($chatId, $this->helpText()),
        };
    }

    /**
     * @param  array<string, mixed>  $callbackQuery
     */
    private function handleCallbackQuery(array $callbackQuery): void
    {
        $chatId = (int) data_get($callbackQuery, 'message.chat.id');
        $messageId = (int) data_get($callbackQuery, 'message.message_id');
        $user = User::firstWhere('telegram_chat_id', $chatId);

        if ($user === null) {
            $this->telegram->answerCallbackQuery($callbackQuery['id'], 'Conecte sua conta do Taskflow primeiro.');

            return;
        }

        [$action, $value, $scope] = array_pad(explode(':', (string) ($callbackQuery['data'] ?? ''), 3), 3, null);

        match ($action) {
            'done' => $this->completeTask($user, $callbackQuery['id'], (int) $value, $scope ?? 'pending', $messageId),
            'due' => $this->createPendingTask($user, $callbackQuery['id'], (int) $value, $messageId),
            'project' => $this->showProject($user, $callbackQuery['id'], (int) $value),
            default => $this->telegram->answerCallbackQuery($callbackQuery['id']),
        };
    }

    /**
     * Split "/command@BotName arguments" into its command and arguments.
     *
     * @return array{0: string|null, 1: string}
     */
    private function parseCommand(string $text): array
    {
        if (preg_match('/^\/(\w+)(?:@\w+)?\s*(.*)$/su', $text, $matches)) {
            return [Str::lower($matches[1]), trim($matches[2])];
        }

        return [null, $text];
    }

    private function connect(int $chatId, string $token): void
    {
        $userId = Cache::pull(self::linkCacheKey($token));
        $user = is_int($userId) ? User::find($userId) : null;

        if ($user === null) {
            $this->telegram->sendMessage($chatId, 'Este link expirou. Gere um novo no Taskflow clicando em <b>Conectar Telegram</b>.');

            return;
        }

        User::where('telegram_chat_id', $chatId)->whereKeyNot($user->id)->update(['telegram_chat_id' => null]);
        $user->forceFill(['telegram_chat_id' => $chatId])->save();

        $this->telegram->sendMessage($chatId, 'Pronto, '.e($user->name)."! Sua conta está conectada.\n\n".$this->helpText());
    }

    private function sendTaskList(User $user, string $scope): void
    {
        [$text, $buttons] = $this->taskList($user, $scope);

        $this->telegram->sendMessage($user->telegram_chat_id, $text, $buttons);
    }

    private function sendProjects(User $user): void
    {
        $projects = $user->projects()
            ->withCount(['tasks as pending_tasks_count' => fn ($query) => $query->whereNull('completed_at')])
            ->orderBy('name')
            ->get();

        if ($projects->isEmpty()) {
            $this->telegram->sendMessage($user->telegram_chat_id, 'Você ainda não tem projetos. Crie um no Taskflow.');

            return;
        }

        $this->telegram->sendMessage(
            $user->telegram_chat_id,
            "<b>Seus projetos</b>\nEscolha um para ver as tarefas pendentes.",
            $projects->map(fn ($project): array => [[
                'text' => "📁 {$project->name} ({$project->pending_tasks_count})",
                'callback_data' => "project:{$project->id}",
            ]])->all(),
        );
    }

    private function showProject(User $user, string $callbackQueryId, int $projectId): void
    {
        $this->telegram->answerCallbackQuery($callbackQueryId);

        $this->sendTaskList($user, "p{$projectId}");
    }

    private function completeTask(User $user, string $callbackQueryId, int $taskId, string $scope, int $messageId): void
    {
        $task = $user->tasks()->find($taskId);

        if ($task === null) {
            $this->telegram->answerCallbackQuery($callbackQueryId, 'Tarefa não encontrada.');

            return;
        }

        $task->update(['completed_at' => now()]);

        $this->telegram->answerCallbackQuery($callbackQueryId, 'Concluída! ✅');

        [$text, $buttons] = $this->taskList($user, $scope);

        $this->telegram->editMessageText($user->telegram_chat_id, $messageId, $text, $buttons);
    }

    private function createTask(User $user, string $text): void
    {
        [$title, $dueDate] = $this->extractDueDate($text);

        if ($title === '') {
            $this->telegram->sendMessage($user->telegram_chat_id, 'Me diga o título da tarefa, por exemplo: <code>/nova Comprar leite amanhã</code>');

            return;
        }

        $title = Str::limit($title, 255, '');

        if ($dueDate === null) {
            Cache::put($this->pendingTaskCacheKey($user), $title, now()->addMinutes(10));

            $this->telegram->sendMessage($user->telegram_chat_id, 'Para quando é <b>'.e($title).'</b>?', [
                [
                    ['text' => 'Hoje', 'callback_data' => 'due:0'],
                    ['text' => 'Amanhã', 'callback_data' => 'due:1'],
                ],
                [
                    ['text' => 'Em 1 semana', 'callback_data' => 'due:7'],
                ],
            ]);

            return;
        }

        if ($dueDate->lt(today())) {
            $this->telegram->sendMessage($user->telegram_chat_id, 'A data de entrega não pode estar no passado.');

            return;
        }

        $task = $user->tasks()->create(['title' => $title, 'due_date' => $dueDate]);

        $this->telegram->sendMessage($user->telegram_chat_id, $this->createdText($task));
    }

    private function createPendingTask(User $user, string $callbackQueryId, int $daysFromToday, int $messageId): void
    {
        $title = Cache::pull($this->pendingTaskCacheKey($user));

        if ($title === null) {
            $this->telegram->answerCallbackQuery($callbackQueryId, 'Essa tarefa expirou. Envie de novo.');

            return;
        }

        $task = $user->tasks()->create([
            'title' => $title,
            'due_date' => today()->addDays(max(0, $daysFromToday)),
        ]);

        $this->telegram->answerCallbackQuery($callbackQueryId, 'Criada!');
        $this->telegram->editMessageText($user->telegram_chat_id, $messageId, $this->createdText($task));
    }

    /**
     * Build the text and "complete" buttons for a task list.
     *
     * Scopes: "today", "pending" or "p{projectId}".
     *
     * @return array{0: string, 1: array<int, array<int, array{text: string, callback_data: string}>>|null}
     */
    private function taskList(User $user, string $scope): array
    {
        $query = $user->tasks()->with('project')->orderBy('due_date')->oldest();

        if ($scope === 'today') {
            $heading = 'Hoje · '.today()->format('d/m');
            $query->dueToday()->reorder()->orderByRaw('completed_at is not null')->oldest();
        } elseif (str_starts_with($scope, 'p')) {
            $project = $user->projects()->find((int) substr($scope, 1));

            if ($project === null) {
                return ['Projeto não encontrado.', null];
            }

            $heading = '📁 '.$project->name;
            $query->whereBelongsTo($project)->whereNull('completed_at');
        } else {
            $heading = 'Tarefas pendentes';
            $query->whereNull('completed_at');
        }

        $total = (clone $query)->count();
        $tasks = $query->limit(self::LIST_LIMIT)->get();

        if ($tasks->isEmpty()) {
            return ['<b>'.e($heading)."</b>\n\nNada pendente por aqui 🎉", null];
        }

        $lines = $tasks->values()->map(function (Task $task, int $index) use ($scope): string {
            $title = $task->isCompleted() ? '✅ <s>'.e($task->title).'</s>' : e($task->title);
            $details = collect([
                $scope === 'today' ? null : $this->formatDueDate($task->due_date),
                $task->project === null || str_starts_with($scope, 'p') ? null : '📁 '.e($task->project->name),
            ])->filter()->implode(' · ');

            return ($index + 1).". {$title}".($details === '' ? '' : " <i>— {$details}</i>");
        });

        if ($total > self::LIST_LIMIT) {
            $lines->push('… e mais '.($total - self::LIST_LIMIT).' tarefas.');
        }

        $buttons = $tasks
            ->reject(fn (Task $task): bool => $task->isCompleted())
            ->map(fn (Task $task): array => [[
                'text' => '✅ '.Str::limit($task->title, 30),
                'callback_data' => "done:{$task->id}:{$scope}",
            ]])
            ->values()
            ->all();

        return ['<b>'.e($heading)."</b>\n\n".$lines->implode("\n"), $buttons ?: null];
    }

    /**
     * Pull a trailing due date ("hoje", "amanhã", "dd/mm" or "dd/mm/yyyy") off the end of the text.
     *
     * @return array{0: string, 1: CarbonInterface|null}
     */
    private function extractDueDate(string $text): array
    {
        if (preg_match('/^(.*?)\s+(hoje|amanh[ãa])$/iu', $text, $matches)) {
            return [trim($matches[1]), Str::lower($matches[2]) === 'hoje' ? today() : today()->addDay()];
        }

        if (preg_match('/^(.*?)\s+(\d{1,2})\/(\d{1,2})(?:\/(\d{4}|\d{2}))?$/u', $text, $matches)) {
            $hasYear = ! empty($matches[4]);
            $year = $hasYear ? (int) Str::padLeft($matches[4], 4, '20') : today()->year;

            if (checkdate((int) $matches[3], (int) $matches[2], $year)) {
                $dueDate = Carbon::create($year, (int) $matches[3], (int) $matches[2])->startOfDay();

                if (! $hasYear && $dueDate->lt(today())) {
                    $dueDate->addYear();
                }

                return [trim($matches[1]), $dueDate];
            }
        }

        return [$text, null];
    }

    private function formatDueDate(CarbonInterface $dueDate): string
    {
        return match (true) {
            $dueDate->isToday() => 'hoje',
            $dueDate->isTomorrow() => 'amanhã',
            $dueDate->lt(today()) => '⚠️ '.$dueDate->format('d/m').' (atrasada)',
            default => $dueDate->format('d/m'),
        };
    }

    private function createdText(Task $task): string
    {
        return '📝 Tarefa criada: <b>'.e($task->title).'</b> — para '.$this->formatDueDate($task->due_date).'.';
    }

    private function helpText(): string
    {
        return implode("\n", [
            '<b>Taskflow no Telegram</b>',
            '',
            '/hoje — tarefas de hoje',
            '/tarefas — todas as pendentes',
            '/projetos — tarefas por projeto',
            '/nova Título [hoje | amanhã | dd/mm] — cria uma tarefa',
            '',
            'Dica: qualquer mensagem sem comando vira uma tarefa nova.',
        ]);
    }

    private function pendingTaskCacheKey(User $user): string
    {
        return "telegram:pending-task:{$user->id}";
    }
}
