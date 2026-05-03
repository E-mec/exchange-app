<?php

namespace Modules\Auth\actions;

use App\Actions\VerifyOtpAction;
use App\Enums\OtpTypeEnum;
use Illuminate\Validation\ValidationException;
use Modules\Auth\dtos\ResponseDto\UserData;
use Modules\Auth\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class VerifyUserAction
{
    /**
     * @throws ValidationException
     */
    public function handle(string $email, string $otp): array
    {
        $user = User::where('email', $email)->first();

        if(!$user){
            throw ValidationException::withMessages([
               'user' => 'User not found'
            ]);
        }

//        if(!$user){
//            return false;
//        }

        if ($user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => 'Email is already verified.',
            ]);
        }

         app(VerifyOtpAction::class)->execute($user, $otp, OtpTypeEnum::REGISTRATION);

            $user->update([
                'email_verified_at' => now(),
            ]);

        return [
            'token' => JWTAuth::fromUser($user),
            'user' => UserData::from($user->refresh()->load('country'))
        ];

    }
}
