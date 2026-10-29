<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('the registration page can be rendered', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/register'));
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $user = User::firstWhere('email', 'test@example.com');

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Test User')
        ->and(Hash::check('password', $user->password))->toBeTrue();

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard'));
});

test('registration requires a unique email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'taken@example.com',
        'password' => 'password',
    ])->assertSessionHasErrors(['email' => 'Este e-mail já está em uso.']);

    $this->assertGuest();
});

test('registration requires a password of at least eight characters', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'short',
    ])->assertSessionHasErrors(['password' => 'O campo senha deve ter pelo menos 8 caracteres.']);

    $this->assertGuest();
    expect(User::count())->toBe(0);
});
