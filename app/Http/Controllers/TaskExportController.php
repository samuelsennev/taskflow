<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Tasks\TaskTextExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskExportController extends Controller
{
    /**
     * Download the tasks of the given project, or all of the user's tasks grouped by project, as a .txt file.
     */
    public function show(Request $request, TaskTextExporter $exporter, ?Project $project = null): StreamedResponse
    {
        $tasks = ($project?->tasks() ?? $request->user()->tasks()->with('project:id,name'))
            ->orderBy('due_date')
            ->oldest()
            ->get();

        $contents = $exporter->export($tasks, groupByProject: $project === null);

        $filename = $project === null
            ? 'tasks-'.today()->toDateString().'.txt'
            : 'tasks-'.(Str::slug($project->name) ?: 'projeto').'-'.today()->toDateString().'.txt';

        return response()->streamDownload(
            function () use ($contents): void {
                echo $contents;
            },
            $filename,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }
}
