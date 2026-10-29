import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { PlusIcon } from '@/components/icons';
import ProjectFormDialog from '@/components/projects/project-form-dialog';
import AppLayout from '@/layouts/app-layout';
import { show } from '@/routes/projects';
import type { ProjectSummary } from '@/types';

type ProjectsIndexProps = {
    projects: ProjectSummary[];
};

export default function ProjectsIndex({ projects }: ProjectsIndexProps) {
    const [isCreating, setIsCreating] = useState(false);

    return (
        <AppLayout title="Projetos">
            <div className="flex items-end justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-neutral-900 dark:text-white">
                        Projetos
                    </h1>
                    <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        {projects.length === 1
                            ? '1 projeto'
                            : `${projects.length} projetos`}
                    </p>
                </div>
                <button
                    type="button"
                    onClick={() => setIsCreating(true)}
                    className="flex h-9 shrink-0 items-center gap-1.5 rounded-lg bg-neutral-900 px-3 text-sm font-medium text-white shadow-sm transition-[background-color,scale] duration-150 hover:bg-neutral-800 active:scale-[0.98] dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200"
                >
                    <PlusIcon />
                    Novo projeto
                </button>
            </div>

            {projects.length === 0 ? (
                <div className="mt-6 flex flex-col items-center rounded-xl bg-white px-6 py-16 text-center shadow-sm ring-1 shadow-black/5 ring-black/6 dark:bg-neutral-900 dark:ring-white/10">
                    <p className="text-sm font-medium text-neutral-900 dark:text-white">
                        Nenhum projeto ainda
                    </p>
                    <p className="mt-1 max-w-sm text-sm text-balance text-neutral-500 dark:text-neutral-400">
                        Crie um projeto para agrupar as tasks de uma mesma
                        entrega.
                    </p>
                </div>
            ) : (
                <ul className="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {projects.map((project) => (
                        <li key={project.id}>
                            <ProjectCard project={project} />
                        </li>
                    ))}
                </ul>
            )}

            <ProjectFormDialog
                isOpen={isCreating}
                onClose={() => setIsCreating(false)}
            />
        </AppLayout>
    );
}

function ProjectCard({ project }: { project: ProjectSummary }) {
    const progress =
        project.tasks_count === 0
            ? 0
            : (project.completed_tasks_count / project.tasks_count) * 100;

    return (
        <Link
            href={show(project.id)}
            className="flex h-full flex-col rounded-xl bg-white p-5 shadow-sm ring-1 shadow-black/5 ring-black/6 transition-shadow hover:shadow-md dark:bg-neutral-900 dark:shadow-black/40 dark:ring-white/10"
        >
            <p className="text-sm font-semibold break-words text-neutral-900 dark:text-white">
                {project.name}
            </p>
            {project.description && (
                <p className="mt-1 line-clamp-2 text-sm break-words text-neutral-500 dark:text-neutral-400">
                    {project.description}
                </p>
            )}
            <div className="mt-auto pt-4">
                <div className="h-1 overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-800">
                    <div
                        className="h-full rounded-full bg-neutral-900 dark:bg-white"
                        style={{ width: `${progress}%` }}
                    />
                </div>
                <p className="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                    {project.completed_tasks_count} de {project.tasks_count}{' '}
                    tasks concluídas
                </p>
            </div>
        </Link>
    );
}
