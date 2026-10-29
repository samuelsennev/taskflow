import { AnimatePresence, motion } from 'motion/react';
import type { ReactNode } from 'react';
import { useEffect, useId } from 'react';
import { CloseIcon } from '@/components/icons';

type DialogProps = {
    isOpen: boolean;
    onClose: () => void;
    title: string;
    description?: string;
    children: ReactNode;
};

/**
 * Animated modal card that closes on Escape, on the close button or on a backdrop click.
 */
export default function Dialog({
    isOpen,
    onClose,
    title,
    description,
    children,
}: DialogProps) {
    const titleId = useId();

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        window.addEventListener('keydown', closeOnEscape);

        return () => window.removeEventListener('keydown', closeOnEscape);
    }, [isOpen, onClose]);

    return (
        <AnimatePresence>
            {isOpen && (
                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 0.15 }}
                    className="fixed inset-0 z-50 flex items-end justify-center bg-neutral-950/30 p-4 backdrop-blur-[2px] sm:items-center"
                    onClick={onClose}
                >
                    <motion.div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby={titleId}
                        initial={{ opacity: 0, y: 12, scale: 0.98 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: 12, scale: 0.98 }}
                        transition={{ duration: 0.18, ease: 'easeOut' }}
                        className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl ring-1 shadow-black/10 ring-black/6 sm:p-8 dark:bg-neutral-900 dark:ring-white/10"
                        onClick={(event) => event.stopPropagation()}
                    >
                        <div className="flex items-start justify-between gap-4">
                            <div className="min-w-0">
                                <h2
                                    id={titleId}
                                    className="text-lg font-semibold tracking-tight break-words text-neutral-900 dark:text-white"
                                >
                                    {title}
                                </h2>
                                {description && (
                                    <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                        {description}
                                    </p>
                                )}
                            </div>
                            <button
                                type="button"
                                onClick={onClose}
                                aria-label="Fechar"
                                className="flex size-8 items-center justify-center rounded-md text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200"
                            >
                                <CloseIcon />
                            </button>
                        </div>

                        {children}
                    </motion.div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}

type FieldProps = {
    label: string;
    htmlFor: string;
    error?: string;
    children: ReactNode;
};

/**
 * Form field wrapper: a small label above the control and the validation error below it.
 */
export function Field({ label, htmlFor, error, children }: FieldProps) {
    return (
        <div className="flex flex-col gap-1.5">
            <label
                htmlFor={htmlFor}
                className="text-xs font-medium text-neutral-600 dark:text-neutral-300"
            >
                {label}
            </label>
            {children}
            {error && (
                <p className="text-xs text-red-600 dark:text-red-400">
                    {error}
                </p>
            )}
        </div>
    );
}
