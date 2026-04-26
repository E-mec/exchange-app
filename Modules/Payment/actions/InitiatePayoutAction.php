<?php

namespace Modules\Payment\actions;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Payment\app\Resolver\PaymentGatewayResolver;
use Modules\Payment\Enums\PaymentProviderEnum;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Enums\PaymentTypeEnum;
use Modules\Payment\Models\Payment;

readonly class InitiatePayoutAction
{
    public function __construct(
        private PaymentGatewayResolver $resolver
    ) {}

    /**
     * Initiate a payout through the resolved payment gateway.
     *
     * @param array $data {
     *     reference: string,
     *     amount: string|float,
     *     currency: string,
     *     destination: array,
     *     provider: string (from PaymentProviderEnum),
     *     meta: array (optional),
     * }
     * @param int|null $userId
     * @return Payment
     */
    public function execute(array $data, ?int $userId): Payment
    {
        $reference = $data['reference'] ?? 'PO-' . strtoupper(Str::random(12));
        $provider  = PaymentProviderEnum::tryFrom($data['provider'])
            ?? throw new InvalidArgumentException('Unsupported payout provider');

        // Idempotency: check for existing payout payment with same reference
        $existing = Payment::query()
            ->where('reference', $reference)
            ->where('type', PaymentTypeEnum::PAYOUT)
            ->first();

        if ($existing) {
            // Already initiated — return as-is (webhook will finalize)
            return $existing;
        }

        // Resolve gateway
        $gateway = $this->resolver->resolve($provider);

        // Create payment record for the payout
        $payment = Payment::create([
            'user_id'   => $userId,
            'type'      => PaymentTypeEnum::PAYOUT->value,
            'reference' => $reference,
            'provider'  => $provider->value,
            'amount'    => $data['amount'],
            'currency'  => $data['currency'],
            'status'    => PaymentStatusEnum::PENDING->value,
            'meta'      => [
                'destination' => $data['destination'] ?? [],
            ],
        ]);

        // Call the gateway payout
        $response = $gateway->payout([
            'amount'      => $data['amount'],
            'currency'    => $data['currency'],
            'destination' => $data['destination'] ?? [],
            'reference'   => $reference,
            'meta'        => $data['meta'] ?? [],
        ]);

        // Update payment with provider response
        $payment->update([
            'provider_reference' => $response['provider_reference'] ?? null,
            'meta' => array_merge($payment->meta ?? [], [
                'provider_response' => $response,
            ]),
        ]);

        return $payment->refresh();
    }
}
