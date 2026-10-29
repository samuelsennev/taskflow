<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportTasksRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TaskImportController extends Controller
{
    /**
     * Create tasks from an uploaded text file, filed under the given project or, outside a project,
     * under the user's project named by each `#` heading.
     */
    public function store(ImportTasksRequest $request, ?Project $project = null): RedirectResponse
    {
        $user = $request->user();

        $projectIdsByName = $project === null
            ? $user->projects()->pluck('id', 'name')->mapWithKeys(fn (int $id, string $name): array => [mb_strtolower($name) => $id])
            : collect();

        $tasks = collect($request->tasks())->map(fn (array $task): array => [
            'project_id' => $project->id ?? $projectIdsByName->get(mb_strtolower($task['heading'] ?? '')),
            'title' => $task['title'],
            'description' => $task['description'],
            'due_date' => $task['due_date'],
            'completed_at' => $task['is_completed'] ? now() : null,
        ]);

        DB::transaction(fn () => $user->tasks()->createMany($tasks));

        Inertia::flash('imported_tasks_count', $tasks->count());

        return back();
    }
}
