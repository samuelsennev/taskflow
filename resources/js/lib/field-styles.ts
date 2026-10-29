import { cn } from '@/lib/utils';

/**
 * Shared look for text fields: white surface, soft shadow and a ring that turns red on error.
 */
export function fieldClassName(hasError: boolean): string {
    return cn(
        'w-full rounded-lg bg-white text-sm text-neutral-900 shadow-sm ring-1 shadow-black/5 outline-none placeholder:text-neutral-400 focus:ring-2 max-sm:text-base dark:bg-neutral-900 dark:text-white',
        hasError
            ? 'ring-red-500/60 focus:ring-red-500 dark:ring-red-500/60'
            : 'ring-black/10 focus:ring-neutral-400 dark:ring-white/10 dark:focus:ring-neutral-600',
    );
}
