<?php

namespace Modules\Auth\Http\Controllers;

use App\Actions\SendOtpAction;
use App\Enums\OtpTypeEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\actions\ChangePassword;
use Modules\Auth\Http\Requests\ChangePasswordRequest;
use Modules\Auth\Http\Requests\ForgotPasswordRequest;
use Modules\Auth\Models\User;

class ForgotPasswordController extends Controller
{

    public function requestChange(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        app(SendOtpAction::class)->execute($user, OtpTypeEnum::PASSWORD);

        return successResponse('OTP has been sent to your email');
    }

    public function change(ChangePasswordRequest $request): JsonResponse
    {
        $user = User::whereEmail($request->input('email'))->findOrFail();

        app(ChangePassword::class)->handle($user, $request->input('password'));

        return successResponse('Password has been changed');
    }
}
