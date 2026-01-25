<?php

namespace Modules\Payment\dtos;

readonly class PaymentInitializationDto
{
    public function __construct(
        public string $reference,
        public float  $amount,
        public string $currency,
        public string $callbackUrl,
        public array  $metadata = []
    ) {}
}
