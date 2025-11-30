<?php

namespace App\Actions;

use App\Enums\OtpTypeEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class VerifyOtpAction
{
    /**
     * @throws ValidationException
     */
    public function execute($user , $otp, OtpTypeEnum $key): true
    {
        $key = $key->value;
        $cacheKey = "$key-otp:{$user->id}";
        $storedOtp = Cache::get($cacheKey);

        if (! $storedOtp) {
            throw ValidationException::withMessages([
                'otp' => 'OTP expired or not found.',
            ]);
        }

        if ($storedOtp !== $otp) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid OTP provided.',
            ]);
        }

        // OTP is correct → consume it
        Cache::forget($cacheKey);

        return true;


    }
}
