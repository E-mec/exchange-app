<?php

namespace Modules\Wallet\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Wallet\actions\TransferFundsAction;
use Modules\Wallet\app\Http\Requests\TransferRequest;

class WalletTransferController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(TransferRequest $request, TransferFundsAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        return successResponse('transfer successful', $result);
    }
}
