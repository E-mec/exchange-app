<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bill_services', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('type');
            $table->string('provider');
            $table->string('provider_service_id');
            $table->string('country_code', 5)->default('NG');
            $table->decimal('min_amount', 18, 2)->nullable();      // was integer
            $table->decimal('max_amount', 18, 2)->nullable();      // was integer
            $table->decimal('commission_rate', 5, 4)->default(0);  // was integer
            $table->boolean('has_variations')->default(false);     // MISSING
            $table->boolean('requires_validation')->default(false);
            $table->string('image_url')->nullable();               // MISSING
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_service_id']); // MISSING — sync upsert relies on this
            $table->index(['type', 'is_active']);
            $table->index(['provider', 'is_active']);


        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE bill_services ADD CONSTRAINT chk_bill_service_type
                CHECK (type IN ('airtime','data','electricity','tv','internet','water','betting'))");

            DB::statement("ALTER TABLE bill_services ADD CONSTRAINT chk_bill_service_provider
                CHECK (provider IN ('vtpass','buypower','baxi'))");
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_services');
    }
};
