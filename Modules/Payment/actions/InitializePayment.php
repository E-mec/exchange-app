<?php

namespace Modules\Payment\actions;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Payment\app\Events\PaymentInitialized;
use Modules\Payment\app\Resolver\PaymentGatewayResolver;
use Modules\Payment\enums\PaymentStatusEnum;
use Modules\Payment\enums\PaymentProviderEnum;
use Modules\Payment\Models\Payment;

class InitializePayment
{
    public function __construct(
        private readonly PaymentGatewayResolver $resolver
    ) {}

    public function execute(array $data, ?int $userId ): array
    {
        $reference = $data['reference'] ?? 'PAY-' . strtoupper(Str::random(12));
        $provider = PaymentProviderEnum::tryFrom($data['provider'])
            ?? throw new InvalidArgumentException('Unsupported provider');

        $existingPayment = Payment::query()
            ->where('reference', $reference)
            ->first();

        if (
            $existingPayment &&
            $existingPayment->status === PaymentStatusEnum::PENDING &&
            ($existingPayment->meta['checkout_url'] ?? null)
        ) {
            $this->assertReusablePaymentMatches($existingPayment, $provider, $data, $userId);

            return [
                'payment_id' => $existingPayment->id,
                'reference' => $existingPayment->reference,
                'checkout_url' => $existingPayment->meta['checkout_url'],
                'provider' => $existingPayment->provider->value,
                'provider_reference' => $existingPayment->provider_reference,
            ];
        }

        if ($existingPayment && $existingPayment->status !== PaymentStatusEnum::PENDING) {
            throw new InvalidArgumentException('Payment reference has already been resolved.');
        }

        //  Resolve gateway
        $gateway = $this->resolver->resolve($provider);

        $payment = $existingPayment ?? Payment::create([
            'user_id' => $userId,
            'reference' => $reference,
            'provider' => $provider->value,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => PaymentStatusEnum::PENDING->value,
        ]);

        if ($existingPayment) {
            $payment->update([
                'user_id' => $payment->user_id ?? $userId,
                'provider' => $provider->value,
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ]);
        }

        //  Initialize with provider
        $response = $gateway->initialize([
            'reference' => $reference,
            'amount'    => $data['amount'],
            'currency'  => $data['currency'],
            'email'     => $data['email'],
            'meta'      => [
                'payment_id' => $payment->id,
                'user_id'    => $userId,
            ],
        ]);

        //  Persist provider reference
        $payment->update([
            'provider_reference' => $response['provider_reference'] ?? null,
            'meta' => array_merge(
                $response['meta'] ?? [],
                ['checkout_url' => $response['checkout_url']]
            ),
        ]);

        //  Emit event
        event(new PaymentInitialized($payment));

        return [
            'payment_id' => $payment->id,
            'reference' => $payment->reference,
            'checkout_url' => $response['checkout_url'],
            'provider' => $payment->provider->value,
            'provider_reference' => $payment->provider_reference,
        ];
    }

    private function assertReusablePaymentMatches(
        Payment $payment,
        PaymentProviderEnum $provider,
        array $data,
        ?int $userId
    ): void {
        $normalizedRequestedAmount = number_format((float) $data['amount'], 2, '.', '');
        $normalizedExistingAmount = number_format((float) $payment->amount, 2, '.', '');

        if (
            $payment->user_id !== $userId ||
            $payment->provider !== $provider ||
            strtoupper($payment->currency) !== strtoupper($data['currency']) ||
            $normalizedExistingAmount !== $normalizedRequestedAmount
        ) {
            throw new InvalidArgumentException('Existing payment does not match requested payload.');
        }
    }
}
