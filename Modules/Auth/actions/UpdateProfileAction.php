<?php

namespace Modules\Auth\actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class UpdateProfileAction
{
    public function handle(array $data): ?Authenticatable
    {
        $user = Auth::user();

        $user->update($data);

        return $user->load('country');
    }
}
