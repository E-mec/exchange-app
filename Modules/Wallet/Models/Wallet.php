<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Models\User;
use Modules\Wallet\app\Observers\WalletObserver;
use Modules\Wallet\Database\Factories\WalletFactory;
use Modules\Wallet\enums\CurrencyEnum;
use Modules\Wallet\enums\WalletStatusEnum;

#[ObservedBy([WalletObserver::class])]
class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'currency',
        'ledger_balance',
        'available_balance',
        'reserved_balance',
        'status',
        'meta'
    ];

    protected $casts = [
        'available_balance' => 'decimal:18',
        'reserved_balance' => 'decimal:18',
        'ledger_balance' => 'decimal:18',
        'meta' => 'array',
        'status' => WalletStatusEnum::class,
        'currency' => CurrencyEnum::class,
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public static function forUser(int $userId, string $currency): ?self
    {
        return self::query()
            ->where('user_id', $userId)
            ->where('currency', $currency)
            ->lockForUpdate() // IMPORTANT for transfers
            ->first();
    }

     protected static function newFactory(): WalletFactory
     {
          return WalletFactory::new();
     }
}
