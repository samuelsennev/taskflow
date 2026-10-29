<?php

namespace App\Services\Tasks;

use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Writes tasks as a plain-text checklist that {@see TaskTextParser} reads back.
 *
 * Each task is a `- [ ] Title (DD/MM/YYYY)` line, completed ones are checked with `[x]`, the
 * description follows indented by four spaces and, when grouped, each project gets a `#` heading.
 */
class TaskTextExporter
{
    /**
     * Render the given tasks, optionally grouped under a heading per project.
     *
     * @param  Collection<int, Task>  $tasks
     */
    public function export(Collection $tasks, bool $groupByProject = false): string
    {
        if (! $groupByProject) {
            return $this->renderTasks($tasks);
        }

        $sections = $tasks
            ->groupBy(fn (Task $task): string => $task->project_id === null ? '' : $task->project->name)
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn (Collection $projectTasks, string $projectName): string => $projectName === ''
                ? $this->renderTasks($projectTasks)
                : "# {$projectName}\n\n".$this->renderTasks($projectTasks));

        return $sections->implode("\n");
    }

    /**
     * @param  Collection<int, Task>  $tasks
     */
    private function renderTasks(Collection $tasks): string
    {
        return $tasks->map(fn (Task $task): string => $this->renderTask($task))->implode('');
    }

    private function renderTask(Task $task): string
    {
        $checkbox = $task->isCompleted() ? '[x]' : '[ ]';
        $line = "- {$checkbox} {$task->title} ({$task->due_date->format('d/m/Y')})\n";

        if ($task->description === null || trim($task->description) === '') {
            return $line;
        }

        $description = Str::of(trim($task->description))
            ->split('/\R/u')
            ->map(fn (string $descriptionLine): string => rtrim($descriptionLine) === '' ? '' : '    '.rtrim($descriptionLine))
            ->implode("\n");

        return $line.$description."\n";
    }
}
