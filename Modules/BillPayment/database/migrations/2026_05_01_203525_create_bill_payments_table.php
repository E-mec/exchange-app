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
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained();
            $table->foreignId('bill_service_id')->constrained('bill_services');  // ADD — was missing
            $table->string('service_id');
            $table->string('variation_code')->nullable();                         // ADD — needed for data/tv/internet
            $table->decimal('amount', 18, 2);
            $table->string('reference')->unique();
            $table->string('idempotency_key')->unique();
            $table->string('type');
            $table->string('provider');
            $table->string('recipient');
            $table->string('currency', 10);
            $table->string('status')->default('pending');
            $table->string('provider_reference')->nullable();
            $table->string('token')->nullable();
            $table->unsignedBigInteger('wallet_reserve_tx_id')->nullable();
            $table->foreign('wallet_reserve_tx_id')
                ->references('id')
                ->on('wallet_transactions')
                ->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['provider', 'provider_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};
