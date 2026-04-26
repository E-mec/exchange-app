<?php

namespace Modules\Wallet\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Wallet\actions\InitiateWithdrawalAction;
use Modules\Wallet\app\Http\Requests\WithdrawalRequest;
use Modules\Wallet\Models\Withdrawal;

class WithdrawalController extends Controller
{

    public function index(): JsonResponse
    {
        $withdrawals = Withdrawal::where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return successResponse('withdrawals', $withdrawals);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WithdrawalRequest $request, InitiateWithdrawalAction $action): JsonResponse
    {
        $withdrawal = $action->execute($request->validated());

        return successResponse('Withdrawal successfully initiated', $withdrawal);

    }



}
