<?php

return [
    'name' => 'BillPayment',

    'providers' => [
        'vtpass' => [
            'env'        => env('VTPASS_ENV', 'sandbox'),
            'api_key'    => env('VTPASS_API_KEY'),
            'public_key' => env('VTPASS_PUBLIC_KEY'),
            'secret_key' => env('VTPASS_SECRET_KEY'),
            'base_url'   => env('VTPASS_ENV') === 'production'
                ? 'https://vtpass.com/api'
                : 'https://sandbox.vtpass.com/api',
        ],
        'buypower' => [
            'token'    => env('BUYPOWER_TOKEN'),
            'base_url' => env('BUYPOWER_ENV') === 'production'
                ? 'https://api.buypower.ng/v2'
                : 'https://idev.buypower.ng/v2',
        ],
        'baxi' => [
            'api_key'  => env('BAXI_API_KEY'),
            'base_url' => env('BAXI_ENV') === 'production'
                ? 'https://payments.baxipay.com.ng/api'
                : 'https://baxi-sandbox.com/api',
        ],
    ],
];
