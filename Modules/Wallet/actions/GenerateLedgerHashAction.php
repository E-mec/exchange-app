<?php

namespace Modules\Wallet\actions;

use Modules\Wallet\Models\WalletTransaction;

class GenerateLedgerHashAction
{
    /**
     * Generate checksum + ledger chain hash for a transaction.
     *
     * @param  WalletTransaction|array  $data
     * @param  string|null $previousHash
     * @return array
     */
    public function execute(array $data, ?string $previousHash): array
    {

        $checksum = hash('sha256', json_encode([
            'wallet_id'      => $data['wallet_id'],
            'user_id'        => $data['user_id'],
            'before_balance' => $data['before_balance'],
            'amount'         => $data['amount'],
            'balance_after'  => $data['balance_after'],
            'currency'       => $data['currency'],
            'type'           => $data['type'],
            'reference'      => $data['reference'],
            'idempotency_key' => $data['idempotency_key'],
        ]));

        $currentHash = hash('sha256', ($previousHash ?? '') . $checksum);

        return [
            'checksum'      => $checksum,
            'previous_hash' => $previousHash,
            'current_hash'  => $currentHash,
        ];
    }
}
