import { Link } from '@inertiajs/react';
import { TelegramIcon } from '@/components/icons';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import settings from '@/routes/settings';
import telegramConnection from '@/routes/telegram/connection';

type ConnectorsProps = {
    telegram: {
        is_available: boolean;
        is_connected: boolean;
    };
};

export default function Connectors({ telegram }: ConnectorsProps) {
    return (
        <AppLayout title="Conectores">
            <div>
                <h1 className="text-2xl font-semibold tracking-tight text-neutral-900 dark:text-white">
                    Configurações
                </h1>
                <nav className="mt-4 flex items-center gap-4 border-b border-black/6 dark:border-white/10">
                    <Link
                        href={settings.connectors()}
                        className="-mb-px border-b-2 border-neutral-900 pb-2 text-sm font-medium text-neutral-900 dark:border-white dark:text-white"
                    >
                        Conectores
                    </Link>
                </nav>
            </div>

            <section className="mt-8">
                <h2 className="text-sm font-semibold text-neutral-900 dark:text-white">
                    Conectores
                </h2>
                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Use o Taskflow a partir de outros apps.
                </p>

                <ul className="mt-4 divide-y divide-black/6 rounded-xl bg-white shadow-sm ring-1 shadow-black/5 ring-black/6 dark:divide-white/10 dark:bg-neutral-900 dark:ring-white/10">
                    <li className="flex items-center gap-4 p-5">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600 ring-1 ring-sky-600/10 dark:bg-sky-500/10 dark:text-sky-400 dark:ring-sky-400/20">
                            <TelegramIcon className="size-5" />
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="flex items-center gap-2 text-sm font-medium text-neutral-900 dark:text-white">
                                Telegram
                                <span
                                    className={cn(
                                        'size-1.5 rounded-full',
                                        telegram.is_connected
                                            ? 'bg-emerald-500'
                                            : 'bg-neutral-300 dark:bg-neutral-600',
                                    )}
                                />
                                <span className="text-xs font-normal text-neutral-500 dark:text-neutral-400">
                                    {telegram.is_connected
                                        ? 'Conectado'
                                        : 'Não conectado'}
                                </span>
                            </p>
                            <p className="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400">
                                {telegram.is_available
                                    ? 'Veja, crie e conclua tasks conversando com o bot.'
                                    : 'O bot ainda não foi configurado neste ambiente.'}
                            </p>
                        </div>
                        {telegram.is_connected ? (
                            <Link
                                href={telegramConnection.destroy()}
                                as="button"
                                className="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium text-neutral-600 ring-1 ring-black/10 transition-colors hover:bg-neutral-50 hover:text-neutral-900 dark:text-neutral-300 dark:ring-white/10 dark:hover:bg-neutral-800 dark:hover:text-white"
                            >
                                Desconectar
                            </Link>
                        ) : (
                            <Link
                                href={telegramConnection.store()}
                                as="button"
                                disabled={!telegram.is_available}
                                className="shrink-0 rounded-lg bg-neutral-900 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition-[background-color,scale] duration-150 hover:bg-neutral-800 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-40 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200"
                            >
                                Conectar
                            </Link>
                        )}
                    </li>
                </ul>
            </section>
        </AppLayout>
    );
}
