<?php

namespace Modules\BillPayment\app\Interfaces;

use Illuminate\Http\Request;
use Modules\BillPayment\dtos\BillPaymentData;
use Modules\BillPayment\dtos\ProviderServiceDto;
use Modules\BillPayment\dtos\ProviderVariationDto;

interface BillProviderInterface
{

    /**
     * Fetch all services this provider supports.
     *
     * @return ProviderServiceDto[]
     */
    public function getServices(): array;

    /**
     * Fetch all variation codes for a specific service.
     *
     * @return ProviderVariationDto[]
     */
    public function getVariations(string $providerServiceId): array;


    /**
     * Validate a recipient (meter number, smartcard, user ID etc.)
     * before a purchase. Only called when requires_validation = true.
     */
    public function validateRecipient(string $providerServiceId, string $recipient): array;

    /**
     * Execute the bill payment with the provider.
     */
    public function purchase(BillPaymentData $dto): array;

    /**
     * Re-query a transaction status by the provider's reference.
     */
    public function queryStatus(string $requestId): array;

    /**
     * Verify the webhook signature/payload is genuinely from this provider.
     */
    public function verifyWebhook(Request $request): bool;

    public function isSuccessful(array $response): bool;

}

