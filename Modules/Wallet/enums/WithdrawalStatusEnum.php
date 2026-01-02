<?php

namespace Modules\Wallet\enums;

enum WithdrawalStatusEnum: string
{
    case PENDING   = 'pending';
    case PROCESSING = 'processing';
    case SUCCESS   = 'success';
    case FAILED    = 'failed';
}
