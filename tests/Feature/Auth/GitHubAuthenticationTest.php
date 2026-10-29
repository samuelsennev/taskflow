<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as GitHubUser;

test('guests are redirected to github to authorize', function () {
    Socialite::fake('github');

    $this->get(route('auth.github.redirect'))->assertRedirect();
});

test('a new user is created and signed in from github', function () {
    Socialite::fake('github', GitHubUser::fake([
        'id' => 'github-123',
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]));

    $response = $this->get(route('auth.github.callback'));

    $user = User::firstWhere('email', 'ada@example.com');

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Ada Lovelace')
        ->and($user->github_id)->toBe('github-123')
        ->and($user->password)->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard'));
});

test('a returning github user is signed in to the same account', function () {
    $user = User::factory()->create(['github_id' => 'github-123']);

    Socialite::fake('github', GitHubUser::fake([
        'id' => 'github-123',
        'name' => 'New Name On GitHub',
        'email' => 'changed@example.com',
    ]));

    $this->get(route('auth.github.callback'))->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect(User::count())->toBe(1);
});

test('github is linked to an existing account with the same email', function () {
    $user = User::factory()->create(['email' => 'ada@example.com']);

    Socialite::fake('github', GitHubUser::fake([
        'id' => 'github-123',
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]));

    $this->get(route('auth.github.callback'))->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh())
        ->github_id->toBe('github-123')
        ->password->not->toBeNull()
        ->and(User::count())->toBe(1);
});

test('linking github to an unverified account locks out whoever registered it', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->unverified()->create([
        'email' => 'ada@example.com',
        'remember_token' => 'attacker-remember-token',
        'telegram_chat_id' => 12345,
    ]);
    DB::table('sessions')->insert([
        'id' => 'attacker-session',
        'user_id' => $user->id,
        'payload' => '',
        'last_activity' => now()->getTimestamp(),
    ]);

    Socialite::fake('github', GitHubUser::fake([
        'id' => 'github-123',
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]));

    $this->get(route('auth.github.callback'))->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh())
        ->password->toBeNull()
        ->remember_token->not->toBe('attacker-remember-token')
        ->telegram_chat_id->toBeNull()
        ->email_verified_at->not->toBeNull()
        ->and(DB::table('sessions')->where('id', 'attacker-session')->exists())->toBeFalse();

    $this->post(route('logout'));
    $this->post(route('login.store'), ['email' => 'ada@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('the nickname is used when the github account has no name', function () {
    Socialite::fake('github', GitHubUser::fake([
        'id' => 'github-123',
        'name' => null,
        'nickname' => 'ada-l',
        'email' => 'ada@example.com',
    ]));

    $this->get(route('auth.github.callback'));

    expect(User::firstWhere('email', 'ada@example.com')->name)->toBe('ada-l');
});

test('sign in fails when the github account has no email', function () {
    Socialite::fake('github', GitHubUser::fake([
        'id' => 'github-123',
        'name' => 'Ada Lovelace',
        'email' => null,
    ]));

    $this->get(route('auth.github.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('github');

    $this->assertGuest();
    expect(User::count())->toBe(0);
});

test('cancelling on github returns to the login page with an error', function () {
    $this->get(route('auth.github.callback', ['error' => 'access_denied']))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('github');

    $this->assertGuest();
});

test('users who only signed up with github cannot log in with a password', function () {
    $user = User::factory()->create(['github_id' => 'github-123', 'password' => null]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'any-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('authenticated users cannot start github sign in', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('auth.github.redirect'))
        ->assertRedirect(route('dashboard'));
});
