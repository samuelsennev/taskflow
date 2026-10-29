<?php

use App\Models\Task;
use App\Models\User;

test('users can complete their own task', function () {
    $this->freezeTime();
    $task = Task::factory()->create();

    $this->actingAs($task->user)
        ->post(route('tasks.completion.store', $task))
        ->assertRedirect();

    expect($task->fresh()->completed_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('users can mark a completed task as pending again', function () {
    $task = Task::factory()->completed()->create();

    $this->actingAs($task->user)
        ->delete(route('tasks.completion.destroy', $task))
        ->assertRedirect();

    expect($task->fresh()->completed_at)->toBeNull();
});

test('users cannot complete another user\'s task', function () {
    $task = Task::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('tasks.completion.store', $task))
        ->assertForbidden();

    expect($task->fresh()->completed_at)->toBeNull();
});
