<?php

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passport\Client;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

/**
 * Issue an OAuth access token (and its refresh token) as if an MCP client had been authorized.
 */
function issueMcpToken(User $user, bool $isRevoked = false, ?CarbonInterface $expiresAt = null): Token
{
    $token = Token::forceCreate([
        'id' => Str::random(80),
        'user_id' => $user->id,
        'client_id' => Client::factory()->create()->getKey(),
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

test('the connectors page shows the mcp server url and whether a client is connected', function (string $tokenState, bool $isConnected) {
    $user = User::factory()->create();

    match ($tokenState) {
        'live' => issueMcpToken($user),
        'revoked' => issueMcpToken($user, isRevoked: true),
        'expired' => issueMcpToken($user, expiresAt: now()->subMinute()),
        'another user' => issueMcpToken(User::factory()->create()),
        'none' => null,
    };

    $this->actingAs($user)
        ->get(route('settings.connectors'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('mcp', ['server_url' => route('mcp'), 'is_connected' => $isConnected]));
})->with([
    'with a live token' => ['live', true],
    'without tokens' => ['none', false],
    'with a revoked token' => ['revoked', false],
    'with an expired token' => ['expired', false],
    'when only another user is connected' => ['another user', false],
]);

test('guests cannot disconnect mcp clients', function () {
    $this->delete(route('mcp.connection.destroy'))->assertRedirect(route('login'));
});

test('disconnecting revokes the user\'s tokens and leaves other users connected', function () {
    $user = User::factory()->create();
    $token = issueMcpToken($user);
    $otherUsersToken = issueMcpToken(User::factory()->create());

    $this->actingAs($user)
        ->from(route('settings.connectors'))
        ->delete(route('mcp.connection.destroy'))
        ->assertRedirect(route('settings.connectors'));

    expect($token->fresh()->revoked)->toBeTrue()
        ->and($token->refreshToken->fresh()->revoked)->toBeTrue()
        ->and($otherUsersToken->fresh()->revoked)->toBeFalse()
        ->and($user->hasMcpClientConnected())->toBeFalse();
});
