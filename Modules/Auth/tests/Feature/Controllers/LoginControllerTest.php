<?php

use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\TestCase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

uses(TestCase::class);

test('user can login with correct credentials', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson('api/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'message',
        'data' => ['token', 'user'],
    ]);
});

test('login fails with wrong credentials', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson('api/auth/login', [
        'email' => $user->email,
        'password' => 'wrongpass',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('login fails when email is not verified', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret123'),
        'email_verified_at' => null,
    ]);

    $response = $this->postJson('api/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();

    $token = JWTAuth::fromUser($user);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('api/auth/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Logged out successfully.']);
});

test('returns authenticated user data', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $token = JWTAuth::fromUser($user);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('api/auth/fetch/profile');

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['user']]);
});

test('authenticated user can refresh token', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $token = JWTAuth::fromUser($user);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('api/auth/refresh');

    $response->assertStatus(200);
});
