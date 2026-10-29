<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the tasks the user has to deliver today.
     */
    public function __invoke(Request $request): Response
    {
        $tasks = $request->user()
            ->tasks()
            ->dueToday()
            ->with('project:id,name')
            ->oldest()
            ->get()
            ->map(fn (Task $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'is_completed' => $task->isCompleted(),
                'project' => $task->project?->only(['id', 'name']),
            ]);

        return Inertia::render('dashboard', [
            'today' => today()->toDateString(),
            'tasks' => $tasks,
        ]);
    }
}
