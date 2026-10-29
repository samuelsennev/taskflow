import { useState } from 'react';
import type { InputHTMLAttributes, ReactNode } from 'react';
import { EyeIcon, EyeOffIcon } from '@/components/icons';
import { fieldClassName } from '@/lib/field-styles';
import { cn } from '@/lib/utils';

type AuthInputProps = InputHTMLAttributes<HTMLInputElement> & {
    name: string;
    icon: ReactNode;
    error?: string;
};

/**
 * Text input with a leading icon. Password inputs get a show/hide toggle.
 */
export default function AuthInput({
    name,
    icon,
    error,
    type = 'text',
    ...props
}: AuthInputProps) {
    const [isPasswordVisible, setIsPasswordVisible] = useState(false);
    const isPassword = type === 'password';

    return (
        <div>
            <div className="relative">
                <span className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400">
                    {icon}
                </span>
                <input
                    id={name}
                    name={name}
                    type={isPassword && isPasswordVisible ? 'text' : type}
                    aria-invalid={!!error}
                    aria-describedby={error ? `${name}-error` : undefined}
                    className={cn(
                        'h-11 pl-10',
                        isPassword ? 'pr-11' : 'pr-3',
                        fieldClassName(!!error),
                    )}
                    {...props}
                />
                {isPassword && (
                    <button
                        type="button"
                        onClick={() => setIsPasswordVisible(!isPasswordVisible)}
                        aria-label={
                            isPasswordVisible
                                ? 'Ocultar senha'
                                : 'Mostrar senha'
                        }
                        className="absolute top-1/2 right-1.5 flex size-8 -translate-y-1/2 items-center justify-center rounded-md text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200"
                    >
                        {isPasswordVisible ? (
                            <EyeOffIcon className="size-4" />
                        ) : (
                            <EyeIcon className="size-4" />
                        )}
                    </button>
                )}
            </div>
            {error && (
                <p
                    id={`${name}-error`}
                    className="mt-1.5 text-xs text-red-600 dark:text-red-400"
                >
                    {error}
                </p>
            )}
        </div>
    );
}
