<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\PresentsTasks;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_tasks')]
#[Description('List the user\'s tasks, ordered by due date. Use the scope to narrow the list (e.g. "today" for what is due today, "overdue" for late tasks). The response includes today\'s date in the user\'s timezone.')]
#[IsReadOnly]
class ListTasksTool extends Tool
{
    use PresentsTasks;

    private const int LIMIT = 100;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'scope' => ['nullable', Rule::in(['pending', 'today', 'overdue', 'upcoming', 'completed', 'all'])],
            'project_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $query = $request->user()->tasks()->with('project')->orderBy('due_date')->oldest();

        match ($validated['scope'] ?? 'pending') {
            'today' => $query->dueToday(),
            'overdue' => $query->whereNull('completed_at')->whereDate('due_date', '<', today()),
            'upcoming' => $query->whereNull('completed_at')->whereDate('due_date', '>', today()),
            'completed' => $query->whereNotNull('completed_at')->reorder()->latest('completed_at'),
            'all' => null,
            default => $query->whereNull('completed_at'),
        };

        if (isset($validated['project_id'])) {
            $query->where('project_id', $validated['project_id']);
        }

        if (filled($validated['search'] ?? null)) {
            $query->whereLike('title', '%'.$validated['search'].'%');
        }

        $total = (clone $query)->count();

        return Response::json([
            'today' => today()->toDateString(),
            'total' => $total,
            'tasks' => $query->limit(self::LIMIT)->get()->map(fn (Task $task): array => $this->presentTask($task))->all(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'scope' => $schema->string()
                ->enum(['pending', 'today', 'overdue', 'upcoming', 'completed', 'all'])
                ->description('Which tasks to list: "pending" (not completed, the default), "today" (due today, completed or not), "overdue" (pending and due before today), "upcoming" (pending and due after today), "completed" or "all".'),
            'project_id' => $schema->integer()
                ->description('Only list tasks from this project. Get project ids from list_projects.'),
            'search' => $schema->string()
                ->description('Only list tasks whose title contains this text.'),
        ];
    }
}
