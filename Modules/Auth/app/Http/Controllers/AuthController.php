<?php

namespace Modules\Auth\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Auth\actions\CreateUserAction;
use Modules\Auth\actions\VerifyUserAction;
use Modules\Auth\app\Http\Requests\RegisterRequest;
use Modules\Auth\app\Http\Requests\VerifyUserRequest;
use Modules\Auth\dtos\RequestDto\RegisterUserData;
use Modules\Auth\dtos\ResponseDto\UserData;

class AuthController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function register(RegisterRequest $request, CreateUserAction $action): JsonResponse
    {

        $dto = RegisterUserData::fromArray($request->validated());
        $user =  DB::transaction(fn() => $action->handle($dto));

        $data = [
            'user' => UserData::from($user),
        ];

        return successResponse(
            message: 'Registration successful',
            data: $data
        );
    }

    public function verify(VerifyUserRequest $request, VerifyUserAction $action): JsonResponse
    {
        return DB::transaction(function () use ($request, $action) {
            $data = $action->handle($request->email, $request->otp);

            return successResponse('Otp verified', $data);
        });
    }

}
