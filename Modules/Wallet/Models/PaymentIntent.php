<?php

namespace Modules\Wallet\Models;

use App\Enums\StatusEnum;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;
use Modules\Wallet\app\Observers\PaymentIntentObserver;

// use Modules\Wallet\Database\Factories\PaymentIntentFactory;

#[ObservedBy([PaymentIntentObserver::class])]
class PaymentIntent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'reference',
        'amount',
        'currency',
        'status',
        'channel',
        'idempotency_key',
        'payment_id',
        'provider_reference',
        'checkout_url',
        'meta',
    ];

    protected $casts = [
        'status' => StatusEnum::class,
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // protected static function newFactory(): PaymentIntentFactory
    // {
    //     // return PaymentIntentFactory::new();
    // }
}
