<?php

namespace Modules\Wallet\actions;

use Illuminate\Support\Facades\DB;
use Modules\Wallet\app\Interfaces\ReleaseReservedFunds;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\Withdrawal;

final class FailWithdrawalAction
{
    public function __construct(
        protected ReleaseReservedFunds $release
    ) {}

    public function execute(Withdrawal $withdrawal, ?string $reason = null): void
    {
        if ($withdrawal->status === WithdrawalStatusEnum::FAILED) {
            return; // idempotent
        }

        DB::transaction(function () use ($withdrawal, $reason) {

            $this->release->execute([
                'reference'       => $withdrawal->reference,
                'idempotencyKey'  => $withdrawal->reference . ':release',
                'reason'          => $reason ?? 'withdrawal_failed',
            ]);

            $withdrawal->update([
                'status'          => WithdrawalStatusEnum::FAILED,
                'failure_reason'  => $reason,
            ]);
        });
    }
}
