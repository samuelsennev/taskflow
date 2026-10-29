<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access projects', function () {
    $this->get(route('projects.index'))->assertRedirect(route('login'));

    $this->post(route('projects.store'), ['name' => 'Taskflow'])->assertRedirect(route('login'));

    expect(Project::count())->toBe(0);
});

test('users only see their own projects with task progress', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'API']);
    Task::factory()->forProject($project)->completed()->create();
    Task::factory()->forProject($project)->create();
    Project::factory()->create();

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/index')
            ->has('projects', 1)
            ->where('projects.0.name', 'API')
            ->where('projects.0.tasks_count', 2)
            ->where('projects.0.completed_tasks_count', 1)
        );
});

test('users can create a project', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('projects.store'), [
            'name' => 'Taskflow',
            'description' => 'App de produtividade',
        ]);

    $project = $user->projects()->sole();

    $response->assertRedirect(route('projects.show', $project))->assertSessionHasNoErrors();

    expect($project->name)->toBe('Taskflow')
        ->and($project->description)->toBe('App de produtividade');
});

test('a project requires a name of at most 255 characters', function (array $data, string $error) {
    $this->actingAs(User::factory()->create())
        ->post(route('projects.store'), $data)
        ->assertSessionHasErrors(['name' => $error]);

    expect(Project::count())->toBe(0);
})->with([
    'missing' => [[], 'O campo nome é obrigatório.'],
    'too long' => [['name' => str_repeat('a', 256)], 'O campo nome não pode ter mais de 255 caracteres.'],
]);

test('users can view their project with its tasks', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->forProject($project)->dueInDays(3)->create();
    Task::factory()->for($project->user)->create();

    $this->actingAs($project->user)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/show')
            ->where('project.id', $project->id)
            ->has('tasks', 1)
            ->where('tasks.0.id', $task->id)
            ->where('tasks.0.due_date', $task->due_date->toDateString())
        );
});

test('users can view their project settings', function () {
    $project = Project::factory()->create(['name' => 'API']);

    $this->actingAs($project->user)
        ->get(route('projects.edit', $project))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/edit')
            ->where('project.id', $project->id)
            ->where('project.name', 'API')
        );
});

test('users can update their project', function () {
    $project = Project::factory()->create();

    $this->actingAs($project->user)
        ->put(route('projects.update', $project), ['name' => 'Novo nome', 'description' => null])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($project->refresh()->name)->toBe('Novo nome')
        ->and($project->description)->toBeNull();
});

test('deleting a project deletes its tasks', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->forProject($project)->create();

    $this->actingAs($project->user)
        ->delete(route('projects.destroy', $project))
        ->assertRedirect(route('projects.index'));

    $this->assertModelMissing($project);
    $this->assertModelMissing($task);
});

test('users cannot view, update or delete another user\'s project', function () {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->create());

    $this->get(route('projects.show', $project))->assertForbidden();
    $this->get(route('projects.edit', $project))->assertForbidden();
    $this->put(route('projects.update', $project), ['name' => 'Invadido'])->assertForbidden();
    $this->delete(route('projects.destroy', $project))->assertForbidden();

    $this->assertModelExists($project);
    expect($project->refresh()->name)->not->toBe('Invadido');
});

test('users can create a task inside their project', function () {
    $project = Project::factory()->create();

    $this->actingAs($project->user)
        ->post(route('tasks.store'), [
            'title' => 'Modelar o banco',
            'due_date' => today()->toDateString(),
            'project_id' => $project->id,
        ])
        ->assertSessionHasNoErrors();

    expect($project->tasks()->sole()->user_id)->toBe($project->user_id);
});

test('users cannot create a task inside another user\'s project', function () {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('tasks.store'), [
            'title' => 'Intrusa',
            'due_date' => today()->toDateString(),
            'project_id' => $project->id,
        ])
        ->assertSessionHasErrors(['project_id' => 'O valor selecionado para projeto é inválido.']);

    expect(Task::count())->toBe(0);
});
