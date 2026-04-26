<?php

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use Modules\Payment\app\Events\PaymentFailed;
use Modules\Payment\app\Events\PaymentSuccessful;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Enums\PaymentTypeEnum;
use Modules\Payment\Models\Payment;

class WebhookService
{

    public function webhook(array $payload, $provider)
    {
        // Determine if this is a payout (transfer) webhook or a charge (deposit) webhook
        $isPayout = $this->isPayoutWebhook($provider, $payload);

        // Resolve payment by provider payload
        $payment = $isPayout
            ? $this->resolvePayoutPayment($provider, $payload)
            : $this->resolvePayment($provider, $payload);

        if (!$payment) {
            return response()->json(['ignored' => true], 200);
        }

        // Idempotency check
        if ($payment->status !== PaymentStatusEnum::PENDING) {
            return response()->json(['duplicate' => true], 200);
        }

        // Determine final status
        $status = $isPayout
            ? $this->resolvePayoutStatus($provider, $payload)
            : $this->resolveStatus($provider, $payload);

        DB::transaction(function () use ($payment, $status, $payload) {
            // Update atomically
            $payment->update([
                'status' => $status,
                'meta' => array_merge($payment->meta ?? [], [
                    'webhook' => $payload,
                ]),
            ]);

            // Refresh to get the casted enum value
            $payment->refresh();

            match ($payment->status) {
                PaymentStatusEnum::SUCCESSFUL => event(new PaymentSuccessful($payment)),
                PaymentStatusEnum::FAILED     => event(new PaymentFailed($payment)),
                default                       => null,
            };
        });
    }

    /**
     * Detect whether the incoming webhook is for a payout/transfer or a charge/deposit.
     */
    protected function isPayoutWebhook(string $provider, array $payload): bool
    {
        return match ($provider) {
            'paystack'    => str_starts_with($payload['event'] ?? '', 'transfer.'),
            'flutterwave' => str_starts_with($payload['event'] ?? '', 'transfer.'),
            'stripe'      => str_starts_with($payload['type'] ?? '', 'payout.'),
            'paypal'      => str_contains($payload['event_type'] ?? '', 'PAYOUTS'),
            'coinbase'    => false, // Coinbase doesn't distinguish — handled by payment type
            default       => false,
        };
    }

    /**
     * Resolve a deposit (charge) payment from webhook payload.
     */
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

    /**
     * Resolve a payout (transfer) payment from webhook payload.
     */
    protected function resolvePayoutPayment(string $provider, array $payload): ?Payment
    {
        return match ($provider) {
            'paystack' => Payment::where('type', PaymentTypeEnum::PAYOUT)
                ->where('provider_reference', $payload['data']['transfer_code'] ?? $payload['data']['reference'] ?? null)
                ->first(),
            'flutterwave' => Payment::where('type', PaymentTypeEnum::PAYOUT)
                ->where('provider_reference', (string) ($payload['data']['id'] ?? null))
                ->first(),
            'stripe' => Payment::where('type', PaymentTypeEnum::PAYOUT)
                ->where('provider_reference', $payload['data']['object']['id'] ?? null)
                ->first(),
            'paypal' => Payment::where('type', PaymentTypeEnum::PAYOUT)
                ->where('provider_reference', $payload['resource']['batch_header']['payout_batch_id'] ?? null)
                ->first(),
            default => null,
        };
    }

    /**
     * Resolve status for deposit (charge) webhooks.
     */
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

    /**
     * Resolve status for payout (transfer) webhooks.
     */
    protected function resolvePayoutStatus(string $provider, array $payload): string
    {
        return match ($provider) {
            'paystack' => match ($payload['event'] ?? '') {
                'transfer.success'  => PaymentStatusEnum::SUCCESSFUL->value,
                'transfer.failed',
                'transfer.reversed' => PaymentStatusEnum::FAILED->value,
                default             => PaymentStatusEnum::PENDING->value,
            },

            'flutterwave' => match ($payload['event'] ?? '') {
                'transfer.completed' => PaymentStatusEnum::SUCCESSFUL->value,
                'transfer.failed'    => PaymentStatusEnum::FAILED->value,
                default              => PaymentStatusEnum::PENDING->value,
            },

            'stripe' => match ($payload['type'] ?? '') {
                'payout.paid'   => PaymentStatusEnum::SUCCESSFUL->value,
                'payout.failed' => PaymentStatusEnum::FAILED->value,
                default         => PaymentStatusEnum::PENDING->value,
            },

            'paypal' => match (true) {
                str_contains($payload['event_type'] ?? '', 'SUCCESS') => PaymentStatusEnum::SUCCESSFUL->value,
                str_contains($payload['event_type'] ?? '', 'DENIED'),
                str_contains($payload['event_type'] ?? '', 'FAILED')  => PaymentStatusEnum::FAILED->value,
                default => PaymentStatusEnum::PENDING->value,
            },

            default => PaymentStatusEnum::FAILED->value,
        };
    }
}
