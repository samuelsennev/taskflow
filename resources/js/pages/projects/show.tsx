import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { PlusIcon, SettingsIcon } from '@/components/icons';
import CreateTaskDialog from '@/components/tasks/create-task-dialog';
import TaskList from '@/components/tasks/task-list';
import AppLayout from '@/layouts/app-layout';
import { edit, index } from '@/routes/projects';
import type { Project, Task } from '@/types';

type ProjectShowProps = {
    /** Today's date in the app timezone (YYYY-MM-DD). */
    today: string;
    project: Project;
    tasks: Task[];
};

export default function ProjectShow({
    today,
    project,
    tasks,
}: ProjectShowProps) {
    const [isCreatingTask, setIsCreatingTask] = useState(false);

    const completedCount = tasks.filter((task) => task.is_completed).length;

    return (
        <AppLayout title={project.name}>
            <Link
                href={index()}
                className="text-sm text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white"
            >
                ← Projetos
            </Link>

            <div className="mt-3">
                <div className="flex items-center gap-2">
                    <h1 className="mr-auto min-w-0 pr-2 text-2xl font-semibold tracking-tight break-words text-neutral-900 dark:text-white">
                        {project.name}
                    </h1>
                    <Link
                        href={edit(project.id)}
                        aria-label="Configurações do projeto"
                        title="Configurações do projeto"
                        className="flex size-9 shrink-0 items-center justify-center rounded-lg text-neutral-500 ring-1 ring-black/10 transition-colors hover:bg-white hover:text-neutral-900 dark:text-neutral-400 dark:ring-white/10 dark:hover:bg-neutral-900 dark:hover:text-white"
                    >
                        <SettingsIcon />
                    </Link>
                    <button
                        type="button"
                        onClick={() => setIsCreatingTask(true)}
                        aria-label="Nova task"
                        title="Nova task"
                        className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-neutral-900 text-white shadow-sm transition-[background-color,scale] duration-150 hover:bg-neutral-800 active:scale-[0.98] dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200"
                    >
                        <PlusIcon />
                    </button>
                </div>
                {project.description && (
                    <p className="mt-1 text-sm break-words whitespace-pre-line text-neutral-500 dark:text-neutral-400">
                        {project.description}
                    </p>
                )}
                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    {completedCount} de {tasks.length} concluídas
                </p>
            </div>

            <div className="mt-6">
                {tasks.length === 0 ? (
                    <div className="flex flex-col items-center rounded-xl bg-white px-6 py-16 text-center shadow-sm ring-1 shadow-black/5 ring-black/6 dark:bg-neutral-900 dark:ring-white/10">
                        <p className="text-sm font-medium text-neutral-900 dark:text-white">
                            Nenhuma task neste projeto
                        </p>
                        <p className="mt-1 max-w-sm text-sm text-balance text-neutral-500 dark:text-neutral-400">
                            Adicione a primeira task para começar a acompanhar a
                            entrega.
                        </p>
                    </div>
                ) : (
                    <TaskList tasks={tasks} />
                )}
            </div>

            <CreateTaskDialog
                isOpen={isCreatingTask}
                onClose={() => setIsCreatingTask(false)}
                defaultDueDate={today}
                minimumDueDate={today}
                projectId={project.id}
            />
        </AppLayout>
    );
}
