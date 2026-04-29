<?php

namespace Modules\Payment\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payment\actions\InitializePayment;
use Modules\Payment\actions\VerifyPayment;
use Modules\Payment\app\Events\PaymentFailed;
use Modules\Payment\app\Events\PaymentSuccessful;
use Modules\Payment\app\Gateways\PaystackGateway;
use Modules\Payment\app\Http\Requests\InitializePaymentRequest;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{

    public function initialize(
        InitializePaymentRequest $request,
        InitializePayment $action
    ): JsonResponse
    {
        return successResponse(
            'payment initialized',
            $action->execute(
                $request->validated(),
                auth()->id()
            )
        );
    }

    /**
     * @throws Exception
     */
    public function verify(Request $request,string $gateway): JsonResponse|Response
    {
        $reference = $request->query('reference');

        return app(VerifyPayment::class)->handle($reference, $gateway);

    }
}
