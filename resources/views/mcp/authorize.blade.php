<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">

    <title>Autorizar {{ $client->name }} - {{ config('app.name') }}</title>

    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css'])
</head>
<body class="bg-neutral-50 font-sans text-neutral-900 antialiased dark:bg-neutral-950 dark:text-white">
<div class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 shadow-black/5 ring-black/6 sm:p-8 dark:bg-neutral-900 dark:shadow-black/40 dark:ring-white/10">
            <div class="flex flex-col items-center text-center">
                @if ($client->logo_uri ?? null)
                    <img src="{{ $client->logo_uri }}" alt="{{ $client->name }}" class="size-12 rounded object-contain">
                @else
                    <div class="flex size-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">
                        <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>
                    </div>
                @endif

                <h1 class="mt-4 text-2xl font-semibold tracking-tight text-balance">
                    Autorizar {{ $client->name }}
                </h1>

                <p class="mt-2 text-sm text-pretty text-neutral-500 dark:text-neutral-400">
                    {{ $client->name }} poderá ver, criar, editar, concluir e excluir suas tasks e projetos no {{ config('app.name') }}.
                </p>

                @php($redirectUri = request()->query('redirect_uri', $client->redirect_uris[0] ?? null))
                @if ($redirectUri)
                    <p class="mt-2 text-xs break-all text-neutral-500 dark:text-neutral-400">
                        Você será redirecionado para {{ $redirectUri }}
                    </p>
                @endif

                @if ($client->client_uri ?? null)
                    <a href="{{ $client->client_uri }}" target="_blank" rel="noopener noreferrer" class="mt-2 text-sm text-neutral-700 underline underline-offset-4 dark:text-neutral-200">
                        {{ $client->client_uri }}
                    </a>
                @endif
            </div>

            <div class="mt-6 space-y-4">
                <div class="rounded-lg bg-neutral-50 p-4 ring-1 ring-black/6 dark:bg-neutral-950 dark:ring-white/10">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">Conectado como</p>
                    <p class="mt-1 text-sm font-medium break-all">{{ $user->email }}</p>
                </div>

                @if (count($scopes) > 0)
                    <div>
                        <p class="text-xs font-medium text-neutral-600 dark:text-neutral-300">Permissões</p>

                        <ul class="mt-2 space-y-2">
                            @foreach ($scopes as $scope)
                                <li class="flex items-start gap-2 text-sm text-neutral-700 dark:text-neutral-300">
                                    <svg class="mt-0.5 size-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M5 12l5 5l10 -10" />
                                    </svg>
                                    {{ $scope->description }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="mt-6 flex items-center gap-3">
                <form method="POST" action="{{ route('passport.authorizations.deny') }}" class="flex-1" id="cancelForm">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="state" value="">
                    <input type="hidden" name="client_id" value="{{ $client->id }}">
                    <input type="hidden" name="auth_token" value="{{ $authToken }}">
                    <button type="submit" class="flex h-11 w-full cursor-pointer items-center justify-center rounded-xl bg-white text-sm font-medium text-neutral-800 shadow-sm ring-1 shadow-black/5 ring-black/10 transition-[background-color,scale] duration-150 hover:bg-neutral-50 active:scale-[0.98] dark:bg-neutral-900 dark:text-neutral-200 dark:ring-white/10 dark:hover:bg-neutral-800">
                        Cancelar
                    </button>
                </form>

                <form method="POST" action="{{ route('passport.authorizations.approve') }}" class="flex-1" id="authorizeForm">
                    @csrf
                    <input type="hidden" name="state" value="">
                    <input type="hidden" name="client_id" value="{{ $client->id }}">
                    <input type="hidden" name="auth_token" value="{{ $authToken }}">
                    <button type="submit" id="authorizeButton" class="flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-neutral-900 text-sm font-medium text-white shadow-sm transition-[background-color,scale] duration-150 hover:bg-neutral-800 active:scale-[0.98] disabled:opacity-60 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200">
                        <svg id="loadingSpinner" class="hidden size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span id="authorizeText">Autorizar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('authorizeForm');
        const button = document.getElementById('authorizeButton');
        const authorizeText = document.getElementById('authorizeText');
        const loadingSpinner = document.getElementById('loadingSpinner');

        form.addEventListener('submit', function () {
            button.disabled = true;
            authorizeText.textContent = 'Autorizando…';
            loadingSpinner.classList.remove('hidden');

            // Close the popup once the browser follows the redirect back to the client.
            setTimeout(function () {
                const checkRedirect = setInterval(function () {
                    if (!window.location.href.includes('/oauth/authorize') ||
                        window.location.search.includes('code=') ||
                        window.location.search.includes('error=')) {
                        clearInterval(checkRedirect);
                        window.close();
                    }
                }, 100);

                setTimeout(function () {
                    clearInterval(checkRedirect);
                    window.close();
                }, 5000);
            }, 200);
        });

        document.getElementById('cancelForm').addEventListener('submit', function () {
            setTimeout(function () {
                window.close();
            }, 200);
        });
    });
</script>
</body>
</html>
