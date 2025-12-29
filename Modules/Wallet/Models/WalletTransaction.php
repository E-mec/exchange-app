<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Wallet\Database\Factories\WalletTransactionFactory;
use Modules\Wallet\enums\CurrencyEnum;
use Modules\Wallet\enums\TransactionTypeEnum;

class WalletTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'wallet_id',
        'currency',
        'type',
        'before_balance',
        'amount',
        'balance_after',
        'reference',
        'idempotency_key',
        'meta',
        'checksum',
        'previous_hash',
        'current_hash',
        'is_finalized',
        'finalized_at'
    ];

    protected $casts = [
        'amount' => 'decimal:18',
        'balance_after' => 'decimal:18',
        'before_balance' => 'decimal:18',
        'meta' => 'array',
        'type' => TransactionTypeEnum::class,
        'currency' => CurrencyEnum::class,
        'is_finalized' => 'boolean'
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

     protected static function newFactory(): WalletTransactionFactory
     {
          return WalletTransactionFactory::new();
     }
}
