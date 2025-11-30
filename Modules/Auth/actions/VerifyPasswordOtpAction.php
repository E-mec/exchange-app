<?php

namespace Modules\Auth\actions;

use App\Actions\VerifyOtpAction;
use App\Enums\OtpTypeEnum;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;

class VerifyPasswordOtpAction
{
    /**
     * @throws ValidationException
     */
    public function execute(string $email, string $otp)
    {
        $user = User::where('email', $email)->first();

        if(!$user){
            throw ValidationException::withMessages([
                'user' => 'User not found'
            ]);
        }

        app(VerifyOtpAction::class)->execute($user, $otp, OtpTypeEnum::PASSWORD);







    }
}
