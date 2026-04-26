<?php

namespace Modules\Wallet\app\Jobs;

use App\Exceptions\CustomException;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Wallet\actions\ReleaseReservedFundsAction;
use Modules\Wallet\app\Interfaces\ReleaseReservedFunds;
use Modules\Wallet\enums\WithdrawalStatusEnum;
use Modules\Wallet\Models\Withdrawal;

class ReconcileWithdrawalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */

    public function handle(ReleaseReservedFunds $release): void
    {
        Withdrawal::where('status', WithdrawalStatusEnum::FAILED)
            ->where('is_reconciled', false)
            ->chunkById(100, function ($withdrawals) use ($release) {
                foreach ($withdrawals as $withdrawal) {
                    $this->reconcileFailed($withdrawal, $release);
                }
            });
    }

    /**
     */
    private function reconcileFailed(Withdrawal $withdrawal, ReleaseReservedFunds $release): void
    {
        $release->execute([  // ← use the injected instance
            'reference' => $withdrawal->reference,
            'idempotencyKey' => 'reconcile:' . $withdrawal->reference,
            'reason' => 'reconciliation_release',
        ]);

        $withdrawal->update([
            'is_reconciled' => true,
            'reconciled_at' => now(),
        ]);
    }}
