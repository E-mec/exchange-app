<?php

namespace Modules\Payment\app\Resolver;

use InvalidArgumentException;
use Modules\Payment\app\Gateways\CoinbaseGateway;
use Modules\Payment\app\Gateways\FlutterwaveGateway;
use Modules\Payment\app\Gateways\PaypalGateway;
use Modules\Payment\app\Gateways\PaystackGateway;
use Modules\Payment\app\Gateways\StripeGateway;
use Modules\Payment\app\Interfaces\PaymentGatewayInterface;
use Modules\Payment\Enums\PaymentProviderEnum;

class PaymentGatewayResolver
{
    public function resolve(
        PaymentProviderEnum $provider
    ): PaymentGatewayInterface {
        return match ($provider) {
            PaymentProviderEnum::PAYSTACK => app(PaystackGateway::class),
            PaymentProviderEnum::STRIPE   => app(StripeGateway::class),
            PaymentProviderEnum::PAYPAL   => app(PaypalGateway::class),
            PaymentProviderEnum::FLUTTERWAVE  => app(FlutterwaveGateway::class),
            PaymentProviderEnum::COINBASE   => app(CoinbaseGateway::class),

            default => throw new InvalidArgumentException(
                'Unsupported payment provider'
            ),
        };
    }
}
