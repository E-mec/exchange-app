<?php

namespace Modules\Wallet\actions;

use App\Exceptions\CustomException;
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

        if ($withdrawal->status === WithdrawalStatusEnum::SUCCESS) {
            throw new CustomException('Successful withdrawals cannot be failed');
        }

        DB::transaction(function () use ($withdrawal, $reason) {
            $lockedWithdrawal = Withdrawal::query()
                ->whereKey($withdrawal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedWithdrawal->status === WithdrawalStatusEnum::FAILED) {
                return;
            }

            if ($lockedWithdrawal->status === WithdrawalStatusEnum::SUCCESS) {
                throw new CustomException('Successful withdrawals cannot be failed');
            }

            $this->release->execute([
                'reference'       => $lockedWithdrawal->reference,
                'idempotencyKey'  => $lockedWithdrawal->reference . ':release',
                'reason'          => $reason ?? 'withdrawal_failed',
            ]);

            $lockedWithdrawal->update([
                'status'          => WithdrawalStatusEnum::FAILED,
                'failure_reason'  => $reason,
            ]);
        });
    }
}
