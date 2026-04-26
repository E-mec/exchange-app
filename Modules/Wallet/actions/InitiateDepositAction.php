<?php

namespace Modules\Wallet\actions;

use App\Enums\StatusEnum;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Modules\Wallet\app\Events\DepositInitiatedEvent;
use Modules\Wallet\Models\PaymentIntent;

class InitiateDepositAction
{
    public function handle($amount, $currency, $channel, string $idempotencyKey): PaymentIntent
    {
        $user = auth()->user();
        $lockKey = sprintf('deposit:init:%s:%s', $user->id, $idempotencyKey);

        try {
            return Cache::lock($lockKey, 10)->block(5, function () use ($user, $amount, $currency, $channel, $idempotencyKey) {
                $intent = DB::transaction(function () use ($user, $amount, $currency, $channel, $idempotencyKey) {
                    $existingIntent = PaymentIntent::query()
                        ->where('user_id', $user->id)
                        ->where('idempotency_key', $idempotencyKey)
                        ->lockForUpdate()
                        ->first();

                    if ($existingIntent) {
                        return $existingIntent;
                    }

                    return PaymentIntent::create([
                        'user_id' => $user->id,
                        'reference' => 'DEP-' . strtoupper(Str::random(12)),
                        'amount' => $amount,
                        'currency' => $currency,
                        'status' => StatusEnum::PENDING,
                        'channel' => $channel,
                        'idempotency_key' => $idempotencyKey,
                    ]);
                });

                if ($intent->status === StatusEnum::SUCCESS || $intent->status === StatusEnum::FAILED) {
                    return $intent;
                }

                if ($intent->checkout_url && in_array($intent->status, [StatusEnum::PENDING, StatusEnum::PROCESSING], true)) {
                    return $intent;
                }

                $responses = event(new DepositInitiatedEvent(
                    userId: $user->id,
                    currency: $currency,
                    amount: (string) $amount,
                    reference: $intent->reference,
                    provider: $channel,
                ));

                $intent = $intent->refresh();

                $payment = collect($responses)->first(fn ($response) => is_array($response));

                if (! is_array($payment) || ! isset($payment['checkout_url'])) {
                    throw new RuntimeException('Unable to initialize deposit payment.');
                }

                $intent->update([
                    'status' => StatusEnum::PROCESSING,
                    'payment_id' => $payment['payment_id'] ?? $intent->payment_id,
                    'provider_reference' => $payment['provider_reference'] ?? $intent->provider_reference,
                    'checkout_url' => $payment['checkout_url'],
                    'meta' => array_merge($intent->meta ?? [], [
                        'payment' => $payment,
                    ]),
                ]);

                return $intent->refresh();
            });
        } catch (LockTimeoutException $exception) {
            throw new RuntimeException('Deposit initialization is already in progress.', previous: $exception);
        }
    }
}
