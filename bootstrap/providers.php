<?php

return [
    App\Providers\AppServiceProvider::class,
    Modules\Auth\Providers\AuthServiceProvider::class,
    Modules\Auth\Providers\RouteServiceProvider::class,
    Modules\Wallet\Providers\WalletServiceProvider::class,
    Modules\Wallet\Providers\RouteServiceProvider::class,
    Modules\Wallet\Providers\EventServiceProvider::class,
    Modules\Payment\Providers\PaymentServiceProvider::class,
    Modules\BillPayment\Providers\BillPaymentServiceProvider::class,
];
