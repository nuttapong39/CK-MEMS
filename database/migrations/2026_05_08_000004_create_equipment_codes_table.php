<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name_th');
            $table->string('name_en')->nullable();
            $table->foreignId('risk_level_id')->nullable()->constrained('risk_levels')->nullOnDelete();
            $table->unsignedSmallInteger('default_calibration_cycle_months')->default(12);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_codes');
    }
};
