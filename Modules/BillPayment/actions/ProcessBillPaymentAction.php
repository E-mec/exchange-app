<?php

namespace Modules\BillPayment\actions;

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

    public function handle(BillPayment $billPayment): void
    {
        $billPayment->update(['status' => BillStatusEnum::PROCESSING->value]);

        $provider = $this->resolver->resolve(
            BillProviderEnum::from($billPayment->provider)
        );

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

            $success = in_array($response['code'] ?? $response['status'] ?? '', ['000', 'success', 'delivered']);

            if ($success) {
                $billPayment->update([
                    'status'             => BillStatusEnum::SUCCESSFUL->value,
                    'provider_reference' => $response['content']['transactions']['transactionId'] ?? null,
                    'token'              => $response['content']['transactions']['token'] ?? null,
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
            $billPayment->update([
                'status' => BillStatusEnum::FAILED->value,
                'meta'   => array_merge($billPayment->meta ?? [], ['exception' => $e->getMessage()]),
            ]);

            event(new BillPaymentFailed($billPayment));
            throw $e; // let the job retry
        }
    }
}
