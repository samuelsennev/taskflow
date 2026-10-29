import { cn } from '@/lib/utils';

type GuideLineProps = {
    position: 'top' | 'bottom';
    /** Tailwind `left-*` classes placing the crosshairs where the vertical guides cross this line. */
    crosshairs: string[];
};

/**
 * Full-width horizontal guide line with crosshair markers.
 */
export function GuideLine({ position, crosshairs }: GuideLineProps) {
    return (
        <div
            className={cn(
                'pointer-events-none absolute left-1/2 h-px w-screen -translate-x-1/2 bg-black/6 dark:bg-white/8',
                position === 'top' ? 'top-0' : 'bottom-0',
            )}
        >
            {crosshairs.map((crosshair) => (
                <Crosshair key={crosshair} className={crosshair} />
            ))}
        </div>
    );
}

/**
 * Vertical guide lines framing a centered column.
 */
export function VerticalGuides({ className }: { className: string }) {
    return (
        <div
            className={cn(
                'pointer-events-none absolute inset-y-0 left-1/2 -z-10 hidden w-full -translate-x-1/2 border-x border-black/6 sm:block dark:border-white/8',
                className,
            )}
        />
    );
}

function Crosshair({ className }: { className: string }) {
    return (
        <svg
            viewBox="0 0 13 13"
            aria-hidden="true"
            className={cn(
                'absolute top-1/2 hidden size-3.25 -translate-1/2 bg-neutral-50 text-black/15 sm:block dark:bg-neutral-950 dark:text-white/20',
                className,
            )}
        >
            <path d="M6.5 0v13M0 6.5h13" fill="none" stroke="currentColor" />
        </svg>
    );
}
