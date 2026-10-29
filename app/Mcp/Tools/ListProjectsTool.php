<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_projects')]
#[Description('List the user\'s projects with how many of their tasks are pending and completed.')]
#[IsReadOnly]
class ListProjectsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $projects = $request->user()
            ->projects()
            ->withCount([
                'tasks as pending_tasks_count' => fn ($query) => $query->whereNull('completed_at'),
                'tasks as completed_tasks_count' => fn ($query) => $query->whereNotNull('completed_at'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'pending_tasks_count' => $project->pending_tasks_count,
                'completed_tasks_count' => $project->completed_tasks_count,
            ]);

        return Response::json(['projects' => $projects->all()]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
