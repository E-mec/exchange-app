<?php

namespace Modules\Wallet\Models;

use App\Enums\StatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

// use Modules\Wallet\Database\Factories\PaymentIntentFactory;

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
        'channel'
    ];

    protected $casts = [
        'status' => StatusEnum::class,
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
