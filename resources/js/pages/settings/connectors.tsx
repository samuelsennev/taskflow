import { Link } from '@inertiajs/react';
import { useRef, useState } from 'react';
import type { ReactNode } from 'react';
import {
    ChatGptLogo,
    CheckIcon,
    ClaudeLogo,
    CopyIcon,
    PlugIcon,
    TelegramLogo,
} from '@/components/icons';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import mcpConnection from '@/routes/mcp/connection';
import settings from '@/routes/settings';
import telegramConnection from '@/routes/telegram/connection';

type ConnectorsProps = {
    telegram: {
        is_available: boolean;
        is_connected: boolean;
    };
    mcp: {
        server_url: string;
        connected_clients: McpClient[];
    };
};

type McpClient = 'claude' | 'chatgpt' | 'other';

/**
 * The assistants with a dedicated card; any other client is grouped under "Outros clientes MCP".
 */
type McpAssistant = Exclude<McpClient, 'other'>;

const secondaryButtonClassName =
    'shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium text-neutral-600 ring-1 ring-black/10 transition-colors hover:bg-neutral-50 hover:text-neutral-900 dark:text-neutral-300 dark:ring-white/10 dark:hover:bg-neutral-800 dark:hover:text-white';

const primaryButtonClassName =
    'shrink-0 rounded-lg bg-neutral-900 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition-[background-color,scale] duration-150 hover:bg-neutral-800 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-40 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200';

function ConnectionStatus({ isConnected }: { isConnected: boolean }) {
    return (
        <>
            <span
                className={cn(
                    'size-1.5 rounded-full',
                    isConnected
                        ? 'bg-emerald-500'
                        : 'bg-neutral-300 dark:bg-neutral-600',
                )}
            />
            <span className="text-xs font-normal whitespace-nowrap text-neutral-500 dark:text-neutral-400">
                {isConnected ? 'Conectado' : 'Não conectado'}
            </span>
        </>
    );
}

type McpConnectorDetails = {
    name: string;
    logo: ReactNode;
    logoClassName: string;
    /**
     * Prompt the user sends to the assistant to get walked through adding the Taskflow MCP connector.
     */
    connectionPrompt: (serverUrl: string) => string;
};

const mcpConnectors: Record<McpAssistant, McpConnectorDetails> = {
    claude: {
        name: 'Claude',
        logo: <ClaudeLogo className="size-6" />,
        logoClassName: 'bg-[#F4F3EE] dark:bg-[#F4F3EE]/10',
        connectionPrompt: (serverUrl) =>
            `Quero conectar o Taskflow a você. Me guie, passo a passo, para adicionar um conector personalizado em Configurações → Conectores usando esta URL de servidor MCP: ${serverUrl}. Depois de adicionar, vou entrar no Taskflow e aprovar o acesso.`,
    },
    chatgpt: {
        name: 'ChatGPT',
        logo: <ChatGptLogo className="size-6" />,
        logoClassName: 'bg-white text-black dark:bg-white/10 dark:text-white',
        connectionPrompt: (serverUrl) =>
            `Quero conectar o Taskflow a você. Me guie, passo a passo, para ativar o modo de desenvolvedor e criar um conector personalizado, com autenticação OAuth, usando esta URL de servidor MCP: ${serverUrl}. Depois de criar, vou entrar no Taskflow e aprovar o acesso.`,
    },
};

function CopyablePrompt({
    prompt,
    assistantName,
}: {
    prompt: string;
    assistantName: string;
}) {
    const [isCopied, setIsCopied] = useState(false);
    const promptRef = useRef<HTMLParagraphElement>(null);

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(prompt);
            setIsCopied(true);
            setTimeout(() => setIsCopied(false), 2000);
        } catch {
            if (promptRef.current) {
                window.getSelection()?.selectAllChildren(promptRef.current);
            }
        }
    };

    return (
        <div>
            <div className="flex items-center justify-between gap-4">
                <p className="text-sm text-neutral-600 dark:text-neutral-300">
                    Envie este prompt ao {assistantName} para conectar:
                </p>
                <button
                    type="button"
                    onClick={copy}
                    aria-label={isCopied ? 'Prompt copiado' : 'Copiar prompt'}
                    className="flex shrink-0 items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-neutral-600 transition-colors hover:bg-neutral-200/60 hover:text-neutral-900 dark:text-neutral-300 dark:hover:bg-white/10 dark:hover:text-white"
                >
                    {isCopied ? (
                        <CheckIcon className="size-3.5 text-emerald-600 dark:text-emerald-400" />
                    ) : (
                        <CopyIcon className="size-3.5" />
                    )}
                    {isCopied ? 'Copiado' : 'Copiar'}
                </button>
            </div>
            <div className="mt-3 rounded-lg bg-neutral-900 p-4 shadow-sm ring-1 ring-black/10 dark:bg-neutral-950 dark:ring-white/10">
                <p
                    ref={promptRef}
                    className="font-mono text-[13px] leading-relaxed break-words text-neutral-200 selection:bg-white/20"
                >
                    {prompt}
                </p>
            </div>
        </div>
    );
}

function McpConnector({
    client,
    serverUrl,
    isConnected,
}: {
    client: McpAssistant;
    serverUrl: string;
    isConnected: boolean;
}) {
    const { name, logo, logoClassName, connectionPrompt } =
        mcpConnectors[client];

    return (
        <li className="p-5">
            <div className="flex items-center gap-4">
                <div
                    className={cn(
                        'flex size-10 shrink-0 items-center justify-center rounded-lg ring-1 ring-black/5 dark:ring-white/10',
                        logoClassName,
                    )}
                >
                    {logo}
                </div>
                <div className="min-w-0 flex-1">
                    <p className="flex items-center gap-2 text-sm font-medium text-neutral-900 dark:text-white">
                        {name}
                        <span className="rounded-md bg-violet-50 px-1.5 py-0.5 font-mono text-[10px] font-semibold tracking-wide text-violet-700 ring-1 ring-violet-600/15 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-400/20">
                            MCP
                        </span>
                        <ConnectionStatus isConnected={isConnected} />
                    </p>
                    <p className="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400">
                        Peça ao {name} para ver, criar e concluir suas tasks.
                    </p>
                </div>
                {isConnected && (
                    <Link
                        href={mcpConnection.destroy(client)}
                        as="button"
                        className={secondaryButtonClassName}
                    >
                        Desconectar
                    </Link>
                )}
            </div>

            {!isConnected && (
                <div className="mt-4 rounded-lg bg-neutral-50 p-4 sm:ml-14 dark:bg-white/5">
                    <CopyablePrompt
                        prompt={connectionPrompt(serverUrl)}
                        assistantName={name}
                    />
                </div>
            )}
        </li>
    );
}

/**
 * Clients outside the known assistants, such as local tools, so their access can still be revoked.
 */
function OtherMcpClients() {
    return (
        <li className="flex items-center gap-4 p-5">
            <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-neutral-50 text-neutral-600 ring-1 ring-black/5 dark:bg-white/5 dark:text-neutral-300 dark:ring-white/10">
                <PlugIcon className="size-5" />
            </div>
            <div className="min-w-0 flex-1">
                <p className="flex items-center gap-2 text-sm font-medium text-neutral-900 dark:text-white">
                    Outros clientes MCP
                    <ConnectionStatus isConnected />
                </p>
                <p className="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400">
                    Apps locais, como editores e ferramentas de teste, com
                    acesso às suas tasks.
                </p>
            </div>
            <Link
                href={mcpConnection.destroy('other')}
                as="button"
                className={secondaryButtonClassName}
            >
                Desconectar
            </Link>
        </li>
    );
}

export default function Connectors({ telegram, mcp }: ConnectorsProps) {
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
                        <TelegramLogo className="size-10 shrink-0" />
                        <div className="min-w-0 flex-1">
                            <p className="flex items-center gap-2 text-sm font-medium text-neutral-900 dark:text-white">
                                Telegram
                                <ConnectionStatus
                                    isConnected={telegram.is_connected}
                                />
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
                                className={secondaryButtonClassName}
                            >
                                Desconectar
                            </Link>
                        ) : (
                            <Link
                                href={telegramConnection.store()}
                                as="button"
                                disabled={!telegram.is_available}
                                className={primaryButtonClassName}
                            >
                                Conectar
                            </Link>
                        )}
                    </li>
                    {(Object.keys(mcpConnectors) as McpAssistant[]).map(
                        (client) => (
                            <McpConnector
                                key={client}
                                client={client}
                                serverUrl={mcp.server_url}
                                isConnected={mcp.connected_clients.includes(
                                    client,
                                )}
                            />
                        ),
                    )}
                    {mcp.connected_clients.includes('other') && (
                        <OtherMcpClients />
                    )}
                </ul>
            </section>
        </AppLayout>
    );
}
