<?php

namespace Modules\Wallet\enums;

enum TransactionTypeEnum: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
    case CHECKOUT = 'checkout';
    case REFUND = 'refund';
    case PAYOUT = 'payout';
    case RESERVE = 'reserve';
    case RESERVE_RELEASE = 'release';
    case FEE = 'fee';
    case ADJUSTMENT = 'adjustment';
}
