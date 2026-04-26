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
            $request->amount,
            $request->currency,
            $request->deposit_channel,
            $request->idempotency_key,
        );

        return successResponse('deposit initiated', DepositData::from($data));
    }
}
