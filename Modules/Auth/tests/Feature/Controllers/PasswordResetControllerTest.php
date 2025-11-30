<?php

use Illuminate\Support\Facades\Hash;
use Modules\Auth\actions\PasswordResetOtpAction;
use Modules\Auth\actions\ResetPasswordAction;
use Modules\Auth\actions\VerifyPasswordOtpAction;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\TestCase;

beforeEach(function () {
    $this->user = User::factory()->create();
});

uses(TestCase::class);

test('can request password reset otp', function () {
    // Mock the OTP sending action
    $this->mock(PasswordResetOtpAction::class, function ($mock) {
        $mock->shouldReceive('execute')->once()->andReturnNull();
    });

    $response = $this->postJson('api/auth/password/otp', [
        'email' => $this->user->email,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'An otp has been sent to your email',
        ]);
});

test('can verify password reset otp', function () {
    // Mock the OTP verification action
    $this->mock(VerifyPasswordOtpAction::class, function ($mock) {
        $mock->shouldReceive('execute')->once()->andReturnNull();
    });

    $response = $this->postJson('api/auth/password/otp/verify', [
        'email' => $this->user->email,
        'otp' => '123456', // dummy OTP
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Otp has been verified',
        ]);
});

test('can reset password', function () {
    // Mock the reset password action
    $this->mock(ResetPasswordAction::class, function ($mock) {
        $mock->shouldReceive('execute')->once()->andReturnUsing(function ($email, $password) {
            $user = User::where('email', $email)->first();
            $user->update(['password' => Hash::make($password)]);
        });
    });

    $newPassword = 'newpassword123';

    $response = $this->postJson('api/auth/password/reset', [
        'email' => $this->user->email,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Password has been reset',
        ]);

    // Ensure password is updated in DB
    $this->assertTrue(Hash::check($newPassword, $this->user->fresh()->password));
});
