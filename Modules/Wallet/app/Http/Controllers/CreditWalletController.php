<?php

namespace Modules\Wallet\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Wallet\actions\ApplyTransactionOrchestratorAction;
use Modules\Wallet\app\Http\Requests\CreditWalletRequest;
use Modules\Wallet\dto\WalletTransactionData;

class CreditWalletController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(CreditWalletRequest $request, ApplyTransactionOrchestratorAction $action): JsonResponse
    {
        $data = $action->execute($request->all());
        return successResponse('wallet credited successfully', WalletTransactionData::from($data));
    }
}
