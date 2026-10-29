<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('guests cannot export tasks', function () {
    $this->get(route('tasks.export.show'))->assertRedirect(route('login'));
});

test('users can export all their tasks grouped by project as a text file', function () {
    $this->travelTo('2026-10-29');
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'API']);
    Task::factory()->for($user)->create(['title' => 'Avulsa', 'description' => null, 'due_date' => '2026-10-30']);
    Task::factory()->forProject($project)->completed()->create([
        'title' => 'Endpoint',
        'description' => "Linha 1\n\nLinha 2",
        'due_date' => '2026-10-31',
    ]);
    Task::factory()->create(['title' => 'De outro usuário']);

    $response = $this->actingAs($user)->get(route('tasks.export.show'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertDownload('tasks-2026-10-29.txt');

    expect($response->streamedContent())->toBe(<<<'TXT'
        - [ ] Avulsa (30/10/2026)

        # API

        - [x] Endpoint (31/10/2026)
            Linha 1

            Linha 2

        TXT);
});

test('users can export the tasks of their project', function () {
    $this->travelTo('2026-10-29');
    $project = Project::factory()->create(['name' => 'Novo Site']);
    Task::factory()->forProject($project)->create(['title' => 'Do projeto', 'description' => null, 'due_date' => '2026-10-29']);
    Task::factory()->for($project->user)->create(['title' => 'Fora do projeto']);

    $response = $this->actingAs($project->user)->get(route('projects.tasks.export.show', $project));

    $response->assertOk()->assertDownload('tasks-novo-site-2026-10-29.txt');

    expect($response->streamedContent())->toBe("- [ ] Do projeto (29/10/2026)\n");
});

test('users cannot export another user\'s project', function () {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('projects.tasks.export.show', $project))
        ->assertForbidden();
});

test('an exported file imports back into the same tasks', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'API']);
    Task::factory()->for($user)->create(['title' => 'Relatório 2026-01-01', 'description' => null, 'due_date' => today()->addDay()]);
    Task::factory()->forProject($project)->completed()->create(['title' => 'Endpoint', 'description' => "Linha 1\n\nLinha 2"]);

    $exported = $this->actingAs($user)->get(route('tasks.export.show'))->streamedContent();
    $original = $user->tasks()->orderBy('id')->get();
    $user->tasks()->delete();

    $this->post(route('tasks.import.store'), ['file' => UploadedFile::fake()->createWithContent('tasks.txt', $exported)])
        ->assertSessionHasNoErrors();

    $imported = $user->tasks()->orderBy('id')->get();
    $comparable = fn (Task $task): array => [
        $task->project_id,
        $task->title,
        $task->description,
        $task->due_date->toDateString(),
        $task->isCompleted(),
    ];

    expect($imported->map($comparable)->all())->toBe($original->map($comparable)->all());
});
