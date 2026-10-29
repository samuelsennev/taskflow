<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('settings.connectors'))->assertRedirect(route('login'));
});

test('the settings entry point opens the connectors page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('settings'))
        ->assertRedirect(route('settings.connectors'));
});

test('the connectors page shows whether the user has connected Telegram', function (?int $telegramChatId, bool $isConnected) {
    config(['services.telegram.bot_username' => 'TaskflowBot']);
    $user = User::factory()->create(['telegram_chat_id' => $telegramChatId]);

    $this->actingAs($user)
        ->get(route('settings.connectors'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/connectors')
            ->where('telegram', ['is_available' => true, 'is_connected' => $isConnected]));
})->with([
    'connected' => [12345, true],
    'not connected' => [null, false],
]);

test('the connectors page marks Telegram as unavailable when the bot is not configured', function () {
    config(['services.telegram.bot_username' => null]);

    $this->actingAs(User::factory()->create())
        ->get(route('settings.connectors'))
        ->assertInertia(fn (Assert $page) => $page->where('telegram.is_available', false));
});
