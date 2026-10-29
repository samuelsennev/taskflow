import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { SubmitButton } from '@/components/auth/auth-buttons';
import Dialog, { Field } from '@/components/dialog';
import { fieldClassName } from '@/lib/field-styles';
import { cn } from '@/lib/utils';
import { store as storeProjectImport } from '@/routes/projects/tasks/import';
import { store as storeImport } from '@/routes/tasks/import';

type ImportTasksDialogProps = {
    isOpen: boolean;
    onClose: () => void;
    /** Project every imported task is filed under; outside a project, `#` headings pick the project. */
    projectId?: number;
};

/**
 * Modal for uploading a text file whose lines become tasks, reporting how many were created.
 */
export default function ImportTasksDialog({
    isOpen,
    onClose,
    projectId,
}: ImportTasksDialogProps) {
    const [importedCount, setImportedCount] = useState<number | null>(null);

    const close = () => {
        onClose();
        setImportedCount(null);
    };

    const action =
        projectId === undefined
            ? storeImport.form()
            : storeProjectImport.form(projectId);

    return (
        <Dialog
            isOpen={isOpen}
            onClose={close}
            title="Importar tasks"
            description="Envie um arquivo de texto: cada linha vira uma task."
        >
            {importedCount !== null ? (
                <div className="mt-6">
                    <p className="text-sm text-neutral-700 dark:text-neutral-300">
                        {importedCount === 1
                            ? '1 task importada.'
                            : `${importedCount} tasks importadas.`}
                    </p>
                    <SubmitButton type="button" onClick={close}>
                        Fechar
                    </SubmitButton>
                </div>
            ) : (
                <Form
                    {...action}
                    options={{ preserveScroll: true }}
                    onSuccess={(page) =>
                        setImportedCount(
                            Number(page.flash.imported_tasks_count ?? 0),
                        )
                    }
                    className="mt-6 flex flex-col gap-3"
                >
                    {({ errors, processing }) => (
                        <>
                            <Field
                                label="Arquivo"
                                htmlFor="file"
                                error={errors.file}
                            >
                                <input
                                    id="file"
                                    name="file"
                                    type="file"
                                    accept="text/*,.txt,.md,.csv,.tsv,.log"
                                    required
                                    aria-invalid={!!errors.file}
                                    className={cn(
                                        'px-3 py-2.5 file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-2.5 file:py-1 file:text-xs file:font-medium file:text-neutral-700 dark:file:bg-neutral-800 dark:file:text-neutral-200',
                                        fieldClassName(!!errors.file),
                                    )}
                                />
                            </Field>
                            <div className="rounded-lg bg-neutral-50 px-3 py-2.5 text-xs/5 text-neutral-500 ring-1 ring-black/5 dark:bg-neutral-950 dark:text-neutral-400 dark:ring-white/10">
                                <p>
                                    Use <code>[x]</code> para tasks concluídas e
                                    uma data (<code>30/10/2026</code> ou{' '}
                                    <code>2026-10-30</code>) para a entrega; sem
                                    data, a entrega fica para hoje. Linhas
                                    recuadas viram a descrição
                                    {projectId === undefined &&
                                        ' e títulos # Nome levam as tasks seguintes para o seu projeto com esse nome'}
                                    .
                                </p>
                                <pre className="mt-2 font-mono text-[11px]/4 text-neutral-600 dark:text-neutral-300">
                                    {
                                        '- [ ] Revisar o PR (30/10/2026)\n    Checar os testes\n- [x] Publicar release'
                                    }
                                </pre>
                            </div>

                            <SubmitButton disabled={processing}>
                                {processing ? 'Importando…' : 'Importar'}
                            </SubmitButton>
                        </>
                    )}
                </Form>
            )}
        </Dialog>
    );
}
