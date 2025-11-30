<?php

namespace Modules\Auth\actions;

use App\Actions\SendOtpAction;
use App\Enums\OtpTypeEnum;
use App\Exceptions\CustomException;
use Modules\Auth\Models\User;

class PasswordResetOtpAction
{
    public function execute(string $email): void
    {
        $user = User::where('email', $email)->first();

        if(!$user){
            throw new CustomException('User not found');
        }

        app(SendOtpAction::class)->execute($user, OtpTypeEnum::PASSWORD);

    }
}
