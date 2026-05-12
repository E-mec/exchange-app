<?php

namespace Modules\BillPayment\actions;

use Illuminate\Support\Facades\Log;
use Modules\BillPayment\app\Events\BillPaymentFailed;
use Modules\BillPayment\app\Events\BillPaymentSuccessful;
use Modules\BillPayment\app\Resolvers\BillProviderResolver;
use Modules\BillPayment\dtos\BillPaymentData;
use Modules\BillPayment\Enums\BillProviderEnum;
use Modules\BillPayment\Enums\BillStatusEnum;
use Modules\BillPayment\Models\BillPayment;

class ProcessBillPaymentAction
{
    public function __construct(private readonly BillProviderResolver $resolver) {}

    /**
     * @throws \Throwable
     */
    public function handle(BillPayment $billPayment): void
    {
        $billPayment->update(['status' => BillStatusEnum::PROCESSING->value]);

//        $provider = $this->resolver->resolve(
//            BillProviderEnum::from($billPayment->provider)
//        );

        $provider = $this->resolver->resolve($billPayment->provider);

        $dto = new BillPaymentData(
            reference:     $billPayment->reference,
            type:          $billPayment->type,
            serviceId:     $billPayment->service_id,
            recipient:     $billPayment->recipient,
            amount:        $billPayment->amount,
            currency:      $billPayment->currency,
            variationCode: $billPayment->variation_code,
            meta:          $billPayment->meta ?? [],
        );

        try {
            $response = $provider->purchase($dto);

            if (($response['code'] ?? '') === '028') {
                Log::info('VTPass: 028 received, re-querying status', [
                    'reference' => $billPayment->reference,
                ]);
                $response = $provider->queryStatus($billPayment->reference);
            }

            $success = $provider->isSuccessful($response);

            if ($success) {
                $billPayment->update([
                    'status'             => BillStatusEnum::SUCCESSFUL->value,
                    'provider_reference' => $response['content']['transactions']['transactionId'] ?? null,
                    'token'              =>  $response['content']['transactions']['token']
                                             ?? $response['content']['transactions']['unique_element']
                                             ?? null,
                    'processed_at'       => now(),
                ]);

                event(new BillPaymentSuccessful($billPayment));
            } else {
                $billPayment->update([
                    'status'    => BillStatusEnum::FAILED->value,
                    'meta'      => array_merge($billPayment->meta ?? [], ['failure_reason' => $response['response_description'] ?? 'Unknown']),
                    'processed_at' => now(),
                ]);

                event(new BillPaymentFailed($billPayment));
            }
        } catch (\Throwable $e) {

            // Log the attempt, update meta — but DON'T mark failed or release funds yet
            $billPayment->update([
                'meta' => array_merge($billPayment->meta ?? [], [
                    'last_exception' => $e->getMessage(),
                    'attempt'        => ($billPayment->meta['attempt'] ?? 0) + 1,
                ]),
            ]);
            throw $e;  // job will retry — funds stay reserved
        }
    }
}
