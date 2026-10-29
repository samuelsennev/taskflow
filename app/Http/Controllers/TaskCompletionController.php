<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class TaskCompletionController extends Controller
{
    /**
     * Mark the given task as completed.
     */
    public function store(Task $task): RedirectResponse
    {
        $task->update(['completed_at' => now()]);

        return back();
    }

    /**
     * Mark the given task as not completed.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $task->update(['completed_at' => null]);

        return back();
    }
}
