<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name_th');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('color_hex', 7)->default('#94A3B8');
            $table->unsignedSmallInteger('recommended_calibration_months')->default(12);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_levels');
    }
};
