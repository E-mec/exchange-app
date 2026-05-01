<?php

namespace Modules\Auth\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Auth\actions\UpdateProfileAction;
use Modules\Auth\actions\UploadProfilePictureAction;
use Modules\Auth\app\Http\Requests\ProfilePictureRequest;
use Modules\Auth\app\Http\Requests\UpdateProfileRequest;
use Modules\Auth\dtos\ResponseDto\UserData;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request, UpdateProfileAction $action)
    {
        return DB::transaction(function () use ($request, $action) {
            $result = $action->handle($request->all());
            return successResponse('Profile updated successfully', UserData::from($result));
        });
    }

    public function saveProfilePicture(ProfilePictureRequest $request, UploadProfilePictureAction $action)
    {
        return DB::transaction(function () use ($request, $action) {
            $user = auth('api')->user();
            $data = $action->handle($request->file('profile_picture'), $user);

            return successResponse('Profile picture updated successfully', UserData::from($data));
        });
    }
}
