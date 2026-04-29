<?php

namespace Modules\Wallet\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Wallet\actions\InitiateDepositAction;
use Modules\Wallet\app\Http\Requests\InitiateDepositRequest;
use Modules\Wallet\dto\DepositData;

class DepositController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(InitiateDepositRequest $request, InitiateDepositAction $action): JsonResponse
    {
        $data = $action->handle(
            $request->input('amount'),
            $request->input('currency'),
            $request->input('deposit_channel'),
            $request->input('idempotency_key'),
        );

        return successResponse('deposit initiated', DepositData::from($data));
    }
}
