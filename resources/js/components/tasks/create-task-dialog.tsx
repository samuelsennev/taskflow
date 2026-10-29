import { Form } from '@inertiajs/react';
import { SubmitButton } from '@/components/auth/auth-buttons';
import Dialog, { Field } from '@/components/dialog';
import { fieldClassName } from '@/lib/field-styles';
import { cn } from '@/lib/utils';
import { store } from '@/routes/tasks';

type CreateTaskDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    /** Prefilled due date (YYYY-MM-DD); left empty to make the user pick one. */
    defaultDueDate: string;
    minimumDueDate: string;
    /** Project the new task is filed under, if any. */
    projectId?: number;
};

/**
 * Modal with the form for adding a task.
 */
export default function CreateTaskDialog({
    isOpen,
    onClose,
    defaultDueDate,
    minimumDueDate,
    projectId,
}: CreateTaskDialogProps) {
    return (
        <Dialog
            isOpen={isOpen}
            onClose={onClose}
            title="Nova task"
            description="O que precisa ser entregue?"
        >
            <Form
                {...store.form()}
                options={{ preserveScroll: true }}
                onSuccess={onClose}
                className="mt-6 flex flex-col gap-3"
            >
                {({ errors, processing }) => (
                    <>
                        {projectId !== undefined && (
                            <input
                                type="hidden"
                                name="project_id"
                                value={projectId}
                            />
                        )}
                        <Field
                            label="Título"
                            htmlFor="title"
                            error={errors.title}
                        >
                            <input
                                id="title"
                                name="title"
                                placeholder="Ex.: Revisar o PR da API de pagamentos"
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
                            htmlFor="description"
                            error={errors.description}
                        >
                            <textarea
                                id="description"
                                name="description"
                                rows={3}
                                maxLength={2000}
                                placeholder="Detalhes, links ou critérios de pronto"
                                aria-invalid={!!errors.description}
                                className={cn(
                                    'resize-none px-3 py-2.5',
                                    fieldClassName(!!errors.description),
                                )}
                            />
                        </Field>
                        <Field
                            label="Data de entrega"
                            htmlFor="due_date"
                            error={errors.due_date}
                        >
                            <input
                                id="due_date"
                                name="due_date"
                                type="date"
                                defaultValue={defaultDueDate}
                                min={minimumDueDate}
                                required
                                aria-invalid={!!errors.due_date}
                                className={cn(
                                    'h-11 px-3',
                                    fieldClassName(!!errors.due_date),
                                )}
                            />
                        </Field>
                        {errors.project_id && (
                            <p className="text-xs text-red-600 dark:text-red-400">
                                {errors.project_id}
                            </p>
                        )}

                        <SubmitButton disabled={processing}>
                            {processing ? 'Criando…' : 'Criar task'}
                        </SubmitButton>
                    </>
                )}
            </Form>
        </Dialog>
    );
}
