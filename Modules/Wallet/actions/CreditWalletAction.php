<?php

namespace Modules\Wallet\actions;

use Modules\Wallet\enums\TransactionTypeEnum;
use Modules\Wallet\Models\WalletTransaction;

final class CreditWalletAction
{
    public function __construct(protected ApplyTransactionOrchestratorAction $orchestrator)
    {
    }
    public function execute(array $data): WalletTransaction
    {
        return $this->orchestrator->execute([
            'walletId'        => $data['walletId'],
            'userId'          => $data['userId'],
            'currency'        => $data['currency'],
            'amount'          => $data['amount'],
            'type'            => TransactionTypeEnum::CREDIT,
            'reference'       => $data['reference'],
            'idempotencyKey'  => $data['idempotencyKey'],
            'meta'            => $data['meta'] ?? [],
        ]);
    }
}
