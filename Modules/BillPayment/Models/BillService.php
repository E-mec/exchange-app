<?php

namespace Modules\BillPayment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\BillPayment\Database\Factories\BillServiceFactory;
use Modules\BillPayment\Enums\BillProviderEnum;
use Modules\BillPayment\Enums\BillTypeEnum;

class BillService extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'slug',
        'name',
        'type',
        'provider',
        'provider_service_id',
        'country_code',
        'min_amount',
        'max_amount',
        'commission_rate',
        'has_variations',
        'requires_validation',
        'image_url',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'type'                => BillTypeEnum::class,
        'provider'            => BillProviderEnum::class,
        'has_variations'      => 'boolean',
        'requires_validation' => 'boolean',
        'is_active'           => 'boolean',
        'min_amount'          => 'decimal:2',
        'max_amount'          => 'decimal:2',
        'commission_rate'     => 'decimal:4',
        'meta'                => 'array',
    ];
    public function variations(): HasMany
    {
        return $this->hasMany(BillServiceVariation::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('amount');
    }

    public function allVariations(): HasMany
    {
        return $this->hasMany(BillServiceVariation::class);
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query?->where('is_active', true);
    }

    public function scopeOfType($query, BillTypeEnum $type)
    {
        return $query->where('type', $type->value);
    }

    public function scopeFromProvider($query, BillProviderEnum $provider)
    {
        return $query->where('provider', $provider->value);
    }

     protected static function newFactory(): BillServiceFactory
     {
          return BillServiceFactory::new();
     }
}
