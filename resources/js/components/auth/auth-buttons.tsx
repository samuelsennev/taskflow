import { usePage } from '@inertiajs/react';
import type { ButtonHTMLAttributes, ReactNode } from 'react';
import { GitHubIcon } from '@/components/icons';
import { cn } from '@/lib/utils';
import { redirect } from '@/routes/auth/github';

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement>;

/**
 * Full-page link to the GitHub OAuth redirect, with any GitHub sign-in error shown below it.
 */
export function GitHubButton({ children }: { children: ReactNode }) {
    const { errors } = usePage().props;

    return (
        <>
            <a
                href={redirect.url()}
                className="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-neutral-900 text-sm font-medium text-white shadow-sm transition-[background-color,scale] duration-150 hover:bg-neutral-800 active:scale-[0.98] dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200"
            >
                <GitHubIcon />
                {children}
            </a>
            {errors.github && (
                <p className="mt-2 text-center text-xs text-red-600 dark:text-red-400">
                    {errors.github}
                </p>
            )}
        </>
    );
}

export function SubmitButton({ children, className, ...props }: ButtonProps) {
    return (
        <button
            type="submit"
            className={cn(
                'mt-4 flex h-11 w-full items-center justify-center rounded-xl bg-white text-sm font-medium text-neutral-800 shadow-sm ring-1 shadow-black/5 ring-black/10 transition-[background-color,scale] duration-150 hover:bg-neutral-50 active:scale-[0.98] disabled:opacity-60 dark:bg-neutral-900 dark:text-neutral-200 dark:ring-white/10 dark:hover:bg-neutral-800',
                className,
            )}
            {...props}
        >
            {children}
        </button>
    );
}

export function EmailDivider() {
    return (
        <div className="my-5 flex items-center gap-3 text-xs text-neutral-400">
            <span className="h-px flex-1 bg-neutral-200 dark:bg-neutral-800" />
            ou com e-mail
            <span className="h-px flex-1 bg-neutral-200 dark:bg-neutral-800" />
        </div>
    );
}
