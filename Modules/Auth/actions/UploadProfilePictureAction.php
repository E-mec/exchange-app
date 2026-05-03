<?php

namespace Modules\Auth\actions;

class UploadProfilePictureAction
{
    public function handle($picture, $user)
    {
        $user->clearMediaCollection('profile_picture')
            ->addMedia($picture)
            ->toMediaCollection('profile_picture');
        return $user->fresh()->load('country');
    }
}
