<?php

namespace Modules\BillPayment\actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\BillPayment\app\Events\BillPaymentFailed;
use Modules\BillPayment\app\Events\BillPaymentSuccessful;
use Modules\BillPayment\app\Resolvers\BillProviderResolver;
use Modules\BillPayment\Enums\BillStatusEnum;
use Modules\BillPayment\Models\BillPayment;

class VerifyBillPaymentAction
{
    public function __construct(
        private readonly BillProviderResolver $resolver
    ) {}

    /**
     * Re-query a bill payment from the provider and settle it.
     *
     * Called by:
     *  - ProcessBillPaymentJob::failed()     (after all retries exhausted — optional re-query before giving up)
     *  - SweepStalePaymentsJob               (scheduled sweep of PROCESSING payments)
     *  - BillPaymentController::status()     (on-demand re-query for PROCESSING payments)
     *  - BillWebhookController               (provider-initiated status update)
     *
     * Returns the refreshed BillPayment model.
     */
    public function handle(BillPayment $billPayment): BillPayment
    {
        // Only act on payments that are still unresolved
        if (! in_array($billPayment->status, [
            BillStatusEnum::PENDING,
            BillStatusEnum::PROCESSING,
        ])) {
            return $billPayment;
        }

        $provider = $this->resolver->resolve($billPayment->provider);

        try {
            $response = $provider->queryStatus($billPayment->reference);
        } catch (\Throwable $e) {
            Log::error('VerifyBillPaymentAction: provider queryStatus failed', [
                'reference' => $billPayment->reference,
                'provider'  => $billPayment->provider->value,
                'error'     => $e->getMessage(),
            ]);

            // Cannot determine status — leave as PROCESSING for next sweep
            return $billPayment;
        }

        return DB::transaction(function () use ($billPayment, $provider, $response) {

            // Re-fetch with lock to prevent race with webhook arriving simultaneously
            $billPayment = BillPayment::lockForUpdate()->findOrFail($billPayment->id);

            // Guard: another process may have already settled it
            if (! in_array($billPayment->status, [
                BillStatusEnum::PENDING,
                BillStatusEnum::PROCESSING,
            ])) {
                return $billPayment;
            }

            if ($provider->isSuccessful($response)) {
                $billPayment->update([
                    'status'             => BillStatusEnum::SUCCESSFUL->value,
                    'provider_reference' => $this->extractProviderReference($response),
                    'token'              => $this->extractToken($response),
                    'processed_at'       => now(),
                ]);

                event(new BillPaymentSuccessful($billPayment->refresh()));

            } elseif ($this->isFailed($response)) {
                $billPayment->update([
                    'status'       => BillStatusEnum::FAILED->value,
                    'processed_at' => now(),
                    'meta'         => array_merge($billPayment->meta ?? [], [
                        'verify_failure_reason' => $this->extractFailureReason($response),
                    ]),
                ]);

                event(new BillPaymentFailed($billPayment->refresh()));

            } else {
                // Still processing on provider side — update last checked timestamp
                $billPayment->update([
                    'meta' => array_merge($billPayment->meta ?? [], [
                        'last_verified_at' => now()->toISOString(),
                    ]),
                ]);
            }

            return $billPayment->refresh();
        });
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * A response is definitively failed when the provider confirms it.
     * "Unknown" or "pending" responses are NOT treated as failures here —
     * those are left for the next sweep cycle.
     */
    private function isFailed(array $response): bool
    {
        $code = $response['code'] ?? $response['responseCode'] ?? '';

        return in_array($code, [
            '099', '016',  // VTPass — reversed / transaction failed
            '99',          // BuyPower — transaction failed
            'failed',      // Baxi
        ]);
    }

    private function extractProviderReference(array $response): ?string
    {
        return $response['content']['transactions']['transactionId']
            ?? $response['transactionId']
            ?? $response['transaction_id']
            ?? null;
    }

    private function extractToken(array $response): ?string
    {
        return $response['content']['transactions']['token']
            ?? $response['token']
            ?? null;
    }

    private function extractFailureReason(array $response): string
    {
        return $response['response_description']
            ?? $response['message']
            ?? $response['error']
            ?? 'Unknown failure reason';
    }
}
