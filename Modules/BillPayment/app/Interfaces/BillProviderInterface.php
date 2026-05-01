<?php

namespace Modules\BillPayment\app\Interfaces;

use Modules\BillPayment\dtos\BillPaymentData;

interface BillProviderInterface
{
    /** Purchase airtime, data, electricity etc */
    public function purchase(
        BillPaymentData $dto
    ): array;

    /** Re-query status (for pending responses) */
    public function queryStatus(
        string $requestId
    ): array;

    /** Verify webhook payload signature */
    public function verifyWebhook(
        Request $request
    ): bool;
}

