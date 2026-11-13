<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();
    $payload = ['name' => 'Test User', 'email' => 'test@example.com'];
    $this->authorizeSecurityChange($user, 'profile.update', $payload);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), $payload);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email is stored in lowercase so the user can still log in', function () {
    $user = User::factory()->create();
    $payload = ['name' => 'Test User', 'email' => 'Test@Example.com'];
    $this->authorizeSecurityChange($user, 'profile.update', $payload);

    $this
        ->actingAs($user)
        ->patch(route('profile.update'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email)->toBe('test@example.com');

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => 'Test@Example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('email must be unique regardless of case', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $user = User::factory()->create();
    $payload = ['name' => 'Test User', 'email' => 'Taken@Example.com'];
    $this->authorizeSecurityChange($user, 'profile.update', $payload);

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), $payload);

    $response
        ->assertSessionHasErrors('email')
        ->assertRedirect(route('profile.edit'));
});

test('profile updates reject an email belonging to a legacy mixed case account', function () {
    $existing = User::factory()->create();
    DB::table('users')->where('id', $existing->id)->update(['email' => 'Legacy@Example.com']);
    $user = User::factory()->create();
    $payload = ['name' => 'Test User', 'email' => 'legacy@example.com'];
    $this->authorizeSecurityChange($user, 'profile.update', $payload);

    $this->patch(route('profile.update'), $payload)->assertSessionHasErrors('email');

    expect($user->refresh()->email)->not->toBe('legacy@example.com');
});

test('profile updates can retain their own mixed case legacy email', function () {
    $user = User::factory()->create();
    DB::table('users')->where('id', $user->id)->update(['email' => 'Legacy@Example.com']);
    $user->refresh();
    $payload = ['name' => 'Test User', 'email' => 'legacy@example.com'];
    $this->authorizeSecurityChange($user, 'profile.update', $payload);

    $this->patch(route('profile.update'), $payload)->assertSessionHasNoErrors();

    expect($user->refresh()->email)->toBe('legacy@example.com');
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
            'current_password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
