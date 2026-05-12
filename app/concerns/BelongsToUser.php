<?php

namespace App\concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

trait BelongsToUser
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
