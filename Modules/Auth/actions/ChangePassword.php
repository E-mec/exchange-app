<?php

namespace Modules\Auth\actions;

use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ChangePassword
{
    public function handle(User $user, string $password): void
    {
        $user->update(['password' => Hash::make($password)]);

        if(auth('api')->check()){
            JWTAuth::invalidate(JWTAuth::getToken());
        }
    }
}
