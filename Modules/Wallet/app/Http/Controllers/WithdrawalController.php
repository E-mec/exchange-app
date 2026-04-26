<?php

namespace Modules\Wallet\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Wallet\actions\InitiateWithdrawalAction;
use Modules\Wallet\app\Http\Requests\WithdrawalRequest;

class WithdrawalController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(WithdrawalRequest $request, InitiateWithdrawalAction $action): JsonResponse
    {
        $withdrawal = $action->execute($request->validated());

        return successResponse('Withdrawal successfully initiated', $withdrawal);

    }



}
