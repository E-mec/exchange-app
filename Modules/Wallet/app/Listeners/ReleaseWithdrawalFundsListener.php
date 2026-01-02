<?php

namespace Modules\Wallet\app\Listeners;

use Modules\Wallet\app\Events\WithdrawalFailed;
use Modules\Wallet\actions\ReleaseReservedFundsAction;

final class ReleaseWithdrawalFundsListener
{
    public function __construct(
        protected ReleaseReservedFundsAction $release
    ) {}

    public function handle(WithdrawalFailed $event): void
    {
        $this->release->execute([
            'reference'       => $event->reference,
            'idempotencyKey'  => $event->reference . ':release',
            'reason'          => $event->reason ?? 'withdrawal_failed',
        ]);
    }
}
