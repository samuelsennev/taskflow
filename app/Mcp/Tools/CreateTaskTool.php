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

#[Name('create_task')]
#[Description('Create a task for the user. Every task needs a due date; if the user did not give one, ask before creating it.')]
class CreateTaskTool extends Tool
{
    use PresentsTasks;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->where('user_id', $user->getAuthIdentifier())],
        ], [
            'due_date.after_or_equal' => 'The due date cannot be in the past. Today is '.today()->toDateString().'.',
            'project_id.exists' => 'Project not found. Use list_projects to get the user\'s project ids.',
        ]);

        $task = $user->tasks()->create($validated);

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
            'title' => $schema->string()
                ->description('Short title of the task.')
                ->required(),
            'due_date' => $schema->string()
                ->format('date')
                ->description('Due date as YYYY-MM-DD, today or later.')
                ->required(),
            'description' => $schema->string()
                ->description('Optional longer notes about the task.'),
            'project_id' => $schema->integer()
                ->description('Optional project to file the task under. Get project ids from list_projects.'),
        ];
    }
}
