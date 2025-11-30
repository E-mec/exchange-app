<?php

namespace Modules\Auth\actions;

use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;

class ResetPasswordAction
{
    public function execute(string $email, string $password)
    {
        $user = User::where('email', $email)->first();

        if(!$user){
            throw new CustomException('User not found');
        }

        $user->update([
            'password' => Hash::make($password)
        ]);
    }
}
