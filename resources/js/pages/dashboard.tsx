import { useState } from 'react';
import { PlusIcon } from '@/components/icons';
import CreateTaskDialog from '@/components/tasks/create-task-dialog';
import EmptyTasksState from '@/components/tasks/empty-tasks-state';
import TaskList from '@/components/tasks/task-list';
import AppLayout from '@/layouts/app-layout';
import type { Task } from '@/types';

type DashboardProps = {
    /** Today's date in the app timezone (YYYY-MM-DD). */
    today: string;
    tasks: Task[];
};

export default function Dashboard({ today, tasks }: DashboardProps) {
    const [dialogDueDate, setDialogDueDate] = useState<string | null>(null);

    const completedCount = tasks.filter((task) => task.is_completed).length;
    const formattedToday = new Intl.DateTimeFormat('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        timeZone: 'UTC',
    }).format(new Date(`${today}T00:00:00Z`));

    return (
        <AppLayout title="Hoje">
            {tasks.length === 0 ? (
                <EmptyTasksState
                    onCreateForToday={() => setDialogDueDate(today)}
                    onScheduleForAnotherDay={() => setDialogDueDate('')}
                />
            ) : (
                <>
                    <div className="flex items-end justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight text-neutral-900 dark:text-white">
                                Hoje
                            </h1>
                            <p className="mt-1 text-sm text-neutral-500 first-letter:uppercase dark:text-neutral-400">
                                {formattedToday} · {completedCount} de{' '}
                                {tasks.length} concluídas
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setDialogDueDate(today)}
                            className="flex h-9 shrink-0 items-center gap-1.5 rounded-lg bg-neutral-900 px-3 text-sm font-medium text-white shadow-sm transition-[background-color,scale] duration-150 hover:bg-neutral-800 active:scale-[0.98] dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200"
                        >
                            <PlusIcon />
                            Nova task
                        </button>
                    </div>
                    <div className="mt-6">
                        <TaskList tasks={tasks} />
                    </div>
                </>
            )}

            <CreateTaskDialog
                isOpen={dialogDueDate !== null}
                onClose={() => setDialogDueDate(null)}
                defaultDueDate={dialogDueDate ?? today}
                minimumDueDate={today}
            />
        </AppLayout>
    );
}
