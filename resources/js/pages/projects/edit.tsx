import { Form, Link, router } from '@inertiajs/react';
import { Field } from '@/components/dialog';
import { TrashIcon } from '@/components/icons';
import AppLayout from '@/layouts/app-layout';
import { fieldClassName } from '@/lib/field-styles';
import { cn } from '@/lib/utils';
import { destroy, show, update } from '@/routes/projects';
import type { Project } from '@/types';

type ProjectEditProps = {
    project: Project;
};

export default function ProjectEdit({ project }: ProjectEditProps) {
    const deleteProject = () => {
        if (
            !window.confirm(
                `Excluir "${project.name}" e todas as suas tasks? Essa ação não pode ser desfeita.`,
            )
        ) {
            return;
        }

        router.visit(destroy(project.id));
    };

    return (
        <AppLayout title={`Configurações · ${project.name}`}>
            <Link
                href={show(project.id)}
                className="text-sm text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white"
            >
                ← {project.name}
            </Link>

            <h1 className="mt-3 text-2xl font-semibold tracking-tight text-neutral-900 dark:text-white">
                Configurações do projeto
            </h1>

            <section className="mt-8">
                <h2 className="text-sm font-semibold text-neutral-900 dark:text-white">
                    Geral
                </h2>
                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Atualize o nome ou a descrição.
                </p>

                <Form
                    {...update.form(project.id)}
                    options={{ preserveScroll: true }}
                    className="mt-4 flex flex-col gap-3 rounded-xl bg-white p-5 shadow-sm ring-1 shadow-black/5 ring-black/6 dark:bg-neutral-900 dark:ring-white/10"
                >
                    {({ errors, processing, recentlySuccessful }) => (
                        <>
                            <Field
                                label="Nome"
                                htmlFor="name"
                                error={errors.name}
                            >
                                <input
                                    id="name"
                                    name="name"
                                    defaultValue={project.name}
                                    placeholder="Ex.: API de pagamentos"
                                    required
                                    maxLength={255}
                                    aria-invalid={!!errors.name}
                                    className={cn(
                                        'h-11 px-3',
                                        fieldClassName(!!errors.name),
                                    )}
                                />
                            </Field>
                            <Field
                                label="Descrição (opcional)"
                                htmlFor="description"
                                error={errors.description}
                            >
                                <textarea
                                    id="description"
                                    name="description"
                                    rows={3}
                                    maxLength={2000}
                                    defaultValue={project.description ?? ''}
                                    placeholder="Objetivo, escopo ou links úteis"
                                    aria-invalid={!!errors.description}
                                    className={cn(
                                        'resize-none px-3 py-2.5',
                                        fieldClassName(!!errors.description),
                                    )}
                                />
                            </Field>

                            <div className="mt-2 flex items-center justify-end gap-3">
                                {recentlySuccessful && (
                                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                        Salvo.
                                    </p>
                                )}
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="h-9 rounded-lg bg-neutral-900 px-3 text-sm font-medium text-white shadow-sm transition-[background-color,scale] duration-150 hover:bg-neutral-800 active:scale-[0.98] disabled:opacity-60 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200"
                                >
                                    {processing
                                        ? 'Salvando…'
                                        : 'Salvar alterações'}
                                </button>
                            </div>
                        </>
                    )}
                </Form>
            </section>

            <section className="mt-10">
                <h2 className="text-sm font-semibold text-red-600 dark:text-red-400">
                    Zona de perigo
                </h2>

                <div className="mt-4 flex flex-col gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 shadow-black/5 ring-red-600/20 sm:flex-row sm:items-center sm:justify-between dark:bg-neutral-900 dark:ring-red-400/30">
                    <div className="min-w-0">
                        <p className="text-sm font-medium text-neutral-900 dark:text-white">
                            Excluir projeto
                        </p>
                        <p className="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400">
                            Remove o projeto e todas as suas tasks. Essa ação
                            não pode ser desfeita.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={deleteProject}
                        className="flex h-9 shrink-0 items-center gap-1.5 self-start rounded-lg bg-red-600 px-3 text-sm font-medium text-white shadow-sm transition-[background-color,scale] duration-150 hover:bg-red-700 active:scale-[0.98] sm:self-auto"
                    >
                        <TrashIcon />
                        Excluir projeto
                    </button>
                </div>
            </section>
        </AppLayout>
    );
}
