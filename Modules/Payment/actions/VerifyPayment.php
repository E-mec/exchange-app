<?php

namespace Modules\Payment\actions;

use Exception;
use Illuminate\Http\JsonResponse;
use Modules\Payment\app\Events\PaymentFailed;
use Modules\Payment\app\Events\PaymentSuccessful;
use Modules\Payment\app\Gateways\CoinbaseGateway;
use Modules\Payment\app\Gateways\FlutterwaveGateway;
use Modules\Payment\app\Gateways\PaypalGateway;
use Modules\Payment\app\Gateways\PaystackGateway;
use Modules\Payment\app\Gateways\StripeGateway;
use Modules\Payment\Enums\PaymentProviderEnum;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;

class VerifyPayment
{
    /**
     * @throws Exception
     */
    public function handle($reference, $gateway): JsonResponse
    {
//        if (! $reference) {
//            return response()->json(['error' => 'Missing reference'], 422);
//        }

        $payment = Payment::where('reference', $reference)->first();

        if (! $payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        // already processed → don’t hit Paystack again
        if ($payment->status !== PaymentStatusEnum::PENDING) {
            return response()->json([
                'status' => $payment->status,
                'reference' => $payment->reference,
            ]);
        }


        // call the payment gateway verify method here

        $result = $this->callGatewayVerifier($gateway, $payment);


        if ($result['status'] === PaymentStatusEnum::SUCCESSFUL->value) {

            $payment->update([
                'status' => PaymentStatusEnum::SUCCESSFUL,
                'meta' => array_merge($payment->meta ?? [], $result['meta'] ?? []),
            ]);

            event(new PaymentSuccessful($payment));
        } else {

            $payment->update([
                'status' => PaymentStatusEnum::FAILED,
            ]);

            event(new PaymentFailed($payment));
        }

        return response()->json([
            'status' => $payment->status,
            'reference' => $payment->reference,
        ]);
    }

    /**
     * @throws Exception
     */
    protected function callGatewayVerifier($gateway, $payment)
    {
        return match ($gateway) {
            PaymentProviderEnum::STRIPE->value => app(StripeGateway::class)->verify($payment->provider_reference),
            PaymentProviderEnum::PAYSTACK->value => app(PaystackGateway::class)->verify($payment->reference),
            PaymentProviderEnum::FLUTTERWAVE->value => app(FlutterwaveGateway::class)->verify($payment->reference),
            PaymentProviderEnum::PAYPAL->value => app(PaypalGateway::class)->verify($payment->reference),
            PaymentProviderEnum::COINBASE->value => app(CoinbaseGateway::class)->verify($payment->reference),
            default => throw new Exception("Unknown Gateway"),
        };
    }
}
