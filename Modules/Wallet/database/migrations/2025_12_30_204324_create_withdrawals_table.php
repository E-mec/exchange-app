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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 36, 18);
            $table->string('currency', 10);

            $table->string('reference')->unique();
            $table->string('provider_reference')->nullable();
            $table->boolean('is_reconciled')->default(false)->index();
            $table->timestamp('reconciled_at')->nullable();

            $table->string('status')->index(); // pending, processing, success, failed, reversed
            $table->json('meta')->nullable();

            $table->string('failure_reason')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
