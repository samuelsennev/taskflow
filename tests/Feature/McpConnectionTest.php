<?php

use App\Enums\McpClient;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passport\Client;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

/**
 * Issue an OAuth access token (and its refresh token) as if the MCP client had been authorized.
 */
function issueMcpToken(User $user, McpClient $mcpClient = McpClient::Claude, bool $isRevoked = false, ?CarbonInterface $expiresAt = null): Token
{
    $client = Client::factory()->create([
        'redirect_uris' => [$mcpClient->redirectOrigins()[0].'/oauth/callback'],
    ]);

    $token = Token::forceCreate([
        'id' => Str::random(80),
        'user_id' => $user->id,
        'client_id' => $client->getKey(),
        'scopes' => ['mcp:use'],
        'revoked' => $isRevoked,
        'expires_at' => $expiresAt ?? now()->addYear(),
    ]);

    RefreshToken::forceCreate([
        'id' => Str::random(80),
        'access_token_id' => $token->id,
        'revoked' => $isRevoked,
        'expires_at' => now()->addYear(),
    ]);

    return $token;
}

test('the connectors page shows the mcp server url and which clients are connected', function (string $tokenState, array $connectedClients) {
    $user = User::factory()->create();

    match ($tokenState) {
        'claude' => issueMcpToken($user),
        'chatgpt' => issueMcpToken($user, McpClient::ChatGpt),
        'revoked' => issueMcpToken($user, isRevoked: true),
        'expired' => issueMcpToken($user, expiresAt: now()->subMinute()),
        'another user' => issueMcpToken(User::factory()->create()),
        'none' => null,
    };

    $this->actingAs($user)
        ->get(route('settings.connectors'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('mcp', ['server_url' => route('mcp'), 'connected_clients' => $connectedClients]));
})->with([
    'with a live claude token' => ['claude', ['claude']],
    'with a live chatgpt token' => ['chatgpt', ['chatgpt']],
    'without tokens' => ['none', []],
    'with a revoked token' => ['revoked', []],
    'with an expired token' => ['expired', []],
    'when only another user is connected' => ['another user', []],
]);

test('guests cannot disconnect mcp clients', function () {
    $this->delete(route('mcp.connection.destroy', McpClient::Claude))->assertRedirect(route('login'));
});

test('disconnecting a client revokes only its tokens and leaves other users connected', function () {
    $user = User::factory()->create();
    $token = issueMcpToken($user);
    $chatGptToken = issueMcpToken($user, McpClient::ChatGpt);
    $otherUsersToken = issueMcpToken(User::factory()->create());

    $this->actingAs($user)
        ->from(route('settings.connectors'))
        ->delete(route('mcp.connection.destroy', McpClient::Claude))
        ->assertRedirect(route('settings.connectors'));

    expect($token->fresh()->revoked)->toBeTrue()
        ->and($token->refreshToken->fresh()->revoked)->toBeTrue()
        ->and($chatGptToken->fresh()->revoked)->toBeFalse()
        ->and($otherUsersToken->fresh()->revoked)->toBeFalse()
        ->and($user->hasMcpClientConnected(McpClient::Claude))->toBeFalse()
        ->and($user->hasMcpClientConnected(McpClient::ChatGpt))->toBeTrue();
});
