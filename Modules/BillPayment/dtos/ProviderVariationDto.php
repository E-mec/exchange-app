<?php

namespace Modules\BillPayment\dtos;

readonly class ProviderVariationDto
{
    public function __construct(
        public string  $name,
        public string  $variationCode,
        public float   $amount,
        public bool    $isFixedPrice,
        public ?string $validity = null,
    ) {}
}
