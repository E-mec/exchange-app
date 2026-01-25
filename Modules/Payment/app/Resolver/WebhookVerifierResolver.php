<?php

namespace Modules\Payment\app\Resolver;

use InvalidArgumentException;
use Modules\Payment\Enums\PaymentProviderEnum;
use Modules\Payment\app\Interfaces\WebhookVerifierInterface;
use Modules\Payment\Webhooks\StripeVerifier;
use Modules\Payment\Webhooks\PaystackVerifier;
use Modules\Payment\Webhooks\CoinbaseVerifier;
use Modules\Payment\Webhooks\FlutterwaveVerifier;

class WebhookVerifierResolver
{
    public function resolve(
        PaymentProviderEnum $provider
    ): WebhookVerifierInterface {
        return match ($provider) {
            PaymentProviderEnum::STRIPE      => app(StripeVerifier::class),
            PaymentProviderEnum::PAYSTACK    => app(PaystackVerifier::class),
            PaymentProviderEnum::COINBASE    => app(CoinbaseVerifier::class),
            PaymentProviderEnum::FLUTTERWAVE => app(FlutterwaveVerifier::class),

            default => throw new InvalidArgumentException(
                'Unsupported webhook provider'
            ),
        };
    }
}
