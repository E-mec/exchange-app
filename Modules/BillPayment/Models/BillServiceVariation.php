<?php

namespace Modules\BillPayment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BillPayment\Database\Factories\BillServiceVariationFactory;

class BillServiceVariation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'bill_service_id',
        'name',
        'variation_code',
        'amount',
        'validity',
        'is_fixed_price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'is_fixed_price' => 'boolean',
        'is_active'      => 'boolean',
        'sort_order'     => 'integer',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(BillService::class, 'bill_service_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
     protected static function newFactory(): BillServiceVariationFactory
     {
          return BillServiceVariationFactory::new();
     }
}
