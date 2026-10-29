<?php

namespace App\Mcp\Tools\Concerns;

use App\Models\Project;
use App\Models\Task;

trait PresentsTasks
{
    /**
     * @return array{id: int, title: string, description: string|null, due_date: string, is_overdue: bool, is_completed: bool, completed_at: string|null, project: array{id: int, name: string}|null}
     */
    protected function presentTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'due_date' => $task->due_date->toDateString(),
            'is_overdue' => ! $task->isCompleted() && $task->due_date->lt(today()),
            'is_completed' => $task->isCompleted(),
            'completed_at' => $task->completed_at?->toIso8601String(),
            'project' => $task->project === null ? null : [
                'id' => $task->project->id,
                'name' => $task->project->name,
            ],
        ];
    }

    /**
     * @return array{id: int, name: string, description: string|null}
     */
    protected function presentProject(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
        ];
    }
}
