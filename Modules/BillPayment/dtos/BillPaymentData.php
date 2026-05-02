<?php

namespace Modules\BillPayment\dtos;

use Modules\BillPayment\Enums\BillTypeEnum;
use Spatie\LaravelData\Data;

class BillPaymentData extends Data
{
    public function __construct(
        public readonly string $reference,
        public readonly BillTypeEnum $type,
        public readonly string $serviceId,   // 'mtn','dstv'...
        public readonly string $recipient,    // phone/meter/card
        public readonly float  $amount,
        public readonly string $currency,
        public readonly ?string $variationCode,
        public readonly array  $meta = [],
    ) {}
}
