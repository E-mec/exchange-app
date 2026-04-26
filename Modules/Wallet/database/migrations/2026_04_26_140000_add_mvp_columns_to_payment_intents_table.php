<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable()->after('channel');
            $table->foreignId('payment_id')->nullable()->after('idempotency_key')->constrained('payments')->nullOnDelete();
            $table->string('provider_reference')->nullable()->after('payment_id');
            $table->text('checkout_url')->nullable()->after('provider_reference');
            $table->json('meta')->nullable()->after('checkout_url');

            $table->unique(['user_id', 'idempotency_key'], 'payment_intents_user_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->dropUnique('payment_intents_user_idempotency_unique');
            $table->dropConstrainedForeignId('payment_id');
            $table->dropColumn([
                'idempotency_key',
                'provider_reference',
                'checkout_url',
                'meta',
            ]);
        });
    }
};
