<?php

namespace Modules\Payment\dtos;

readonly class PaymentVerificationDto
{
    public function __construct(
        public bool    $successful,
        public string  $reference,
        public ?string $providerReference,
        public array   $rawResponse = []
    ) {}
}
