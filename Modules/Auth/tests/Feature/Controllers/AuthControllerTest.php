<?php

use App\Enums\OtpTypeEnum;
use Illuminate\Support\Facades\Cache;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\app\Notifications\OtpNotification;

uses(TestCase::class);

test('user can register', function () {

    Notification::fake();
    $payload = [
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'firstname' => 'John',
        'lastname' => 'Doe',
        'username' => 'Doe',
        'phone_number' => '08104133974',
        'dial_code' => '+234',
        'country' => 'Nigeria',
        'pin' => '123456',
    ];

    $response = $this->postJson('/api/auth/register', $payload);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'user' => [
                    'uuid',
                    'email',
                    'firstname',
                    'lastname'
                ]
            ]
        ]);

    // OTP was sent

    $user = User::first();
    expect($user)->not->toBeNull();

    Notification::assertSentTo($user, OtpNotification::class);

    $cacheKey = OtpTypeEnum::REGISTRATION->value . "-otp:{$user->id}";
    expect(Cache::has($cacheKey))->toBeTrue();
});

test('can verify otp after registeration', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    // Manually put OTP in cache exactly how SendOtpAction stores it
    $otp = "123456";
    $cacheKey = OtpTypeEnum::REGISTRATION->value . "-otp:{$user->id}";
    Cache::put($cacheKey, $otp, now()->addMinutes(2));

    $payload = [
        'email' => $user->email,
        'otp'   => $otp,
    ];

    $response = $this->postJson('/api/auth/verify/otp', $payload);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Otp verified',
        ]);

    // Assert email is now verified
    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();

    // Assert token is generated
    $response->assertJsonStructure([
        'data' => [
            'token',
            'user',
        ]
    ]);

    // OTP must be removed after verification
    expect(Cache::has($cacheKey))->toBeFalse();
});

test('verification fails for wrong otp', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    Cache::put(
        OtpTypeEnum::REGISTRATION->value . "-otp:{$user->id}",
        "123456",
        now()->addMinutes(2)
    );

    $payload = [
        'email' => $user->email,
        'otp'   => "999999"
    ];

    $response = $this->postJson('/api/auth/verify/otp', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('otp');
});
