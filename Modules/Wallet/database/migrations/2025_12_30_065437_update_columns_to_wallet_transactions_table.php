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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // drop the wrong unique constraint
            $table->dropUnique(['reference']);

            // make reference non-unique
            $table->index('reference');

            // protect against duplicate tx per wallet
            $table->unique(['wallet_id', 'reference', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropUnique(['wallet_id', 'reference', 'type']);

            // rollback index
            $table->dropIndex(['reference']);

            // restore old behavior (not recommended, but rollback-safe)
            $table->unique('reference');
        });
    }
};
