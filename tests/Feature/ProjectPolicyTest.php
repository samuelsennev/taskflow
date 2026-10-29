<?php

use App\Models\Project;
use App\Models\User;

test('only the owner can view, update or delete a project', function (string $ability) {
    $project = Project::factory()->create();

    expect($project->user->can($ability, $project))->toBeTrue()
        ->and(User::factory()->create()->can($ability, $project))->toBeFalse();
})->with(['view', 'update', 'delete']);
