<?php

namespace Modules\Payment\actions;

use Illuminate\Support\Str;
use Modules\Payment\app\Events\PaymentInitialized;
use Modules\Payment\app\Resolver\PaymentGatewayResolver;
use Modules\Payment\enums\PaymentStatusEnum;
use Modules\Payment\enums\PaymentProviderEnum;
use Modules\Payment\app\Interfaces\PaymentGatewayInterface;
use Modules\Payment\Models\Payment;

class InitializePayment
{
    public function __construct(
        private readonly PaymentGatewayResolver $resolver
    ) {}

    public function execute(array $data, int $userId): array
    {
        $reference = Str::uuid()->toString();
        $provider  = PaymentProviderEnum::from($data['provider']);

        // 1️⃣ Resolve gateway
        $gateway = $this->resolver->resolve($provider);

        // 2️⃣ Create pending payment
        $payment = Payment::create([
            'user_id'   => $userId,
            'reference' => $reference,
            'provider'  => $provider->value,
            'amount'    => $data['amount'],
            'currency'  => $data['currency'],
            'status'    => PaymentStatusEnum::PENDING->value,
        ]);

        // 3️⃣ Initialize with provider
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

        // 4️⃣ Persist provider reference
        $payment->update([
            'provider_reference' => $response['provider_reference'] ?? null,
            'meta'               => $response['meta'] ?? null,
        ]);

        // 5️⃣ Emit event
        event(new PaymentInitialized($payment));

        return [
            'reference'    => $payment->reference,
            'checkout_url' => $response['checkout_url'],
            'provider'     => $payment->provider,
        ];
    }
}
