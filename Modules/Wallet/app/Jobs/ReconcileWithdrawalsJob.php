<?php

namespace Modules\Wallet\app\Jobs;

use App\Exceptions\CustomException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Wallet\app\Interfaces\ReleaseReservedFunds;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\Withdrawal;

class ReconcileWithdrawalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

//    public function handle(ReleaseReservedFunds $release): void
//    {
//        Withdrawal::query()
//            ->where('status', WithdrawalStatusEnum::FAILED)
//            ->where('is_reconciled', false)
//            ->chunkById(100, function ($withdrawals) use ($release) {
//                foreach ($withdrawals as $withdrawal) {
//                    $this->reconcileFailed($withdrawal, $release);
//                }
//            });
//    }
//
//    private function reconcileFailed(Withdrawal $withdrawal, ReleaseReservedFunds $release): void
//    {
//        try {
//            $release->execute([
//                'reference' => $withdrawal->reference,
//                'idempotencyKey' => 'reconcile:' . $withdrawal->reference,
//                'reason' => 'reconciliation_release',
//            ]);
//        } catch (CustomException $exception) {
//            report($exception);
//
//            return;
//        }
//
//        $withdrawal->update([
//            'is_reconciled' => true,
//            'reconciled_at' => now(),
//        ]);
//    }

    public function handle(ReleaseReservedFunds $release): void
    {
        Withdrawal::query()
            ->where('is_reconciled', false)
            ->where(function ($query) {
                // Case 1: Already marked FAILED but funds not released yet
                $query->where('status', WithdrawalStatusEnum::FAILED)

                    // Case 2: Stuck PROCESSING for over 24 hours (webhook never arrived)
                    ->orWhere(function ($q) {
                        $q->where('status', WithdrawalStatusEnum::PROCESSING)
                            ->where('updated_at', '<', now()->subHours(24));
                    });
            })
            ->chunkById(100, function ($withdrawals) use ($release) {
                foreach ($withdrawals as $withdrawal) {
                    $this->reconcileFailed($withdrawal, $release);
                }
            });
    }

    private function reconcileFailed(Withdrawal $withdrawal, ReleaseReservedFunds $release): void
    {
        try {
            $release->execute([
                'reference'      => $withdrawal->reference,
                'idempotencyKey' => 'reconcile:' . $withdrawal->reference,
                'reason'         => 'reconciliation_release',
            ]);
        } catch (CustomException $exception) {
            report($exception);
            return; // already released — safe to continue
        }

        $withdrawal->update([
            'status'         => WithdrawalStatusEnum::FAILED, // mark PROCESSING → FAILED
            'is_reconciled'  => true,
            'reconciled_at'  => now(),
            'failure_reason' => 'reconciled_after_timeout',
        ]);
    }
}
