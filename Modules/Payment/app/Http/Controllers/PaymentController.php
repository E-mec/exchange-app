<?php

namespace Modules\Payment\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Payment\actions\InitializePayment;
use Modules\Payment\app\Http\Requests\InitializePaymentRequest;

class PaymentController extends Controller
{


    public function initialize(
        InitializePaymentRequest $request,
        InitializePayment $action
    ) {
        return successResponse(
            'payment initialized',
            $action->execute(
                $request->validated(),
                auth()->id()
            )
        );
    }
}
