import { Form, Link } from '@inertiajs/react';
import {
    EmailDivider,
    GitHubButton,
    SubmitButton,
} from '@/components/auth/auth-buttons';
import AuthInput from '@/components/auth/auth-input';
import { LockIcon, MailIcon } from '@/components/icons';
import AuthLayout from '@/layouts/auth-layout';
import { register } from '@/routes';
import { store } from '@/routes/login';

type LoginProps = {
    status?: string;
};

export default function Login({ status }: LoginProps) {
    return (
        <AuthLayout
            title="Bem-vindo de volta"
            description="Entre na sua conta para continuar de onde parou."
            footer={
                <>
                    Ainda não tem uma conta?{' '}
                    <Link
                        href={register()}
                        className="font-medium text-neutral-900 underline-offset-4 hover:underline dark:text-white"
                    >
                        Cadastre-se
                    </Link>
                </>
            }
        >
            {status && (
                <p className="mb-5 text-center text-sm font-medium text-green-600">
                    {status}
                </p>
            )}

            <GitHubButton>Entrar com GitHub</GitHubButton>
            <EmailDivider />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                resetOnError={['password']}
            >
                {({ errors, processing }) => (
                    <>
                        <div className="flex flex-col gap-3">
                            <AuthInput
                                name="email"
                                type="email"
                                placeholder="Endereço de e-mail"
                                autoComplete="email"
                                autoFocus
                                required
                                icon={<MailIcon />}
                                error={errors.email}
                            />
                            <AuthInput
                                name="password"
                                type="password"
                                placeholder="Senha"
                                autoComplete="current-password"
                                required
                                icon={<LockIcon />}
                                error={errors.password}
                            />
                        </div>

                        <SubmitButton disabled={processing}>
                            {processing ? 'Entrando…' : 'Entrar'}
                        </SubmitButton>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
