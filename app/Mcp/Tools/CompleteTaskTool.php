<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\PresentsTasks;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('complete_task')]
#[Description('Mark a task as completed, or as not completed again with completed set to false.')]
#[IsIdempotent]
class CompleteTaskTool extends Tool
{
    use PresentsTasks;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'task_id' => ['required', 'integer'],
            'completed' => ['nullable', 'boolean'],
        ]);

        $task = $request->user()->tasks()->whereKey($validated['task_id'])->first();

        if ($task === null) {
            return Response::error('Task not found. Use list_tasks to get the user\'s task ids.');
        }

        $isCompleting = $validated['completed'] ?? true;

        if ($isCompleting !== $task->isCompleted()) {
            $task->update(['completed_at' => $isCompleting ? now() : null]);
        }

        return Response::json(['task' => $this->presentTask($task->load('project'))]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()
                ->description('Id of the task, from list_tasks.')
                ->required(),
            'completed' => $schema->boolean()
                ->description('true to mark it as done (the default), false to reopen it.')
                ->default(true),
        ];
    }
}
