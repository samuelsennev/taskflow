import { usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { Auth } from '@/types';

export default function Profile() {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (
        <AppLayout title="Meu perfil">
            <div>
                <h1 className="text-2xl font-semibold tracking-tight text-neutral-900 dark:text-white">
                    Meu perfil
                </h1>
                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    {auth.user.name} · {auth.user.email}
                </p>
            </div>
        </AppLayout>
    );
}
