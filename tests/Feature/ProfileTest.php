<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('profile'))->assertRedirect(route('login'));
});

test('users can see their profile', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('profile'));
});
