<?php

namespace Modules\BillPayment\Enums;

enum BillTypeEnum: string
{
    case AIRTIME     = 'airtime';
    case DATA        = 'data';
    case ELECTRICITY = 'electricity';
    case TV          = 'tv';
    case INTERNET    = 'internet';
    case WATER       = 'water';
    case BETTING     = 'betting';
}
