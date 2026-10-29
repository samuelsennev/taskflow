import { Form, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import Dialog, { Field } from '@/components/dialog';
import { CheckIcon } from '@/components/icons';
import { fieldClassName } from '@/lib/field-styles';
import { cn } from '@/lib/utils';
import { show } from '@/routes/projects';
import { update } from '@/routes/tasks';
import type { Task } from '@/types';

type TaskDetailsDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    task: Task | undefined;
    onToggleCompletion: () => void;
};

const dueDateFormatter = new Intl.DateTimeFormat('pt-BR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    timeZone: 'UTC',
});

/**
 * Modal with the full details of a task, a shortcut to toggle its completion
 * and a form to edit its title and description.
 */
export default function TaskDetailsDialog({
    isOpen,
    onClose,
    task,
    onToggleCompletion,
}: TaskDetailsDialogProps) {
    const [isEditing, setIsEditing] = useState(false);
    const [wasOpen, setWasOpen] = useState(isOpen);

    // Always reopen the modal in read mode.
    if (isOpen !== wasOpen) {
        setWasOpen(isOpen);

        if (isOpen) {
            setIsEditing(false);
        }
    }

    return (
        <Dialog
            isOpen={isOpen && task !== undefined}
            onClose={onClose}
            title={isEditing ? 'Editar task' : (task?.title ?? '')}
        >
            {task && isEditing && (
                <Form
                    {...update.form(task.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setIsEditing(false)}
                    className="mt-6 flex flex-col gap-3"
                >
                    {({ errors, processing }) => (
                        <>
                            <Field
                                label="Título"
                                htmlFor="edit-title"
                                error={errors.title}
                            >
                                <input
                                    id="edit-title"
                                    name="title"
                                    defaultValue={task.title}
                                    autoFocus
                                    required
                                    maxLength={255}
                                    aria-invalid={!!errors.title}
                                    className={cn(
                                        'h-11 px-3',
                                        fieldClassName(!!errors.title),
                                    )}
                                />
                            </Field>
                            <Field
                                label="Descrição (opcional)"
                                htmlFor="edit-description"
                                error={errors.description}
                            >
                                <textarea
                                    id="edit-description"
                                    name="description"
                                    rows={4}
                                    maxLength={2000}
                                    defaultValue={task.description ?? ''}
                                    placeholder="Detalhes, links ou critérios de pronto"
                                    aria-invalid={!!errors.description}
                                    className={cn(
                                        'resize-none px-3 py-2.5',
                                        fieldClassName(!!errors.description),
                                    )}
                                />
                            </Field>

                            <div className="mt-4 flex gap-2">
                                <button
                                    type="button"
                                    onClick={() => setIsEditing(false)}
                                    className="flex h-11 flex-1 items-center justify-center rounded-xl bg-white text-sm font-medium text-neutral-800 shadow-sm ring-1 shadow-black/5 ring-black/10 transition-colors hover:bg-neutral-50 dark:bg-neutral-900 dark:text-neutral-200 dark:ring-white/10 dark:hover:bg-neutral-800"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="flex h-11 flex-1 items-center justify-center rounded-xl bg-neutral-900 text-sm font-medium text-white shadow-sm transition-[background-color,scale] duration-150 hover:bg-neutral-800 active:scale-[0.98] disabled:opacity-60 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200"
                                >
                                    {processing ? 'Salvando…' : 'Salvar'}
                                </button>
                            </div>
                        </>
                    )}
                </Form>
            )}
            {task && !isEditing && (
                <>
                    <p
                        className={cn(
                            'mt-4 text-sm break-words whitespace-pre-line',
                            task.description
                                ? 'text-neutral-600 dark:text-neutral-300'
                                : 'text-neutral-400 italic dark:text-neutral-500',
                        )}
                    >
                        {task.description ?? 'Sem descrição.'}
                    </p>

                    <dl className="mt-6 divide-y divide-black/6 border-y border-black/6 text-sm dark:divide-white/8 dark:border-white/8">
                        {task.project !== undefined && (
                            <DetailRow label="Projeto">
                                {task.project ? (
                                    <Link
                                        href={show(task.project.id)}
                                        className="font-medium text-neutral-900 underline-offset-2 hover:underline dark:text-white"
                                    >
                                        {task.project.name}
                                    </Link>
                                ) : (
                                    <span className="text-neutral-400 dark:text-neutral-500">
                                        Sem projeto
                                    </span>
                                )}
                            </DetailRow>
                        )}
                        {task.due_date && (
                            <DetailRow label="Entrega">
                                {dueDateFormatter.format(
                                    new Date(`${task.due_date}T00:00:00Z`),
                                )}
                            </DetailRow>
                        )}
                        <DetailRow label="Status">
                            <span className="flex items-center gap-1.5">
                                <span
                                    className={cn(
                                        'size-1.5 rounded-full',
                                        task.is_completed
                                            ? 'bg-emerald-500'
                                            : 'bg-amber-400',
                                    )}
                                />
                                {task.is_completed ? 'Concluída' : 'Pendente'}
                            </span>
                        </DetailRow>
                    </dl>

                    <div className="mt-6 flex gap-2">
                        <button
                            type="button"
                            onClick={() => setIsEditing(true)}
                            className="flex h-11 items-center justify-center rounded-xl bg-white px-4 text-sm font-medium text-neutral-800 shadow-sm ring-1 shadow-black/5 ring-black/10 transition-colors hover:bg-neutral-50 dark:bg-neutral-900 dark:text-neutral-200 dark:ring-white/10 dark:hover:bg-neutral-800"
                        >
                            Editar
                        </button>
                        <button
                            type="button"
                            onClick={onToggleCompletion}
                            className={cn(
                                'flex h-11 flex-1 items-center justify-center gap-1.5 rounded-xl text-sm font-medium shadow-sm transition-[background-color,scale] duration-150 active:scale-[0.98]',
                                task.is_completed
                                    ? 'bg-white text-neutral-800 ring-1 shadow-black/5 ring-black/10 hover:bg-neutral-50 dark:bg-neutral-900 dark:text-neutral-200 dark:ring-white/10 dark:hover:bg-neutral-800'
                                    : 'bg-neutral-900 text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200',
                            )}
                        >
                            {!task.is_completed && <CheckIcon />}
                            {task.is_completed
                                ? 'Marcar como pendente'
                                : 'Concluir task'}
                        </button>
                    </div>
                </>
            )}
        </Dialog>
    );
}

function DetailRow({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <div className="flex items-center justify-between gap-4 py-2.5">
            <dt className="text-neutral-500 dark:text-neutral-400">{label}</dt>
            <dd className="min-w-0 text-right break-words text-neutral-900 dark:text-white">
                {children}
            </dd>
        </div>
    );
}
