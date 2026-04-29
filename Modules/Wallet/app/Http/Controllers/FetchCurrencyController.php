<?php

namespace Modules\Wallet\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Wallet\enums\CurrencyEnum;

class FetchCurrencyController extends Controller
{
    public function __invoke()
    {
        $currencies = CurrencyEnum::values();

        return successResponse('currencies', $currencies);
    }
}
