import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { GuideLine, VerticalGuides } from '@/components/guide-lines';
import { UserMenu } from '@/components/user-menu';
import { cn } from '@/lib/utils';
import type { RouteDefinition } from '@/wayfinder';
import { dashboard } from '@/routes';
import projects from '@/routes/projects';
import type { Auth } from '@/types';

type AppLayoutProps = {
    title: string;
    children: ReactNode;
};

const crosshairs = ['left-[calc(50%-28rem)]', 'left-[calc(50%+28rem)]'];

/**
 * Shell for the signed-in area: a centered column framed by guide lines, under a slim header.
 */
export default function AppLayout({ title, children }: AppLayoutProps) {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (
        <>
            <Head title={title} />
            <div className="relative isolate flex min-h-screen w-full flex-col overflow-x-clip bg-neutral-50 font-inter dark:bg-neutral-950">
                <VerticalGuides className="max-w-4xl" />
                <header className="relative">
                    <div className="mx-auto flex h-16 w-full max-w-4xl items-center justify-between px-4 sm:px-6">
                        <div className="flex items-center gap-6">
                            <Link
                                href={dashboard()}
                                className="text-sm font-semibold tracking-tight text-neutral-900 dark:text-white"
                            >
                                Taskflow
                            </Link>
                            <nav className="flex items-center gap-4">
                                <NavLink href={dashboard()}>Hoje</NavLink>
                                <NavLink href={projects.index()}>
                                    Projetos
                                </NavLink>
                            </nav>
                        </div>
                        <UserMenu name={auth.user.name} />
                    </div>
                    <GuideLine position="bottom" crosshairs={crosshairs} />
                </header>
                <main className="mx-auto flex w-full max-w-4xl flex-1 flex-col px-4 py-10 sm:px-6">
                    {children}
                </main>
            </div>
        </>
    );
}

function NavLink({
    href,
    children,
}: {
    href: RouteDefinition<'get'>;
    children: ReactNode;
}) {
    const { url } = usePage();
    const isActive = url === href.url || url.startsWith(`${href.url}/`);

    return (
        <Link
            href={href}
            className={cn(
                'text-sm transition-colors',
                isActive
                    ? 'font-medium text-neutral-900 dark:text-white'
                    : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white',
            )}
        >
            {children}
        </Link>
    );
}
