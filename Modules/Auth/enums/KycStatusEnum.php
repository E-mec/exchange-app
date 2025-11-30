<?php

namespace Modules\Auth\enums;

enum KycStatusEnum: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
}
