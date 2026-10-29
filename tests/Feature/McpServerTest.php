<?php

use App\Mcp\Servers\TaskflowServer;
use App\Mcp\Tools\CompleteTaskTool;
use App\Mcp\Tools\CreateProjectTool;
use App\Mcp\Tools\CreateTaskTool;
use App\Mcp\Tools\DeleteTaskTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ListTasksTool;
use App\Mcp\Tools\UpdateTaskTool;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

test('the mcp endpoint requires an oauth token', function () {
    $this->postJson(route('mcp'), ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate');
});

test('oauth clients can only register redirects to trusted domains', function (string $redirectUri, bool $isAllowed) {
    $response = $this->postJson(route('mcp.oauth.register'), [
        'client_name' => 'Claude',
        'redirect_uris' => [$redirectUri],
    ]);

    $isAllowed ? $response->assertSuccessful() : $response->assertStatus(400);
})->with([
    'claude.ai' => ['https://claude.ai/api/mcp/auth_callback', true],
    'chatgpt.com' => ['https://chatgpt.com/connector_platform_oauth_redirect', true],
    'localhost' => ['http://localhost:6274/oauth/callback', true],
    'another site' => ['https://evil.example/callback', false],
    'a look-alike host' => ['https://claude.ai.evil.example/callback', false],
]);

test('listing tasks filters by scope and only shows the user\'s tasks', function (string $scope, array $expectedTitles, array $hiddenTitles) {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['title' => 'Due today']);
    Task::factory()->for($user)->dueInDays(-2)->create(['title' => 'Late one']);
    Task::factory()->for($user)->dueInDays(3)->create(['title' => 'Next week']);
    Task::factory()->for($user)->completed()->create(['title' => 'Already done']);
    Task::factory()->create(['title' => 'Someone else']);

    TaskflowServer::actingAs($user)->tool(ListTasksTool::class, ['scope' => $scope])
        ->assertOk()
        ->assertSee($expectedTitles)
        ->assertDontSee([...$hiddenTitles, 'Someone else']);
})->with([
    'pending' => ['pending', ['Due today', 'Late one', 'Next week'], ['Already done']],
    'today' => ['today', ['Due today', 'Already done'], ['Late one', 'Next week']],
    'overdue' => ['overdue', ['Late one'], ['Due today', 'Next week', 'Already done']],
    'upcoming' => ['upcoming', ['Next week'], ['Due today', 'Late one', 'Already done']],
    'completed' => ['completed', ['Already done'], ['Due today', 'Late one', 'Next week']],
]);

test('listing tasks can be narrowed to a project and a search term', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    Task::factory()->forProject($project)->create(['title' => 'Buy milk']);
    Task::factory()->forProject($project)->create(['title' => 'Call bank']);
    Task::factory()->for($user)->create(['title' => 'Buy bread']);

    TaskflowServer::actingAs($user)->tool(ListTasksTool::class, ['project_id' => $project->id, 'search' => 'buy'])
        ->assertOk()
        ->assertSee('Buy milk')
        ->assertDontSee(['Call bank', 'Buy bread']);
});

test('listing projects only shows the user\'s projects', function () {
    $user = User::factory()->create();
    Project::factory()->for($user)->create(['name' => 'Home']);
    Project::factory()->create(['name' => 'Not mine']);

    TaskflowServer::actingAs($user)->tool(ListProjectsTool::class)
        ->assertOk()
        ->assertSee('Home')
        ->assertDontSee('Not mine');
});

test('creating a task stores it for the user', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    TaskflowServer::actingAs($user)->tool(CreateTaskTool::class, [
        'title' => 'Pay rent',
        'due_date' => today()->addDay()->toDateString(),
        'project_id' => $project->id,
    ])->assertOk()->assertSee('Pay rent');

    $task = $user->tasks()->sole();

    expect($task->title)->toBe('Pay rent')
        ->and($task->due_date->toDateString())->toBe(today()->addDay()->toDateString())
        ->and($task->project_id)->toBe($project->id);
});

test('creating a task rejects invalid input', function (array $arguments) {
    $user = User::factory()->create();

    TaskflowServer::actingAs($user)->tool(CreateTaskTool::class, [
        'title' => 'Pay rent',
        'due_date' => today()->toDateString(),
        ...$arguments,
    ])->assertHasErrors();

    expect(Task::count())->toBe(0);
})->with([
    'a due date in the past' => [['due_date' => today()->subDay()->toDateString()]],
    'a missing title' => [['title' => '']],
    'another user\'s project' => [fn () => ['project_id' => Project::factory()->create()->id]],
]);

test('updating a task changes only the given fields', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['title' => 'Old title', 'description' => 'Keep me']);

    TaskflowServer::actingAs($user)->tool(UpdateTaskTool::class, [
        'task_id' => $task->id,
        'title' => 'New title',
        'due_date' => today()->addDays(5)->toDateString(),
    ])->assertOk();

    $task->refresh();

    expect($task->title)->toBe('New title')
        ->and($task->description)->toBe('Keep me')
        ->and($task->due_date->toDateString())->toBe(today()->addDays(5)->toDateString());
});

test('completing a task marks it as done and can reopen it', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    TaskflowServer::actingAs($user)->tool(CompleteTaskTool::class, ['task_id' => $task->id])->assertOk();

    expect($task->fresh()->isCompleted())->toBeTrue();

    TaskflowServer::actingAs($user)->tool(CompleteTaskTool::class, ['task_id' => $task->id, 'completed' => false])->assertOk();

    expect($task->fresh()->isCompleted())->toBeFalse();
});

test('deleting a task removes it', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    TaskflowServer::actingAs($user)->tool(DeleteTaskTool::class, ['task_id' => $task->id])->assertOk();

    expect(Task::find($task->id))->toBeNull();
});

test('users cannot change another user\'s task', function (string $tool, array $arguments) {
    $user = User::factory()->create();
    $task = Task::factory()->create(['title' => 'Not mine']);

    TaskflowServer::actingAs($user)->tool($tool, ['task_id' => $task->id, ...$arguments])
        ->assertHasErrors(['Task not found.']);

    expect($task->fresh())->not->toBeNull()
        ->title->toBe('Not mine')
        ->completed_at->toBeNull();
})->with([
    'update' => [UpdateTaskTool::class, ['title' => 'Hijacked']],
    'complete' => [CompleteTaskTool::class, []],
    'delete' => [DeleteTaskTool::class, []],
]);

test('creating a project stores it for the user', function () {
    $user = User::factory()->create();

    TaskflowServer::actingAs($user)->tool(CreateProjectTool::class, ['name' => 'Garden'])->assertOk();

    expect($user->projects()->sole()->name)->toBe('Garden');
});
