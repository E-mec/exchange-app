<?php

namespace Modules\Payment\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Payment\app\Resolver\WebhookVerifierResolver;
use Modules\Payment\Enums\PaymentProviderEnum;

class VerifyPaymentWebhook
{
    public function __construct(
        private readonly WebhookVerifierResolver $resolver
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $provider = PaymentProviderEnum::tryFrom(
            $request->route('provider')
        );

        if (! $provider) {
            abort(404, 'Unsupported payment provider');
        }

        $verifier = $this->resolver->resolve($provider);

        // MUST throw on failure
        $verifier->verify($request);

        return $next($request);
    }

}
