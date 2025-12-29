<?php

namespace Modules\Wallet\enums;

enum WalletStatusEnum: string
{
    case ACTIVE = 'active';
    case FROZEN = 'frozen';
    case MAINTENANCE = 'maintenance';

}
