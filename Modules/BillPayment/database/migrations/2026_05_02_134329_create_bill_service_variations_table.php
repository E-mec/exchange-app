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
        Schema::create('bill_service_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_service_id')
                ->constrained('bill_services')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('variation_code');
            $table->decimal('amount', 18, 2);
            $table->string('validity')->nullable();   // '30 days', '1 month'
            $table->boolean('is_fixed_price')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['bill_service_id', 'variation_code']);
            $table->index(['bill_service_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_service_variations');
    }
};
