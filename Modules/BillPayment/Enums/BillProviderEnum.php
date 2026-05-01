<?php

namespace Modules\BillPayment\Enums;

enum BillProviderEnum: string
{
    case VTPASS   = 'vtpass';
    case BUYPOWER = 'buypower';
    case BAXI     = 'baxi';
}
