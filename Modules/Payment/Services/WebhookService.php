<?php

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use Modules\Payment\app\Events\PaymentFailed;
use Modules\Payment\app\Events\PaymentSuccessful;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;

class WebhookService
{

    public function webhook(array $payload, $provider)
    {
        //  Resolve payment by provider payload
        $payment = $this->resolvePayment($provider, $payload);

        if (!$payment) {
            return response()->json(['ignored' => true], 200);
        }

        // Idempotency check
        if ($payment->status !== PaymentStatusEnum::PENDING) {
            return response()->json(['duplicate' => true], 200);
        }

        // Determine final status
        $status = $this->resolveStatus($provider, $payload);

        DB::transaction(function () use ($payment, $status, $payload) {
            // Update atomically
            $payment->update([
                'status' => $status,
                'meta' => array_merge($payment->meta ?? [], [
                    'webhook' => $payload,
                ]),
            ]);


            match ($status) {
                PaymentStatusEnum::SUCCESSFUL->value =>
                event(new PaymentSuccessful($payment)),

                PaymentStatusEnum::FAILED->value =>
                event(new PaymentFailed($payment)),

                default => null,
            };
        });
    }

    protected function resolvePayment(string $provider, array $payload): ?Payment
    {
        return match ($provider) {
            'paystack' => Payment::where('provider_reference', $payload['data']['reference'] ?? null)->first(),
            'flutterwave' => Payment::where('provider_reference', $payload['data']['tx_ref'] ?? null)->first(),
            'stripe' => Payment::where('provider_reference', $payload['data']['object']['id'] ?? null)->first(),
            'paypal' => Payment::where('provider_reference', $payload['resource']['id'] ?? null)->first(),
            'coinbase' => Payment::where('provider_reference', $payload['event']['data']['code'] ?? null)->first(),
            default => null,
        };
    }

    protected function resolveStatus(string $provider, array $payload): string
    {
        return match ($provider) {
            'paystack' =>
            $payload['data']['status'] === 'success'
                ? PaymentStatusEnum::SUCCESSFUL->value
                : PaymentStatusEnum::FAILED->value,

            'flutterwave' =>
            $payload['data']['status'] === 'successful'
                ? PaymentStatusEnum::SUCCESSFUL->value
                : PaymentStatusEnum::FAILED->value,

            'stripe' =>
            $payload['data']['object']['payment_status'] === 'paid'
                ? PaymentStatusEnum::SUCCESSFUL->value
                : PaymentStatusEnum::FAILED->value,

            'paypal' =>
            $payload['resource']['status'] === 'COMPLETED'
                ? PaymentStatusEnum::SUCCESSFUL->value
                : PaymentStatusEnum::FAILED->value,

            'coinbase' =>
            $payload['event']['type'] === 'charge:confirmed'
                ? PaymentStatusEnum::SUCCESSFUL->value
                : PaymentStatusEnum::PENDING->value,

            default => PaymentStatusEnum::FAILED->value,
        };
    }
}
