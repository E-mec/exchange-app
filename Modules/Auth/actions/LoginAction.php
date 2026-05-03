<?php

namespace Modules\Auth\actions;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;

class LoginAction
{
    /**
     * @throws ValidationException
     */
    public function handle($email, $password)
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'login' => 'Invalid login credentials.',
            ]);
        }

        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Invalid login credentials.',
            ]);
        }

        if (! $user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => 'Please verify your email before logging in.',
            ]);
        }

        return $user->load('country');

    }
}
