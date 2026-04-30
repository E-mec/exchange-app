<?php

namespace App\Actions;

use App\Enums\OtpTypeEnum;
use Illuminate\Support\Facades\Cache;
use Modules\Auth\Models\User;
use Modules\Auth\app\Notifications\OtpNotification;

class SendOtpAction
{
    protected int $otpLength = 6;
    protected int $ttlMinutes = 5;

    public function execute(User $user,OtpTypeEnum $key ): void
    {
        $otp = $this->generateOtp();

        $key = $key->value;

        // Store OTP in cache (or database if needed)
        Cache::put($this->getCacheKey($user, $key), $otp, now()->addMinutes($this->ttlMinutes));

        // Send OTP via Laravel Notification (Email + SMS)
        $user->notify(new OtpNotification($otp));
    }

    protected function generateOtp(): string
    {
        return str_pad(random_int(0, (10 ** $this->otpLength) - 1), $this->otpLength, '0', STR_PAD_LEFT);
    }

    protected function getCacheKey(User $user, $key ): string
    {
        return "$key-otp:{$user->id}";
    }
}
