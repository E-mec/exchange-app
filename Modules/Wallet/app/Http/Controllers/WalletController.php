<?php

namespace Modules\Wallet\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\actions\GetUserWalletByCurrencyAction;
use Modules\Wallet\actions\GetUserWalletsAction;
use Modules\Wallet\actions\InitiateDepositAction;
use Modules\Wallet\app\Http\Requests\InitiateDepositRequest;
use Modules\Wallet\dto\DepositData;
use Modules\Wallet\enums\CurrencyEnum;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(GetUserWalletsAction $action): JsonResponse
    {
        return successResponse('user wallets',
            $action->handle(auth('api')->id())
        );
    }

    /**
     * Show the specified resource.
     */
    public function show(
        string $currency,
        GetUserWalletByCurrencyAction $action
    ): JsonResponse
    {
        $currencyEnum = CurrencyEnum::tryFrom(strtoupper($currency));

        if (! $currencyEnum) {
            throw new NotFoundHttpException('Wallet currency not supported.');
        }

        return successResponse('user wallets',
            $action->execute(
                auth('api')->id(),
                $currencyEnum
            ));
    }


}
