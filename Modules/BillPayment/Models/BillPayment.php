<?php

namespace Modules\BillPayment\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BillPayment\Database\Factories\BillPaymentFactory;
use Modules\BillPayment\Enums\BillStatusEnum;
use Modules\BillPayment\Enums\BillTypeEnum;
use Modules\BillPayment\Enums\BillProviderEnum;

class BillPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'wallet_id', 'bill_service_id',
        'reference', 'idempotency_key',
        'type', 'provider', 'service_id',
        'recipient', 'variation_code',
        'amount', 'currency', 'status',
        'provider_reference', 'token',
        'wallet_reserve_tx_id', 'meta', 'processed_at',
    ];

    protected $casts = [
        'type'         => BillTypeEnum::class,
        'provider'     => BillProviderEnum::class,
        'status'       => BillStatusEnum::class,
        'amount'       => 'decimal:2',
        'meta'         => 'array',
        'processed_at' => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(BillService::class, 'bill_service_id');
    }

    protected static function newFactory(): BillPaymentFactory
    {
        return BillPaymentFactory::new();
    }
}
