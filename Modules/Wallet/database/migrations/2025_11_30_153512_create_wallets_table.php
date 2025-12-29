<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Wallet\enums\WalletStatusEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('currency', 10)->index(); // e.g., NGN, BTC, USDT

            $table->decimal('available_balance', 36, 18)->default(0);
            $table->decimal('reserved_balance', 36, 18)->default(0); // locked funds for pending tx
            $table->decimal('ledger_balance', 36, 18)->default(0); // sum of ledger entries

            $table->json('meta')->nullable(); // optional metadata (e.g., blockchain address)

            $table->string('status')->default(WalletStatusEnum::ACTIVE);

            $table->timestamps();
            $table->softDeletes();

            // Unique wallet per user per currency
            $table->unique(['user_id', 'currency']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
