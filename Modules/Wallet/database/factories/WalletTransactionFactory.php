<?php

namespace Modules\Wallet\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\enums\CurrencyEnum;
use Modules\Wallet\enums\TransactionTypeEnum;

class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition(): array
    {
        // Pick a random wallet (or create one)
        $wallet = Wallet::factory()->create();

        $before = fake()->randomFloat(8, 0, 1000);
        $amount = fake()->randomFloat(8, -200, 300); // could be credit or debit
        $after  = $before + $amount;

        // ledger hash components
        $rowData = json_encode([
            'wallet_id' => $wallet->id,
            'user_id'   => $wallet->user_id,
            'before'    => $before,
            'amount'    => $amount,
            'after'     => $after,
        ]);

        $checksum = hash('sha256', $rowData);

        return [
            'wallet_id'        => $wallet->id,
            'user_id'          => $wallet->user_id,
            'currency'         => $wallet->currency ?? CurrencyEnum::USD,
            'type'             => fake()->randomElement(TransactionTypeEnum::cases()),

            'before_balance'   => $before,
            'amount'           => $amount,
            'balance_after'    => $after,

            'reference'        => Str::uuid(),
            'idempotency_key'  => Str::uuid(),

            'meta'             => [
                'ip' => fake()->ipv4(),
                'test' => true
            ],

            'checksum'         => $checksum,
            'previous_hash'    => null,
            'current_hash'     => $checksum,
        ];
    }

    /**
     * Force debit transaction
     */
    public function debit(): static
    {
        return $this->state(function () {
            $before = fake()->randomFloat(8, 50, 300);
            $amount = -1 * fake()->randomFloat(8, 10, 100);
            $after = $before + $amount;

            $checksum = hash('sha256', $before.$amount.$after);

            return [
                'before_balance' => $before,
                'amount' => $amount,
                'balance_after' => $after,
                'type' => TransactionTypeEnum::DEBIT,
                'checksum' => $checksum,
                'current_hash' => $checksum,
            ];
        });
    }

    /**
     * Force credit transaction
     */
    public function credit(): static
    {
        return $this->state(function () {
            $before = fake()->randomFloat(8, 0, 300);
            $amount = fake()->randomFloat(8, 10, 200);
            $after = $before + $amount;

            $checksum = hash('sha256', $before.$amount.$after);

            return [
                'before_balance' => $before,
                'amount' => $amount,
                'balance_after' => $after,
                'type' => TransactionTypeEnum::CREDIT,
                'checksum' => $checksum,
                'current_hash' => $checksum,
            ];
        });
    }
}
