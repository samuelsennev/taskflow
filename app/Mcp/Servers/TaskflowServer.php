<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CompleteTaskTool;
use App\Mcp\Tools\CreateProjectTool;
use App\Mcp\Tools\CreateTaskTool;
use App\Mcp\Tools\DeleteTaskTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ListTasksTool;
use App\Mcp\Tools\UpdateTaskTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Taskflow')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
    Taskflow is the user's personal task manager. Every task has a due date and may belong to a project.
    Look tasks up with list_tasks before changing them, and refer to them by title (not id) when talking to the user.
    Dates are YYYY-MM-DD in the user's timezone; list_tasks returns today's date so you can resolve words like "tomorrow" or "next Friday".
    TEXT)]
class TaskflowServer extends Server
{
    protected array $tools = [
        ListTasksTool::class,
        ListProjectsTool::class,
        CreateTaskTool::class,
        UpdateTaskTool::class,
        CompleteTaskTool::class,
        DeleteTaskTool::class,
        CreateProjectTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
