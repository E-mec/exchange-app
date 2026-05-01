<?php

namespace Modules\BillPayment\Enums;

enum BillStatusEnum: string
{
    case PENDING    = 'pending';
    case PROCESSING = 'processing';
    case SUCCESSFUL = 'successful';
    case FAILED     = 'failed';
    case REFUNDED   = 'refunded';
}
