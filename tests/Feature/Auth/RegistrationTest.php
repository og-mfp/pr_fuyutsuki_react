<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('registration normalizes email addresses', function () {
    $this->installationOwner();

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'New@Example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
});

test('registration rejects a legacy mixed case email', function () {
    $this->installationOwner();
    $existing = User::factory()->create();
    DB::table('users')->where('id', $existing->id)->update(['email' => 'Legacy@Example.com']);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'legacy@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertDatabaseMissing('users', ['email' => 'legacy@example.com']);
});

test('new users can register', function () {
    $this->installationOwner();
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    $user = User::where('email', 'test@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->groups()->where('slug', 'std-usr')->exists())->toBeTrue();
    expect($user->groups()->where('slug', 'owner')->exists())->toBeFalse();
    $response->assertRedirect(route('dashboard', absolute: false));
});
