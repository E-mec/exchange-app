<?php

namespace Modules\Payment\Enums;

enum PaymentStatusEnum: string
{
    case PENDING = 'pending';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}

