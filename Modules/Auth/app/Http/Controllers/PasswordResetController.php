<?php

namespace Modules\Auth\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Auth\actions\PasswordResetOtpAction;
use Modules\Auth\actions\ResetPasswordAction;
use Modules\Auth\actions\VerifyPasswordOtpAction;
use Modules\Auth\app\Http\Requests\PasswordResetRequest;
use Modules\Auth\app\Http\Requests\ResetPasswordRequest;
use Modules\Auth\app\Http\Requests\VerifyPasswordResetOtpRequest;

class PasswordResetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function requestOtp(PasswordResetRequest $request, PasswordResetOtpAction $action): JsonResponse
    {
        return DB::transaction(function () use ($request, $action) {
            $action->execute($request->input('email'));
            return successResponse('An otp has been sent to your email');
        });

    }

    /**
     * Show the form for creating a new resource.
     */
    public function verifyOtp(VerifyPasswordResetOtpRequest $request, VerifyPasswordOtpAction $action)
    {
        return DB::transaction(function () use ($request, $action) {
            $action->execute($request->email, $request->otp);
            return successResponse('Otp has been verified');
        });
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        return DB::transaction(function () use ($request, $action) {
           $action->execute($request->email, $request->password);
           return successResponse('Password has been reset');
        });
    }

}
