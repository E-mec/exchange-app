<?php

namespace Modules\Auth\actions;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SetPinAction
{
    public function handle(array $data)
    {
        $user = auth()->user();

        if(!Hash::check($data['password'], $user->password))
        {
            throw ValidationException::withMessages([
                'password' => 'Incorrect Password.'
            ]);
        }

        $user->pin = Hash::make($data['pin']);
        $user->save();

        return $user->fresh();
    }
}
