<?php

namespace App\Enums;

enum StatusEnum: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case REVERSED = 'reversed';
    case PROCESSING = 'processing';
}
