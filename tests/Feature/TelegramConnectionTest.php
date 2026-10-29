<?php

use App\Models\User;
use App\Services\Telegram\TelegramUpdateHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

test('guests cannot start a Telegram connection', function () {
    $this->post(route('telegram.connection.store'))->assertRedirect(route('login'));
});

test('users are sent to the bot with a one-time link token', function () {
    config(['services.telegram.bot_username' => 'TaskflowBot']);
    Str::createRandomStringsUsing(fn (): string => 'link-token');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('telegram.connection.store'), [], ['X-Inertia' => 'true'])
        ->assertConflict()
        ->assertHeader('X-Inertia-Location', 'https://t.me/TaskflowBot?start=link-token');

    expect(Cache::get(TelegramUpdateHandler::linkCacheKey('link-token')))->toBe($user->id);

    Str::createRandomStringsNormally();
});

test('connecting fails clearly when the bot username is not configured', function () {
    config(['services.telegram.bot_username' => null]);

    $this->actingAs(User::factory()->create())
        ->post(route('telegram.connection.store'), [], ['X-Inertia' => 'true'])
        ->assertServiceUnavailable();
});

test('users can disconnect their Telegram chat', function () {
    $user = User::factory()->create(['telegram_chat_id' => 12345]);

    $this->actingAs($user)
        ->delete(route('telegram.connection.destroy'))
        ->assertRedirect();

    expect($user->fresh()->telegram_chat_id)->toBeNull();
});
