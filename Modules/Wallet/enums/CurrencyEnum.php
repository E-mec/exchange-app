<?php

namespace Modules\Wallet\enums;

enum CurrencyEnum: string
{
    case NGN = 'NGN';
    case USD = 'USD';
    case EUR = 'EUR';
    case BTC = 'BTC';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
