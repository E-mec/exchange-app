<?php

namespace App\Actions;

use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VerifyPin
{
    /**
     * @throws ValidationException
     * @throws CustomException
     */
    public function handle(string $pin): void
    {
        $user = auth('api')->user();

        if(!$user->pin)
        {
            throw new CustomException("Please set pin");
        }

        if (!Hash::check($pin, $user->pin)) {
            throw ValidationException::withMessages([
                'pin' => 'Invalid PIN.'
            ]);
        }
    }
}
