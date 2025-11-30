<?php

use App\Enums\OtpTypeEnum;
use Illuminate\Support\Facades\Cache;
use Modules\Auth\actions\VerifyUserAction;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\TestCase;

uses(TestCase::class);

test('can verify user', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $otp = "123456";

    Cache::put(
        OtpTypeEnum::REGISTRATION->value . "-otp:{$user->id}",
        $otp,
        now()->addMinutes(2)
    );

    $action = new VerifyUserAction();
    $result = $action->handle($user->email, $otp);

    expect($result)->toHaveKeys(['token', 'user']);
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});
