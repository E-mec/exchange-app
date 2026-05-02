<?php

namespace Modules\BillPayment\app\Resolvers;

use Modules\BillPayment\app\Interfaces\BillProviderInterface;
use Modules\BillPayment\app\Providers\BaxiProvider;
use Modules\BillPayment\app\Providers\BuyPowerProvider;
use Modules\BillPayment\app\Providers\VTPassProvider;
use Modules\BillPayment\Enums\BillProviderEnum;

class BillProviderResolver
{
    public function resolve(BillProviderEnum $provider): BillProviderInterface
    {
        return match ($provider) {
            BillProviderEnum::VTPASS   => app(VTPassProvider::class),
            BillProviderEnum::BUYPOWER => app(BuyPowerProvider::class),
            BillProviderEnum::BAXI     => app(BaxiProvider::class),
        };
    }
}
