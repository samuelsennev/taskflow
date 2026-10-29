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

#[Name('create_project')]
#[Description('Create a project to group the user\'s tasks.')]
class CreateProjectTool extends Tool
{
    use PresentsTasks;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $project = $request->user()->projects()->create($validated);

        return Response::json(['project' => $this->presentProject($project)]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Name of the project.')
                ->required(),
            'description' => $schema->string()
                ->description('Optional description of the project.'),
        ];
    }
}
