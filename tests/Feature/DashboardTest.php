<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('the dashboard lists only the tasks the user has due today', function () {
    $this->freezeTime();
    $user = User::factory()->create();

    $dueToday = Task::factory()->for($user)->create(['title' => 'Due today']);
    Task::factory()->for($user)->dueInDays(-1)->create(['title' => 'Due yesterday']);
    Task::factory()->for($user)->dueInDays(1)->create(['title' => 'Due tomorrow']);
    Task::factory()->create(['title' => 'Someone else due today']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('today', today()->toDateString())
            ->has('tasks', 1)
            ->where('tasks.0', [
                'id' => $dueToday->id,
                'title' => 'Due today',
                'description' => $dueToday->description,
                'is_completed' => false,
                'project' => null,
            ]));
});

test('dashboard tasks include the project they belong to', function () {
    $project = Project::factory()->create(['name' => 'Ponto X']);
    Task::factory()->forProject($project)->create();

    $this->actingAs($project->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tasks.0.project', ['id' => $project->id, 'name' => 'Ponto X']));
});

test('completed tasks keep their position in the list', function () {
    $user = User::factory()->create();

    $completed = Task::factory()->for($user)->completed()->create(['created_at' => now()->subMinute()]);
    $pending = Task::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tasks.0.id', $completed->id)
            ->where('tasks.0.is_completed', true)
            ->where('tasks.1.id', $pending->id)
            ->where('tasks.1.is_completed', false));
});

test('the dashboard has no tasks when nothing is due today', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('tasks', 0));
});
