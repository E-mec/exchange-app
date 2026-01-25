<?php

namespace Modules\Payment\app\Interfaces;

use Illuminate\Http\Request;

interface WebhookVerifierInterface
{
    public function verify(Request $request): void;
}
