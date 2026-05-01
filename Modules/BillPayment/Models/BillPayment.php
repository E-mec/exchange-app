<?php

namespace Modules\BillPayment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\BillPayment\Database\Factories\BillPaymentFactory;

class BillPayment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): BillPaymentFactory
    // {
    //     // return BillPaymentFactory::new();
    // }
}
