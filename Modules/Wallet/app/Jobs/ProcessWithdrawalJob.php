<?php

namespace Modules\Wallet\app\Jobs;

use App\Exceptions\CustomException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Payment\actions\InitiatePayoutAction;
use Modules\Wallet\app\Events\WithdrawalFailed;
use Modules\Wallet\Models\Withdrawal;

class ProcessWithdrawalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120]; // Progressive backoff

    public function __construct(
        public readonly string $withdrawalReference,
    ) {}

    /**
     * Execute the job.
     * @throws \Exception
     */
//    public function handle(InitiatePayoutAction $payoutAction): void
//    {
//        $withdrawal = Withdrawal::query()
//            ->where('reference', $this->withdrawalReference)
//            ->lockForUpdate()
//            ->first();
//
//        if (!$withdrawal) {
//            throw new CustomException("Withdrawal with reference {$this->withdrawalReference} not found");
//        }
//
//        try {
//            // Determine payout provider from config (default per currency or global default)
//            $provider = config(
//                'payment.payout_provider.' . strtolower($withdrawal->currency->value ?? $withdrawal->currency),
//                config('payment.default_payout_provider', 'paystack')
//            );
//
//            // Call Payment module to initiate the payout
//            $payment = $payoutAction->execute([
//                'reference'   => $withdrawal->reference,
//                'amount'      => $withdrawal->amount,
//                'currency'    => $withdrawal->currency->value ?? $withdrawal->currency,
//                'destination' => $withdrawal->meta['destination'] ?? [],
//                'provider'    => $provider,
//                'meta'        => [
//                    'withdrawal_id' => $withdrawal->id,
//                    'reason'        => 'Withdrawal payout',
//                ],
//            ], $withdrawal->user_id);
//
//            // Store the payment reference on the withdrawal for cross-tracking
//            $withdrawal->update([
//                'provider_reference' => $payment->provider_reference,
//                'meta' => array_merge($withdrawal->meta ?? [], [
//                    'payment_id'        => $payment->id,
//                    'provider_response' => $payment->meta['provider_response'] ?? [],
//                ]),
//            ]);
//
//            Log::info('ProcessWithdrawalJob: Payout initiated, awaiting webhook.', [
//                'withdrawal_reference' => $withdrawal->reference,
//                'payment_id'           => $payment->id,
//                'provider'             => $provider,
//            ]);
//
//            // The webhook from the payment provider will fire PaymentSuccessful/PaymentFailed,
//            // which the Wallet listeners will pick up to finalize or fail the withdrawal.
//
//        } catch (\Exception $exception) {
//            report($exception);
//
//            Log::error('ProcessWithdrawalJob: Payout attempt failed.', [
//                'withdrawal_reference' => $this->withdrawalReference,
//                'attempt'              => $this->attempts(),
//                'max_tries'            => $this->tries,
//                'error'                => $exception->getMessage(),
//            ]);
//
//            // If retries remain, release with backoff — Laravel handles this via $backoff property.
//            // If this is the last attempt, let it fall through to failed().
//            if ($this->attempts() < $this->tries) {
//                $this->release($this->backoff[$this->attempts() - 1] ?? 120);
//                return;
//            }
//
//            // Last attempt — let the job fail naturally so failed() is called.
//            throw $exception;
//        }
//    }
//
//    /**
//     * Handle a job failure after all retries are exhausted.
//     */
//    public function failed(\Throwable $exception): void
//    {
//        Log::error('ProcessWithdrawalJob: All attempts exhausted, dispatching WithdrawalFailed.', [
//            'withdrawal_reference' => $this->withdrawalReference,
//            'error'                => $exception->getMessage(),
//        ]);
//
//        WithdrawalFailed::dispatch($this->withdrawalReference, $exception->getMessage());
//    }

    public function handle(InitiatePayoutAction $payoutAction): void
    {
        $withdrawal = Withdrawal::query()
            ->where('reference', $this->withdrawalReference)
            ->first();

        if (! $withdrawal) {
            throw new CustomException("Withdrawal {$this->withdrawalReference} not found");
        }

        // Idempotency guard — already dispatched to provider, wait for webhook
        if ($withdrawal->provider_reference) {
            return;
        }

        $provider = config(
            'payment.payout_provider.' . strtolower($withdrawal->currency->value ?? $withdrawal->currency),
            config('payment.default_payout_provider', 'paystack')
        );

        logger('here', [
            'provider' => $provider,
        ]);

        $payment = $payoutAction->execute([
            'reference'   => $withdrawal->reference,
            'amount'      => $withdrawal->amount,
            'currency'    => $withdrawal->currency->value ?? $withdrawal->currency,
            'destination' => $withdrawal->meta['destination'] ?? [],
            'provider'    => $provider,
            'meta'        => [
                'withdrawal_id' => $withdrawal->id,
                'reason'        => 'Withdrawal payout',
            ],
        ], $withdrawal->user_id);

        $withdrawal->update([
            'provider_reference' => $payment->provider_reference,
            'meta' => array_merge($withdrawal->meta ?? [], [
                'payment_id'        => $payment->id,
                'provider_response' => $payment->meta['provider_response'] ?? [],
            ]),
        ]);

        // Do NOT fire WithdrawalSucceeded here.
        // The payout webhook will call WebhookService → PaymentSuccessful
        // → FinalizeWithdrawalOnPayoutSuccessListener → WithdrawalSucceeded
    }

// Laravel calls this automatically after $tries attempts are all exhausted
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessWithdrawalJob: All retries exhausted.', [
            'withdrawal_reference' => $this->withdrawalReference,
            'error'                => $exception->getMessage(),
        ]);

        WithdrawalFailed::dispatch($this->withdrawalReference, $exception->getMessage());
    }
}
