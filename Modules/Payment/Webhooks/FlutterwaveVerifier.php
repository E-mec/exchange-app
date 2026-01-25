<?php

namespace Modules\Payment\Webhooks;

use Illuminate\Http\Request;
use Modules\Payment\app\Interfaces\WebhookVerifierInterface;

class FlutterwaveVerifier implements WebhookVerifierInterface
{

    public function verify(Request $request): void
    {
        // TODO: Implement verify() method.
    }
}
