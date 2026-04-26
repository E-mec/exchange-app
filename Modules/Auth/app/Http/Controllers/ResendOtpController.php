<?php

namespace Modules\Auth\app\Http\Controllers;

use App\Actions\SendOtpAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;

class ResendOtpController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
            return DB::transaction(function () use ($request) {
                $user = User::where('email', $request->input('email'))->first();

                if(!$user){
                    throw ValidationException::withMessages([
                        'user' => 'User not found'
                    ]);
                }

                app(SendOtpAction::class)->execute($user, $request->input('otp_type'));

                return successResponse('OTP has been sent to your email');
            });
    }
}
