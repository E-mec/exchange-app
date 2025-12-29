<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('currency', 10)->index(); // snapshot at time of txn

            $table->string('type')->index();

            // For integrity + reconciliation
            $table->decimal('before_balance', 36, 18);
            $table->decimal('amount', 36, 18);
            $table->decimal('balance_after', 36, 18);

            $table->string('reference')->unique(); // public reference
            $table->string('idempotency_key')->unique(); // prevent duplicates

            $table->json('meta')->nullable();

            // Anti-tamper mechanisms
            $table->string('checksum')->index();        // hash(currentRow)
            $table->string('previous_hash')->nullable()->index(); // rolling ledger chain
            $table->string('current_hash')->nullable()->index();  // SHA256(prev + row)

            $table->timestamps();

            // High-volume indexing
            $table->index(['wallet_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
