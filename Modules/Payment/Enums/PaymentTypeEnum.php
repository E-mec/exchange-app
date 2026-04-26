<?php

namespace Modules\Payment\Enums;

enum PaymentTypeEnum: string
{
    case DEPOSIT = 'deposit';
    case PAYOUT  = 'payout';
}
