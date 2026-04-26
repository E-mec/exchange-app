<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Payment\Enums\PaymentProviderEnum;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Enums\PaymentTypeEnum;

// use Modules\Payment\Database\Factories\PaymentFactory;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'type',
        'reference',
        'provider',
        'amount',
        'currency',
        'status',
        'provider_reference',
        'meta',
    ];

    protected $casts = [
        'meta'     => 'array',
        'status'   => PaymentStatusEnum::class,
        'provider' => PaymentProviderEnum::class,
        'type'     => PaymentTypeEnum::class,
    ];

    // protected static function newFactory(): PaymentFactory
    // {
    //     // return PaymentFactory::new();
    // }
}
