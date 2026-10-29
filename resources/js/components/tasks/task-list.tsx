import { router } from '@inertiajs/react';
import { useState } from 'react';
import { CalendarIcon, CheckIcon, TrashIcon } from '@/components/icons';
import TaskDetailsDialog from '@/components/tasks/task-details-dialog';
import { cn } from '@/lib/utils';
import { destroy } from '@/routes/tasks';
import completion from '@/routes/tasks/completion';
import type { Task } from '@/types';

type TaskListProps = {
    tasks: Task[];
};

const dueDateFormatter = new Intl.DateTimeFormat('pt-BR', {
    day: 'numeric',
    month: 'short',
    timeZone: 'UTC',
});

/**
 * Toggle the completion of the given task, updating the page's tasks optimistically.
 */
function toggleCompletion(task: Task) {
    const route = task.is_completed
        ? completion.destroy(task.id)
        : completion.store(task.id);

    router.visit(route, {
        preserveScroll: true,
        optimistic: (props) => ({
            tasks: (props.tasks as Task[]).map((current) =>
                current.id === task.id
                    ? { ...current, is_completed: !task.is_completed }
                    : current,
            ),
        }),
    });
}

/**
 * Tasks as rows in a single card, each with a completion toggle and a delete action.
 * Clicking a row opens a modal with the task's details.
 */
export default function TaskList({ tasks }: TaskListProps) {
    const [selectedTaskId, setSelectedTaskId] = useState<number | null>(null);
    const [isDetailsOpen, setIsDetailsOpen] = useState(false);

    const selectedTask = tasks.find((task) => task.id === selectedTaskId);

    const openDetails = (task: Task) => {
        setSelectedTaskId(task.id);
        setIsDetailsOpen(true);
    };

    return (
        <>
            <ul className="divide-y divide-black/6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 shadow-black/5 ring-black/6 dark:divide-white/8 dark:bg-neutral-900 dark:shadow-black/40 dark:ring-white/10">
                {tasks.map((task) => (
                    <TaskRow
                        key={task.id}
                        task={task}
                        onOpen={() => openDetails(task)}
                    />
                ))}
            </ul>

            <TaskDetailsDialog
                isOpen={isDetailsOpen}
                onClose={() => setIsDetailsOpen(false)}
                task={selectedTask}
                onToggleCompletion={() =>
                    selectedTask && toggleCompletion(selectedTask)
                }
            />
        </>
    );
}

function TaskRow({ task, onOpen }: { task: Task; onOpen: () => void }) {
    const deleteTask = () => {
        router.visit(destroy(task.id), {
            preserveScroll: true,
            optimistic: (props) => ({
                tasks: (props.tasks as Task[]).filter(
                    (current) => current.id !== task.id,
                ),
            }),
        });
    };

    return (
        <li className="group flex items-start gap-3 px-4 py-3.5 transition-colors hover:bg-neutral-50 sm:px-5 dark:hover:bg-neutral-800/50">
            <button
                type="button"
                role="checkbox"
                aria-checked={task.is_completed}
                aria-label={
                    task.is_completed
                        ? `Marcar "${task.title}" como pendente`
                        : `Concluir "${task.title}"`
                }
                onClick={() => toggleCompletion(task)}
                className={cn(
                    'mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full ring-1 transition-colors',
                    task.is_completed
                        ? 'bg-neutral-900 text-white ring-neutral-900 dark:bg-white dark:text-neutral-900 dark:ring-white'
                        : 'bg-white text-transparent ring-black/15 hover:ring-black/30 dark:bg-neutral-900 dark:ring-white/20',
                )}
            >
                <CheckIcon className="size-3" />
            </button>
            <button
                type="button"
                onClick={onOpen}
                aria-label={`Ver detalhes de "${task.title}"`}
                className="min-w-0 flex-1 text-left"
            >
                <span
                    className={cn(
                        'block text-sm font-medium break-words',
                        task.is_completed
                            ? 'text-neutral-400 line-through'
                            : 'text-neutral-900 dark:text-white',
                    )}
                >
                    {task.title}
                </span>
                {task.description && (
                    <span
                        className={cn(
                            'mt-0.5 line-clamp-2 block text-sm break-words whitespace-pre-line',
                            task.is_completed
                                ? 'text-neutral-300 dark:text-neutral-600'
                                : 'text-neutral-500 dark:text-neutral-400',
                        )}
                    >
                        {task.description}
                    </span>
                )}
                {(task.due_date || task.project) && (
                    <span className="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-neutral-400 dark:text-neutral-500">
                        {task.project && (
                            <span className="inline-flex items-center rounded-md bg-neutral-100 px-1.5 py-0.5 font-medium text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                                {task.project.name}
                            </span>
                        )}
                        {task.due_date && (
                            <span className="flex items-center gap-1">
                                <CalendarIcon className="size-3.5" />
                                {dueDateFormatter.format(
                                    new Date(`${task.due_date}T00:00:00Z`),
                                )}
                            </span>
                        )}
                    </span>
                )}
            </button>
            <button
                type="button"
                onClick={deleteTask}
                aria-label={`Excluir "${task.title}"`}
                className="flex size-7 shrink-0 items-center justify-center rounded-md text-neutral-400 opacity-100 transition-opacity hover:text-red-600 focus-visible:opacity-100 sm:opacity-0 sm:group-hover:opacity-100 dark:hover:text-red-400"
            >
                <TrashIcon />
            </button>
        </li>
    );
}
