<?php

namespace App\Enums;

use Laravel\Passport\Client;

enum McpClient: string
{
    case Claude = 'claude';
    case ChatGpt = 'chatgpt';

    /**
     * Any client that isn't a known app, such as local tools redirecting to localhost.
     */
    case Other = 'other';

    /**
     * The origins this app redirects to after the user approves OAuth access.
     *
     * @return list<string>
     */
    public function redirectOrigins(): array
    {
        return match ($this) {
            self::Claude => ['https://claude.ai', 'https://claude.com'],
            self::ChatGpt => ['https://chatgpt.com'],
            self::Other => [],
        };
    }

    /**
     * Determine if the OAuth client belongs to this app, judging by its redirect URIs.
     *
     * Every client matches exactly one case, so no token is left out of the
     * connectors page or beyond the reach of revocation.
     */
    public function registered(?Client $client): bool
    {
        if ($this === self::Other) {
            return ! self::Claude->registered($client) && ! self::ChatGpt->registered($client);
        }

        return collect($client?->redirect_uris)->contains(function (string $redirectUri): bool {
            $scheme = parse_url($redirectUri, PHP_URL_SCHEME);
            $host = parse_url($redirectUri, PHP_URL_HOST);

            return in_array("{$scheme}://{$host}", $this->redirectOrigins(), true);
        });
    }
}
