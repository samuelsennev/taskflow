<?php

use App\Models\Task;
use App\Models\User;

test('only the owner can update or delete a task', function (string $ability) {
    $task = Task::factory()->create();

    expect($task->user->can($ability, $task))->toBeTrue()
        ->and(User::factory()->create()->can($ability, $task))->toBeFalse();
})->with(['update', 'delete']);
