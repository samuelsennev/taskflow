import { Form, Link } from '@inertiajs/react';
import {
    EmailDivider,
    GitHubButton,
    SubmitButton,
} from '@/components/auth/auth-buttons';
import AuthInput from '@/components/auth/auth-input';
import { LockIcon, MailIcon, UserIcon } from '@/components/icons';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { store } from '@/routes/register';

export default function Register() {
    return (
        <AuthLayout
            title="Crie sua conta"
            description="Comece em menos de um minuto. Sem cartão de crédito."
            footer={
                <>
                    Já tem uma conta?{' '}
                    <Link
                        href={login()}
                        className="font-medium text-neutral-900 underline-offset-4 hover:underline dark:text-white"
                    >
                        Entrar
                    </Link>
                </>
            }
        >
            <GitHubButton>Cadastrar com GitHub</GitHubButton>
            <EmailDivider />

            <Form {...store.form()} resetOnError={['password']}>
                {({ errors, processing }) => (
                    <>
                        <div className="flex flex-col gap-3">
                            <AuthInput
                                name="name"
                                placeholder="Nome completo"
                                autoComplete="name"
                                autoFocus
                                required
                                icon={<UserIcon />}
                                error={errors.name}
                            />
                            <AuthInput
                                name="email"
                                type="email"
                                placeholder="Endereço de e-mail"
                                autoComplete="email"
                                required
                                icon={<MailIcon />}
                                error={errors.email}
                            />
                            <AuthInput
                                name="password"
                                type="password"
                                placeholder="Senha (mín. 8 caracteres)"
                                autoComplete="new-password"
                                required
                                icon={<LockIcon />}
                                error={errors.password}
                            />
                        </div>

                        <SubmitButton disabled={processing}>
                            {processing ? 'Criando conta…' : 'Criar conta'}
                        </SubmitButton>
                    </>
                )}
            </Form>

            <p className="mt-4 text-center text-xs text-pretty text-neutral-400">
                Ao se cadastrar, você concorda com os nossos{' '}
                <a
                    href="#"
                    className="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-200"
                >
                    Termos de Uso
                </a>{' '}
                e{' '}
                <a
                    href="#"
                    className="underline underline-offset-2 hover:text-neutral-700 dark:hover:text-neutral-200"
                >
                    Política de Privacidade
                </a>
                .
            </p>
        </AuthLayout>
    );
}
