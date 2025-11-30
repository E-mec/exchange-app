<?php

use Modules\Auth\Models\User;
use Modules\Auth\actions\LoginAction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Tests\TestCase;

uses(TestCase::class);

test('login succeeds with correct credentials and verified email', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'password' => Hash::make('secret123'),
    ]);

    $result = app(LoginAction::class)->handle($user->email, 'secret123');

    expect($result->id)->toBe($user->id);
});

test('login fails when user does not exist', function () {
    $this->expectException(ValidationException::class);

    app(LoginAction::class)->handle('missing@example.com', 'secret123');
});

test('login fails with wrong password', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'password' => Hash::make('secret123'),
    ]);

    $this->expectException(ValidationException::class);

    app(LoginAction::class)->handle($user->email, 'wrongpass');
});

test('login fails if email is not verified', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'password' => Hash::make('secret123'),
    ]);

    $this->expectException(ValidationException::class);

    app(LoginAction::class)->handle($user->email, 'secret123');
});
