<?php

namespace Modules\Wallet\actions;

use App\Exceptions\CustomException;
use Modules\Wallet\enums\TransactionTypeEnum;

final class BalanceCalculatorAction
{
    /**
     * @throws CustomException
     */
    public function execute(
        string $available,
        string $reserved,
        string $amount,
        TransactionTypeEnum $type
    ): array {
        $scale = 18;

        return match ($type) {
            TransactionTypeEnum::CREDIT => [
                'available' => bcadd($available, $amount, $scale),
                'reserved'  => $reserved,
            ],

            TransactionTypeEnum::RESERVE => $this->reserve($available, $reserved, $amount, $scale),

            TransactionTypeEnum::RESERVE_RELEASE => $this->release($available, $reserved, $amount, $scale),

            TransactionTypeEnum::DEBIT => $this->debit($available, $reserved, $amount, $scale),

            default => throw new CustomException("Unsupported transaction type: {$type->value}"),
        };
    }

    private function reserve(string $available, string $reserved, string $amount, int $scale): array
    {
        if (bccomp($available, $amount, $scale) < 0) {
            throw new CustomException('Insufficient available balance to reserve.');
        }

        return [
            'available' => bcsub($available, $amount, $scale),
            'reserved'  => bcadd($reserved, $amount, $scale),
        ];
    }

    private function release(string $available, string $reserved, string $amount, int $scale): array
    {
        if (bccomp($reserved, $amount, $scale) < 0) {
            throw new CustomException('Insufficient reserved balance to release.');
        }

        return [
            'available' => bcadd($available, $amount, $scale),
            'reserved'  => bcsub($reserved, $amount, $scale),
        ];
    }

    private function debit(string $available, string $reserved, string $amount, int $scale): array
    {
        if (bccomp($reserved, $amount, $scale) < 0) {
            throw new CustomException('Insufficient reserved balance to debit.');
        }

        return [
            'available' => $available,
            'reserved'  => bcsub($reserved, $amount, $scale),
        ];
    }
}
