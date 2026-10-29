<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\PresentsTasks;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('update_task')]
#[Description('Change a task\'s title, description, due date or project. Only the fields you send are changed. Use complete_task to mark it as done.')]
#[IsIdempotent]
class UpdateTaskTool extends Tool
{
    use PresentsTasks;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();

        $validated = $request->validate([
            'task_id' => ['required', 'integer'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'due_date' => ['sometimes', 'required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'project_id' => ['sometimes', 'nullable', 'integer', Rule::exists('projects', 'id')->where('user_id', $user->getAuthIdentifier())],
        ], [
            'due_date.after_or_equal' => 'The due date cannot be in the past. Today is '.today()->toDateString().'.',
            'project_id.exists' => 'Project not found. Use list_projects to get the user\'s project ids.',
        ]);

        $task = $user->tasks()->whereKey($validated['task_id'])->first();

        if ($task === null) {
            return Response::error('Task not found. Use list_tasks to get the user\'s task ids.');
        }

        $task->update(collect($validated)->except('task_id')->all());

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
            'title' => $schema->string()
                ->description('New title.'),
            'description' => $schema->string()
                ->nullable()
                ->description('New notes, or null to clear them.'),
            'due_date' => $schema->string()
                ->format('date')
                ->description('New due date as YYYY-MM-DD, today or later.'),
            'project_id' => $schema->integer()
                ->nullable()
                ->description('Project to move the task to, or null to remove it from its project.'),
        ];
    }
}
