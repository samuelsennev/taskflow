<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('delete_task')]
#[Description('Permanently delete a task. Prefer complete_task when the user finished it; only delete when they ask to remove it.')]
#[IsDestructive]
class DeleteTaskTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'task_id' => ['required', 'integer'],
        ]);

        $task = $request->user()->tasks()->whereKey($validated['task_id'])->first();

        if ($task === null) {
            return Response::error('Task not found. Use list_tasks to get the user\'s task ids.');
        }

        $task->delete();

        return Response::text("Deleted task \"{$task->title}\".");
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
        ];
    }
}
