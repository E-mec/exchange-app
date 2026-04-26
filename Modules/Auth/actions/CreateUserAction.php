<?php

namespace Modules\Auth\actions;

use App\Actions\SendOtpAction;
use App\Enums\OtpTypeEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\app\Events\UserRegisteredEvent;
use Modules\Auth\dtos\RequestDto\RegisterUserData;
use Modules\Auth\enums\KycStatusEnum;
use Modules\Auth\enums\UserStatusEnum;
use Modules\Auth\Models\User;

class CreateUserAction
{
    /**
     * @throws ValidationException
     */
    public function handle(RegisterUserData $data)
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'firstname' => $data->firstname,
                'lastname' => $data->lastname,
                'email' => $data->email,
                'username' => $data->username,
                'password' => Hash::make($data->password),
                'phone_number' => $data->phone_number,
                'dial_code' => $data->dial_code,
                'country' => $data->country,
                'pin' => $data->pin,
                'referral_code' => Str::random(8),
                'referred_by' => $data->referred_by,
                'status' => UserStatusEnum::Suspended,
                'kyc_status' => KYCStatusEnum::Pending,
            ]);

            if(isset($data->profile_picture)){
                $user->addMediaFromRequest('profile_picture')->toMediaCollection('profile_pictures');
            }

            app(SendOtpAction::class)->execute($user, OtpTypeEnum::REGISTRATION);

            event(new UserRegisteredEvent($user));

            return $user;
        });

    }
}
