<?php

use Modules\Payment\Enums\PaymentProviderEnum;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */
    'default_currency' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [

        PaymentProviderEnum::PAYSTACK->value => [
            'secret' => env('PAYSTACK_SECRET_KEY'),
            'public' => env('PAYSTACK_PUBLIC_KEY'),
            'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
        ],

        PaymentProviderEnum::FLUTTERWAVE->value => [
            'secret' => env('FLUTTERWAVE_SECRET_KEY'),
            'public' => env('FLUTTERWAVE_PUBLIC_KEY'),
            'webhook_secret' => env('FLUTTERWAVE_WEBHOOK_SECRET'),
            'redirect_url' => env('APP_URL').'/api/payments/verify/flutterwave',

        ],

        PaymentProviderEnum::STRIPE->value => [
            'secret' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'success_url' => env('APP_URL').'/api/payments/verify/stripe',
            'failure_url' => env('APP_URL').'/api/payments/verify/stripe',
        ],

        PaymentProviderEnum::PAYPAL->value => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_SECRET'),
            'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
            'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox | live
        ],

        PaymentProviderEnum::COINBASE->value => [
            'api_key' => env('COINBASE_API_KEY'),
            'webhook_secret' => env('COINBASE_WEBHOOK_SECRET'),
        ],

//        PaymentProviderEnum::BINANCE->value => [
//            'api_key' => env('BINANCE_API_KEY'),
//            'secret' => env('BINANCE_SECRET_KEY'),
//            'webhook_secret' => env('BINANCE_WEBHOOK_SECRET'),
//        ],

        'payout_provider' => [
            'ngn' => PaymentProviderEnum::PAYSTACK->value,
            'usd' => PaymentProviderEnum::STRIPE->value,
            'eur' => PaymentProviderEnum::FLUTTERWAVE->value,
        ],
    ],
];
