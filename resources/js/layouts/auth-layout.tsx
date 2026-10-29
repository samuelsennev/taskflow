import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { GuideLine, VerticalGuides } from '@/components/guide-lines';

type AuthLayoutProps = {
    title: string;
    description: string;
    footer: ReactNode;
    children: ReactNode;
};

const crosshairs = ['left-[calc(50%-14rem)]', 'left-[calc(50%+14rem)]'];

/**
 * Centered auth card framed by guide lines, with crosshair markers where the lines meet.
 */
export default function AuthLayout({
    title,
    description,
    footer,
    children,
}: AuthLayoutProps) {
    return (
        <>
            <Head title={title} />
            <div className="relative isolate flex min-h-screen w-full flex-col overflow-x-clip bg-neutral-50 font-inter dark:bg-neutral-950">
                <VerticalGuides className="max-w-md" />
                <div className="flex flex-1 flex-col justify-center py-20">
                    <div className="mx-auto w-full max-w-md">
                        <h2 className="px-6 text-center text-2xl font-semibold tracking-tight text-balance text-neutral-900 dark:text-white">
                            {title}
                        </h2>
                        <p className="mx-auto mt-3 max-w-sm px-4 text-center text-sm text-pretty text-neutral-500 dark:text-neutral-400">
                            {description}
                        </p>
                        <div className="relative mt-8 p-2 sm:p-3">
                            <GuideLine position="top" crosshairs={crosshairs} />
                            <GuideLine
                                position="bottom"
                                crosshairs={crosshairs}
                            />
                            <div className="rounded-xl bg-white p-6 shadow-sm ring-1 shadow-black/5 ring-black/6 sm:p-8 dark:bg-neutral-900 dark:shadow-black/40 dark:ring-white/10">
                                {children}
                            </div>
                        </div>
                        <p className="mt-6 text-center text-sm text-neutral-500 dark:text-neutral-400">
                            {footer}
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
