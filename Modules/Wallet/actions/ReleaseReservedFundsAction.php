<?php

namespace Modules\Wallet\actions;

use App\Exceptions\CustomException;
use Modules\Wallet\app\Interfaces\ReleaseReservedFunds;
use Modules\Wallet\enums\TransactionTypeEnum;
use Modules\Wallet\Models\WalletTransaction;

final class ReleaseReservedFundsAction implements ReleaseReservedFunds
{
    public function __construct(
        protected ApplyTransactionOrchestratorAction $orchestrator
    ) {}

    /**
     * @throws CustomException
     */
    public function execute(array $data): WalletTransaction
    {
        // 1. Find reserve transaction
        $reserveTxn = WalletTransaction::where('reference', $data['reference'])
            ->where('type', TransactionTypeEnum::RESERVE)
            ->lockForUpdate()
            ->first();

        if (! $reserveTxn) {
            throw new CustomException('Reserve transaction not found.');
        }

        // 2. Prevent double release
        if ($reserveTxn->is_finalized) {
            throw new CustomException('Reserve already finalized.');
        }

        // 3. Release via orchestrator
        $releaseTxn = $this->orchestrator->execute([
            'walletId'       => $reserveTxn->wallet_id,
            'currency'       => $reserveTxn->currency,
            'amount'         => $reserveTxn->amount,
            'type'           => TransactionTypeEnum::RESERVE_RELEASE,
            'reference'      => $reserveTxn->reference,
            'idempotencyKey' => $data['idempotencyKey'],
            'meta' => [
                'released_from' => $reserveTxn->id,
                'reason'        => $data['reason'] ?? 'payment_failed',
            ],
        ]);

        // 4. Mark reserve as finalized
        $reserveTxn->update([
            'is_finalized' => true,
        ]);

        return $releaseTxn;
    }
}
