<?php

namespace Modules\Payment\Enums;

enum PaymentProviderEnum: string
{
    case PAYSTACK = 'paystack';
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';
    case FLUTTERWAVE = 'flutterwave';
    case COINBASE = 'coinbase';
    case BINANCE = 'binance';
}

