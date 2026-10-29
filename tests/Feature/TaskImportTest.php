<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function taskFile(string $contents, string $name = 'tasks.txt'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $contents);
}

test('guests cannot import tasks', function () {
    $this->post(route('tasks.import.store'), ['file' => taskFile('- Ship it')])
        ->assertRedirect(route('login'));

    expect(Task::count())->toBe(0);
});

test('users can import a checklist with dates, completion and descriptions', function () {
    $this->freezeTime();
    $user = User::factory()->create();

    $contents = <<<'TXT'
        - [ ] Revisar o PR (30/10/2026)
            Checar os testes

            E a cobertura
        - [x] Publicar release 2026-10-28
        Comprar café
        TXT;

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('tasks.import.store'), ['file' => taskFile($contents)])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('imported_tasks_count', 3);

    $tasks = $user->tasks()->orderBy('id')->get();

    expect($tasks)->toHaveCount(3)
        ->and($tasks[0]->title)->toBe('Revisar o PR')
        ->and($tasks[0]->description)->toBe("Checar os testes\n\nE a cobertura")
        ->and($tasks[0]->due_date->toDateString())->toBe('2026-10-30')
        ->and($tasks[0]->completed_at)->toBeNull()
        ->and($tasks[1]->title)->toBe('Publicar release')
        ->and($tasks[1]->due_date->toDateString())->toBe('2026-10-28')
        ->and($tasks[1]->completed_at)->not->toBeNull()
        ->and($tasks[2]->title)->toBe('Comprar café')
        ->and($tasks[2]->due_date->toDateString())->toBe(today()->toDateString())
        ->and($tasks->pluck('project_id')->filter())->toBeEmpty();
});

test('list markers and separators are stripped from imported titles', function (string $line, string $title) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tasks.import.store'), ['file' => taskFile($line, 'tasks.md')])
        ->assertSessionHasNoErrors();

    expect($user->tasks()->sole()->title)->toBe($title);
})->with([
    'markdown bullet' => ['* Escrever docs', 'Escrever docs'],
    'numbered list' => ['2) Escrever docs', 'Escrever docs'],
    'csv line' => ['Escrever docs,2026-11-02', 'Escrever docs'],
    'pipe separator' => ['Escrever docs | 02/11/2026', 'Escrever docs'],
]);

test('importing outside a project files tasks under the user\'s project named by each heading', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'API']);
    Project::factory()->create(['name' => 'Site']);

    $contents = <<<'TXT'
        - Sem projeto

        # api
        - Da API

        # Site
        - Projeto de outro usuário
        TXT;

    $this->actingAs($user)
        ->post(route('tasks.import.store'), ['file' => taskFile($contents)])
        ->assertSessionHasNoErrors();

    expect($user->tasks()->where('title', 'Da API')->sole()->project_id)->toBe($project->id)
        ->and($user->tasks()->where('title', 'Sem projeto')->sole()->project_id)->toBeNull()
        ->and($user->tasks()->where('title', 'Projeto de outro usuário')->sole()->project_id)->toBeNull();
});

test('importing into a project files every task under it', function () {
    $project = Project::factory()->create();

    $this->actingAs($project->user)
        ->post(route('projects.tasks.import.store', $project), ['file' => taskFile("# Outro\n- Primeira\n- Segunda")])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('imported_tasks_count', 2);

    expect($project->tasks()->count())->toBe(2);
});

test('users cannot import tasks into another user\'s project', function () {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('projects.tasks.import.store', $project), ['file' => taskFile('- Intrusa')])
        ->assertForbidden();

    expect(Task::count())->toBe(0);
});

test('imported descriptions are cut at 2000 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tasks.import.store'), ['file' => taskFile("- Longa\n".str_repeat('    '.str_repeat('a', 99)."\n", 50))])
        ->assertSessionHasNoErrors();

    $description = $user->tasks()->sole()->description;

    expect(mb_strlen($description))->toBe(2000)
        ->and($description)->toStartWith(str_repeat('a', 99)."\n".str_repeat('a', 99));
});

test('legacy windows-1252 files are imported as utf-8', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tasks.import.store'), ['file' => taskFile(mb_convert_encoding('Revisão', 'Windows-1252', 'UTF-8'))])
        ->assertSessionHasNoErrors();

    expect($user->tasks()->sole()->title)->toBe('Revisão');
});

test('importing requires a file', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('tasks.import.store'), [])
        ->assertSessionHasErrors(['file' => 'Escolha um arquivo para importar.']);
});

test('importing rejects files that are not text', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('tasks.import.store'), ['file' => taskFile("\x89PNG\r\n\x1a\n\0\0\0", 'image.png')])
        ->assertSessionHasErrors(['file' => 'O arquivo precisa ser de texto.']);

    expect(Task::count())->toBe(0);
});

test('importing rejects files without tasks', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('tasks.import.store'), ['file' => taskFile("\n   \n# Só um título\n")])
        ->assertSessionHasErrors(['file' => 'Nenhuma task encontrada no arquivo.']);
});

test('importing rejects files with more than 500 tasks', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('tasks.import.store'), ['file' => taskFile(str_repeat("- Task\n", 501))])
        ->assertSessionHasErrors(['file' => 'O arquivo não pode ter mais de 500 tasks.']);

    expect(Task::count())->toBe(0);
});

test('importing rejects files larger than 1 MB', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('tasks.import.store'), ['file' => UploadedFile::fake()->create('tasks.txt', 1025, 'text/plain')])
        ->assertSessionHasErrors(['file' => 'O arquivo não pode ter mais de 1 MB.']);
});
