<?php

namespace Modules\Payment\actions;

use Illuminate\Support\Facades\Auth;
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
        $reference = $data['reference'];
        $provider = PaymentProviderEnum::tryFrom($data['provider'])
            ?? throw new InvalidArgumentException('Unsupported provider');

        //  Resolve gateway
        $gateway = $this->resolver->resolve($provider);

        //  Create pending payment
        $payment = Payment::create([
            'user_id'   => $userId,
            'reference' => $reference,
            'provider'  => $provider->value,
            'amount'    => $data['amount'],
            'currency'  => $data['currency'],
            'status'    => PaymentStatusEnum::PENDING->value,
        ]);

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
            'reference'    => $payment->reference,
            'checkout_url' => $response['checkout_url'],
            'provider'     => $payment->provider,
        ];
    }
}
