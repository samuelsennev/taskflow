<?php

namespace App\Enums;

use Laravel\Passport\Client;

enum McpClient: string
{
    case Claude = 'claude';
    case ChatGpt = 'chatgpt';

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
        };
    }

    /**
     * Determine if the OAuth client was registered by this app, judging by its redirect URIs.
     */
    public function registered(Client $client): bool
    {
        return collect($client->redirect_uris)->contains(function (string $redirectUri): bool {
            $scheme = parse_url($redirectUri, PHP_URL_SCHEME);
            $host = parse_url($redirectUri, PHP_URL_HOST);

            return in_array("{$scheme}://{$host}", $this->redirectOrigins(), true);
        });
    }
}
