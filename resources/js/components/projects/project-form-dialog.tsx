import { Form } from '@inertiajs/react';
import { SubmitButton } from '@/components/auth/auth-buttons';
import Dialog, { Field } from '@/components/dialog';
import { fieldClassName } from '@/lib/field-styles';
import { cn } from '@/lib/utils';
import { store } from '@/routes/projects';

type ProjectFormDialogProps = {
    isOpen: boolean;
    onClose: () => void;
};

/**
 * Modal with the form for creating a project.
 */
export default function ProjectFormDialog({
    isOpen,
    onClose,
}: ProjectFormDialogProps) {
    return (
        <Dialog
            isOpen={isOpen}
            onClose={onClose}
            title="Novo projeto"
            description="Agrupe as tasks de uma mesma entrega."
        >
            <Form
                {...store.form()}
                options={{ preserveScroll: true }}
                onSuccess={onClose}
                className="mt-6 flex flex-col gap-3"
            >
                {({ errors, processing }) => (
                    <>
                        <Field label="Nome" htmlFor="name" error={errors.name}>
                            <input
                                id="name"
                                name="name"
                                placeholder="Ex.: API de pagamentos"
                                autoFocus
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
                                placeholder="Objetivo, escopo ou links úteis"
                                aria-invalid={!!errors.description}
                                className={cn(
                                    'resize-none px-3 py-2.5',
                                    fieldClassName(!!errors.description),
                                )}
                            />
                        </Field>

                        <SubmitButton disabled={processing}>
                            {processing ? 'Criando…' : 'Criar projeto'}
                        </SubmitButton>
                    </>
                )}
            </Form>
        </Dialog>
    );
}
