<?php

namespace Modules\Auth\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Auth\actions\LoginAction;
use Modules\Auth\app\Http\Requests\LoginRequest;
use Modules\Auth\dtos\ResponseDto\UserData;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller
{

    /**
     * Show the form for creating a new resource.
     */
    public function login(LoginRequest $request, LoginAction $action)
    {
        return DB::transaction(function () use ($request, $action) {
            $user = $action->handle($request->email, $request->password);
            $token = JWTAuth::fromUser($user);

            $data = [
              'token' => $token,
              'user' => UserData::from($user)
            ];

            return successResponse('Logged in successfully.', $data);
        });
    }

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return successResponse('Logged out successfully.');
    }

    public function me(): JsonResponse
    {
        $user = auth('api')->user();

        return successResponse('Authenticated user', [
            'user' => UserData::from($user)
        ]);
    }

    public function refresh(): JsonResponse
    {
        $newToken = JWTAuth::refresh(JWTAuth::getToken());

        return successResponse('new token', $newToken);
    }


}
