<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;
use Modules\Wallet\app\Observers\WithdrawalObserver;
use Modules\Wallet\database\factories\WithdrawalFactory;
use Modules\Wallet\enums\WithdrawalStatusEnum;

#[ObservedBy([WithdrawalObserver::class])]
class Withdrawal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'wallet_id',
        'user_id',
        'amount',
        'currency',
        'reference',
        'provider_reference',
        'status',
        'meta',
        'failure_reason',
        'is_reconciled',
        'reconciled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:18',
        'meta'   => 'array',
        'status' => WithdrawalStatusEnum::class,
        'reconciled_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

     protected static function newFactory(): WithdrawalFactory
     {
          return WithdrawalFactory::new();
     }
}
