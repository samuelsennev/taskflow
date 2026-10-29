<?php

use App\Models\Task;
use App\Models\User;

test('guests cannot create tasks', function () {
    $this->post(route('tasks.store'), ['title' => 'Ship it', 'due_date' => today()->toDateString()])
        ->assertRedirect(route('login'));

    expect(Task::count())->toBe(0);
});

test('users can create a task', function () {
    $this->freezeTime();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('tasks.store'), [
            'title' => 'Revisar o PR',
            'description' => 'Checar os testes',
            'due_date' => today()->addDay()->toDateString(),
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();

    $task = $user->tasks()->sole();

    expect($task->title)->toBe('Revisar o PR')
        ->and($task->description)->toBe('Checar os testes')
        ->and($task->due_date->toDateString())->toBe(today()->addDay()->toDateString())
        ->and($task->completed_at)->toBeNull();
});

test('a task requires a title and a due date', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('tasks.store'), [])
        ->assertSessionHasErrors([
            'title' => 'O campo título é obrigatório.',
            'due_date' => 'O campo data de entrega é obrigatório.',
        ]);

    expect(Task::count())->toBe(0);
});

test('a task cannot be due in the past', function () {
    $this->freezeTime();

    $this->actingAs(User::factory()->create())
        ->post(route('tasks.store'), [
            'title' => 'Ontem',
            'due_date' => today()->subDay()->toDateString(),
        ])
        ->assertSessionHasErrors(['due_date' => 'A data de entrega não pode estar no passado.']);

    expect(Task::count())->toBe(0);
});

test('a task title cannot be longer than 255 characters', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('tasks.store'), [
            'title' => str_repeat('a', 256),
            'due_date' => today()->toDateString(),
        ])
        ->assertSessionHasErrors(['title' => 'O campo título não pode ter mais de 255 caracteres.']);
});

test('a task description cannot be longer than 2000 characters', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('tasks.store'), [
            'title' => 'Ship it',
            'description' => str_repeat('a', 2001),
            'due_date' => today()->toDateString(),
        ])
        ->assertSessionHasErrors(['description' => 'O campo descrição não pode ter mais de 2000 caracteres.']);
});

test('users can delete their own task', function () {
    $task = Task::factory()->create();

    $this->actingAs($task->user)
        ->delete(route('tasks.destroy', $task))
        ->assertRedirect();

    $this->assertModelMissing($task);
});

test('users cannot delete another user\'s task', function () {
    $task = Task::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('tasks.destroy', $task))
        ->assertForbidden();

    $this->assertModelExists($task);
});

test('users can edit the title and description of their task', function () {
    $task = Task::factory()->create(['title' => 'Antigo', 'description' => 'Velha']);

    $this->actingAs($task->user)
        ->put(route('tasks.update', $task), ['title' => 'Novo título', 'description' => null])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($task->refresh()->title)->toBe('Novo título')
        ->and($task->description)->toBeNull();
});

test('editing a task requires a title', function () {
    $task = Task::factory()->create(['title' => 'Antigo']);

    $this->actingAs($task->user)
        ->put(route('tasks.update', $task), ['title' => ''])
        ->assertSessionHasErrors('title');

    expect($task->refresh()->title)->toBe('Antigo');
});

test('users cannot edit another user\'s task', function () {
    $task = Task::factory()->create(['title' => 'Antigo']);

    $this->actingAs(User::factory()->create())
        ->put(route('tasks.update', $task), ['title' => 'Invadido'])
        ->assertForbidden();

    expect($task->refresh()->title)->toBe('Antigo');
});
