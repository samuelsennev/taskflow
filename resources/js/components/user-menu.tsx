import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import {
    ChevronDownIcon,
    LogoutIcon,
    SettingsIcon,
    UserIcon,
} from '@/components/icons';
import { cn } from '@/lib/utils';
import { logout, profile } from '@/routes';
import settings from '@/routes/settings';

const itemClassName =
    'flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-sm text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-900 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:hover:text-white';

/**
 * Account dropdown in the app header, triggered by the signed-in user's name.
 */
export function UserMenu({ name }: { name: string }) {
    const [isOpen, setIsOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        const closeOnOutsideClick = (event: PointerEvent) => {
            if (!containerRef.current?.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };

        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setIsOpen(false);
            }
        };

        document.addEventListener('pointerdown', closeOnOutsideClick);
        document.addEventListener('keydown', closeOnEscape);

        return () => {
            document.removeEventListener('pointerdown', closeOnOutsideClick);
            document.removeEventListener('keydown', closeOnEscape);
        };
    }, [isOpen]);

    return (
        <div ref={containerRef} className="relative">
            <button
                type="button"
                onClick={() => setIsOpen((open) => !open)}
                aria-haspopup="menu"
                aria-expanded={isOpen}
                className="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm text-neutral-500 transition-colors hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white"
            >
                <span className="hidden sm:inline">{name}</span>
                <UserIcon className="size-4 sm:hidden" />
                <ChevronDownIcon
                    className={cn(
                        'size-3.5 transition-transform',
                        isOpen && 'rotate-180',
                    )}
                />
            </button>
            <div
                role="menu"
                className={cn(
                    'absolute right-0 z-20 mt-2 w-48 origin-top-right rounded-xl bg-white p-1 shadow-lg ring-1 ring-black/10 transition duration-150 ease-out dark:bg-neutral-900 dark:ring-white/10',
                    isOpen
                        ? 'scale-100 opacity-100'
                        : 'pointer-events-none invisible scale-95 opacity-0',
                )}
            >
                <Link
                    href={profile()}
                    role="menuitem"
                    onClick={() => setIsOpen(false)}
                    className={itemClassName}
                >
                    <UserIcon />
                    Meu perfil
                </Link>
                <Link
                    href={settings.connectors()}
                    role="menuitem"
                    onClick={() => setIsOpen(false)}
                    className={itemClassName}
                >
                    <SettingsIcon />
                    Configurações
                </Link>
                <div className="my-1 h-px bg-black/5 dark:bg-white/10" />
                <Link
                    href={logout()}
                    as="button"
                    role="menuitem"
                    className={itemClassName}
                >
                    <LogoutIcon />
                    Sair
                </Link>
            </div>
        </div>
    );
}
