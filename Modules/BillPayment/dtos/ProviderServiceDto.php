<?php

namespace Modules\BillPayment\dtos;

use Modules\BillPayment\Enums\BillProviderEnum;
use Modules\BillPayment\Enums\BillTypeEnum;

readonly class ProviderServiceDto
{
    public function __construct(
        public string          $name,
        public string          $providerServiceId,
        public BillTypeEnum    $type,
        public BillProviderEnum $provider,
        public bool            $hasVariations,
        public bool            $requiresValidation,
        public ?float          $minAmount = null,
        public ?float          $maxAmount = null,
        public ?string         $imageUrl  = null,
    ) {}
}
